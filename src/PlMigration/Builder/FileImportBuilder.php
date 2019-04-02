<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/22/18
 */

namespace PlMigration\Builder;

use PlMigration\Builder\Helper\ImportBuilder;
use PlMigration\Builder\Traits\ApiBuilderTrait;
use PlMigration\Builder\Traits\CsvBuilderTrait;
use PlMigration\Client\LocalClient;
use PlMigration\Client\RemoteClient;
use PlMigration\Connectors\APIv3Connector;
use PlMigration\Exceptions\BuilderException;
use PlMigration\Exceptions\ClientException;
use PlMigration\Exceptions\WriterException;
use PlMigration\Helper\DataFunctions\IValueManipulator;
use PlMigration\Helper\ImportInterface;
use PlMigration\Model\Field\Field;
use PlMigration\Model\Field\ImportField;
use PlMigration\Model\ImportModel;
use PlMigration\PlayerlyncImport;
use PlMigration\Writer\TransactionLogger;

/**
 * Builder to configure and execute the import process. Once configurations are ready, execute the process with the import() function
 * @package PlMigration\Builder
 */
class FileImportBuilder extends ImportBuilder
{
    /** sub classes that are also used to create the import */
    use CsvBuilderTrait;
    use ApiBuilderTrait;

    /**
     * Location of the input data file to be used for import
     *
     * @var string
     */
    protected $inputFile;

    /**
     * Connection protocol that contains the name of the file
     * @var RemoteClient
     */
    private $protocol;

    /**
     * Options that will be held by the importer
     *
     * @var array
     */
    protected $options = [];

    /**
     * File path to the destination of the created transaction files
     * @var string
     */
    private $transactionLogDir;

    /**
     * File path to the destination of where transaction files will be moved to after processing
     * @var string
     */
    private $storeLocation;

    /**
     * Boolean to determine whether or not to delete the source file from its original spot.
     * Default it will not delete the file
     * @var bool
     */
    private $deleteFileWhenDone = false;

    /**
     * ImportBuilder constructor.
     */
    public function __construct()
    {
        $this->protocol = new LocalClient();
    }

    /**
     * Get file to use for importing.
     * If the protocol parameter is set, it will grab from the server specified (ftp, sftp, etc).
     * Otherwise it will try to find the file locally.
     *
     * @param string $file file path to the file, including file name.
     * @param RemoteClient|null $protocol Client object to retrieve file from remote server.
     * @return $this
     */
    public function inputFile($file, $protocol = null)
    {
        $this->inputFile = $file;
        if($protocol !== null)
            $this->protocol = $protocol;
        return $this;
    }

    /**
     * Set a file path to store the input file after being processed.
     * @param string $storeLocation file path destination
     * @return $this
     */
    public function storeFile($storeLocation)
    {
        $this->storeLocation = $storeLocation;
        return $this;
    }

    /**
     * Function to tell the script to delete the data source file from its original location provided in @inputFile
     * Default value: false
     * @param bool $decision
     * @return $this
     */
    public function deleteFileAfterProcess($decision)
    {
        $this->deleteFileWhenDone = $decision;
        return $this;
    }

    /**
     * Toggle to determine if the input file provided contains a header row.
     * By enabling this toggle, the import will ignore the first row of the data file to prevent an unnecessary insertion
     * error into the Playerlync system.
     *
     * @param bool $hasHeaders
     * @return $this
     */
    public function hasHeaders($hasHeaders)
    {
        $this->options['include_headers'] = $hasHeaders;
        return $this;
    }

    /**
     * Set the directory where the transaction files will be created.
     * This will also automatically enable the transaction log functionality.
     * If the directory does not exist, the directory will NOT be created
     *
     * @param string $transactionLogDir Directory path to be used to create the transaction files
     * @return $this
     */
    public function transactionLog($transactionLogDir)
    {
        if($transactionLogDir === '')
            $transactionLogDir = '.';

        $this->transactionLogDir = $transactionLogDir;
        return $this;
    }

    /**
     * Add a field to be mapped from the outside source to the playerlync system.
     * In a file import, the order of method calls indicates the column that will be mapped.
     * example:
     *  data file contents:
     *  testlogmein,smith,denver,qwerty
     *   $importer
     *   ->addField('member') //testlogmein will be set to the member api field
     *   ->addField('last_name') //smith will be set to the last_name field
     *   ->addField('location') //denver will be set to the location api field
     *   ->addField('password') //qwerty will be set to the password api field
     *
     * @param string $apiField Field name recognized by the Playerlync API
     * @param string $type Field type to be recognized. Refer to Field.php constants for types allowed
     * @param array|IValueManipulator $extra
     * @return $this
     */
    public function addField($apiField, $type = Field::VARIABLE, $extra = [])
    {
        $this->fields[] = new ImportField($apiField, count($this->fields), $type, $extra);
        return $this;
    }

