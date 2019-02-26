<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/22/18
 */

namespace PlMigration\Builder;

use PlMigration\Builder\Traits\ApiBuilderTrait;
use PlMigration\Builder\Traits\CsvBuilderTrait;
use PlMigration\Builder\Traits\ErrorLogBuilderTrait;
use PlMigration\Client\IClient;
use PlMigration\Connectors\APIConnector;
use PlMigration\Exceptions\BuilderException;
use PlMigration\Exceptions\ClientException;
use PlMigration\Exceptions\WriterException;
use PlMigration\Helper\DataFunctions\IValueManipulator;
use PlMigration\Model\Field\Field;
use PlMigration\Model\Field\ImportField;
use PlMigration\Model\ImportModel;
use PlMigration\PlayerlyncImport;
use PlMigration\Writer\TransactionLogger;

/**
 * Builder to configure and execute the import process. Once configurations are ready, execute the process with the import() function
 * @package PlMigration\Builder
 */
class FileImportBuilder
{
    /** sub classes that are also used to create the import */
    use CsvBuilderTrait;
    use ApiBuilderTrait;
    use ErrorLogBuilderTrait;

    /**
     * Location of the input data file to be used for import
     *
     * @var string
     */
    private $inputFile;

    /**
     * Connection protocol that contains the name of the file
     * @var IClient
     */
    private $protocol;

    /**
     * Options that will be held by the importer
     *
     * @var array
     */
    private $options = [];

    /**
     * Fields to be used in the import
     * @var array
     */
    private $fields = [];

    /**
     * File path to the destination of the created transaction files
     * @var string
     */
    private $transactionLogDir;

    /**
     * ImportBuilder constructor.
     */
    public function __construct()
    {
        $this->apiVersion('v3');
    }

    /**
     * Get file to use for importing.
     * If the protocol parameter is set, it will grab from the server specified (ftp, sftp, etc).
     * Otherwise it will try to find the file locally.
     *
     * @param string $file file path to the file, including file name.
     * @param IClient|null $protocol Client object to retrieve file from remote server.
     * @return $this
     */
    public function inputFile($file, $protocol = null)
    {
        $this->inputFile = $file;
        $this->protocol = $protocol;
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
        $this->fields[] = new ImportField($apiField, (string)count($this->fields), $type, $extra);
        return $this;
    }

    /**
     * Add a field to be mapped from the outside source to the playerlync system.
     * Unlike addField(), this function provides more flexibility by allowing multiple pieces of outside data point to a single point in Playerlync data, and vice versa.
     * However, it causes a higher likelyhood of bad data mapping.
     * DO NOT mix the addField() and mapField() together for an import. This will result in confusing results.
     * example:
     * data file contents:
     *   testlogin,smith,denver,qwerty
     * $importer
     * ->mapField('first_name', 0) //first_name field will read the first column value (testlogin)
     * ->mapField('last_name', 1) //last_name field will read the second column value (smith)
     * ->mapField('member', 0) //member field will ALSO read the first column value (testlogin)
     * ->mapField('password', 3) //password field will read the fourth column value (qwerty)
     * ->mapField('location', 2) //location field will read the third column value (denver)
     *
     * @param string $apiField The name of the field to be recognized by the Playerlync API
     * @param string $alias The alias point from the external data source.
     * @param string $type The type that the field belongs to
     * @param array|IValueManipulator $extra Additional functionality to be done on the field before being inserted into the Playerlync system.
     * @return $this
     */
    public function mapField($apiField, $alias, $type = Field::VARIABLE, $extra = [])
    {
        $this->fields[] = new ImportField($apiField, (string)$alias, $type, $extra);
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
    }

    /**
     * Verify the data provided by the builder functions and create all objects needed for the import
     * @return PlayerlyncImport
     * @throws BuilderException
     */
    protected function build()
    {
        try
        {
            $this->buildErrorLog('ImportLog');

            $options = $this->buildOptions();

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
            $this->source(APIConnector::DEFAULT_SOURCE);
            $api = $this->buildApi($this->errorLog);

            if($api->getPostService() === null)
            {
                throw new BuilderException('postService() method needs to provide a Playerlync API path to run import');
            }

            return $this->buildImporter($model, $reader, $api, $options);
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
        if($this->protocol !== null) //Get input file from server location
        {
            $fileName = pathinfo($this->inputFile,PATHINFO_BASENAME);
            try
            {
                $this->protocol->connect();
                $this->protocol->get($this->inputFile, $fileName);
                $this->protocol->close();
            }
            catch(ClientException $e)
            {
                $this->addError($e->getMessage());
                throw new BuilderException($e->getMessage());
            }
            return $fileName;
        }

        if(!file_exists($this->inputFile)) //Verify file exists locally
        {
            throw new BuilderException('"'.$this->inputFile.'" input file does not exist locally');
        }
        return $this->inputFile;
    }

    /**
     * Verify and re-organize the field data for the model
     * @return array
     * @throws BuilderException
     */
    protected function buildFields()
    {
        $fields = [];

        foreach($this->fields as $fieldInfo)
        {
            if(array_key_exists($fieldInfo->getField(), $fields))
            {
                throw new BuilderException('Attempting to add duplicate field: '. $fieldInfo->getField());
            }
            $fields[$fieldInfo->getField()] = $fieldInfo;
        }
        $this->fields = [];
        return $fields;
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
     * @return PlayerlyncImport
     */
    protected function buildImporter($model, $reader, $api, $options)
    {
        return new PlayerlyncImport($api, $reader, $model, $options);
    }
}