    /**
     * Build all objects and execute the import import process
     *
     * @throws BuilderException
     */
    public function import()
    {
        $this->build()->import();

        if($this->storeLocation !== null)
            $this->transportDataSourceToANonVolatileStorageLocation();

        if($this->deleteFileWhenDone)
            $this->deleteFile();
    }

    /**
     * Verify the data provided by the builder functions and create all objects needed for the import
     * @return ImportInterface
     * @throws BuilderException
     */
    protected function build()
    {
        try
        {
            $this->buildErrorLog('ImportLog');

            $model = new ImportModel($this->buildFields());

            $reader = $this->buildReader($this->getInputFile());
            $record = $reader->getRecord();
            foreach($model->getFields() as $field)
            {
                foreach($field->getAliasFields() as $refField)
                {
                    if(!array_key_exists($refField, $record))
                    {
                        throw new BuilderException($field->getField().' is mapped to an invalid column number '. $refField);
                    }
                }
            }
            $api = $this->buildApi($this->errorLog);

            if($api->getPostService() === null)
            {
                throw new BuilderException('postService() method needs to provide a Playerlync API path to run import');
            }

            return $this->buildImporter($model, $reader, $api, $this->buildOptions());
        }
        catch(BuilderException $e)
        {
            $this->addError($e->getMessage());
            throw $e;
        }
    }

    /**
     * Find and verify the input file provided. If a protocol was set, the file will be downloaded.
     * @return string
     * @throws BuilderException
     */
    protected function getInputFile()
    {
        try
        {
            $this->protocol->connect();
            if(substr($this->inputFile, -1) === '*') //If finding a file pattern (ie. /Tests/member-*)
            {
                $fileInfo = pathinfo($this->inputFile);

                $files = $this->protocol->getDirectoryFiles($fileInfo['dirname'], $fileInfo['basename']);

                if(empty($files))
                {
                    throw new BuilderException('No files found in the system that match the pattern '.$fileInfo['basename']);
                }

                $this->inputFile = $fileInfo['dirname'] . '/' . array_shift($files);
            }

            $this->protocol->download($this->inputFile, '.');
            $this->protocol->close();
        }
        catch(ClientException $e)
        {
            throw new BuilderException('Unable to get input file: '. $e->getMessage());
        }
        return pathinfo($this->inputFile,PATHINFO_BASENAME);
    }

    /**
     * Set the import extra options including the transaction log and the logging capability
     *
     * @return array
     * @throws BuilderException
     */
    protected function buildOptions()
    {
        $options = $this->options;
        if($this->transactionLogDir !== null)
        {
            if(!is_dir($this->transactionLogDir) && !mkdir($this->transactionLogDir))
            {
                throw new BuilderException('Unable to create transaction directory');
            }
            try
            {
                $options['transaction_log'] = new TransactionLogger($this->transactionLogDir, $this->inputFile, $this->enclosure, $this->delimiter);
            }
            catch (WriterException $e)
            {
                throw new BuilderException($e->getMessage());
            }
        }

        $options['logger'] = $this->errorLog;

        return $options;
    }

    /**
     * Build PlayerlyncImport object
     * @param $model
     * @param $reader
     * @param $api
     * @param $options
     * @return ImportInterface
     */
    protected function buildImporter($model, $reader, $api, $options)
    {
        return new PlayerlyncImport($api, $reader, $model, $options);
    }

    /**
     * Send data file to the
     * @throws BuilderException
     */
    protected function transportDataSourceToANonVolatileStorageLocation()
    {
        if(!is_dir($this->storeLocation) && !mkdir($this->storeLocation))
        {
            throw new BuilderException('Unable to create store location');
        }

        $fileName = pathinfo($this->inputFile, PATHINFO_BASENAME);
        if(!rename('./'.$fileName, $this->storeLocation . '/' . $fileName))
        {
            throw new BuilderException('Failed to move file to '. $this->storeLocation);
        }
    }

    /**
     * Delete the data source file from its original location.
     */
    protected function deleteFile()
    {
        try
        {
            $this->protocol->connect();
            $this->protocol->delete($this->inputFile);
            $this->protocol->close();
        }
        catch (ClientException $e)
        {
            $this->addError('Failed to delete file, error:'.$e->getMessage());
        }
    }
}