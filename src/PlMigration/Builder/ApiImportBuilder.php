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
use PlMigration\Exceptions\BuilderException;
use PlMigration\Exceptions\ClientException;
use PlMigration\Exceptions\WriterException;
use PlMigration\Model\Field;
use PlMigration\Model\ImportModel;
use PlMigration\PlayerlyncImport;
use PlMigration\Writer\TransactionLogger;

class ApiImportBuilder
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
     *
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
     * Get file to use for importing. If the protocol is set, it will grab from the server specified (ftp, sftp, etc).
     * Otherwise it will try to find the file locally.
     *
     * @param $file
     * @param null $protocol
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
     * @return $this
     */
    public function hasHeaders()
    {
        $this->options['include_headers'] = true;
        return $this;
    }

    /**
     * Set the directory where the transaction files will be created.
     * This will also automatically enable the transaction log functionality.
     *
     * @param $transactionLogDir
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
     * field will be mapped the selected api field from the call
     *
     * @param string $apiField
     * @param string $type
     * @return $this
     */
    public function addField($apiField, $type = Field::VARIABLE)
    {
        $this->fields[] = new Field($apiField, count($this->fields), $type);
        //$this->mapField($apiField, count($this->fields), $type);
        return $this;
    }

    /**
     * Field will be mapped the selected alias. By default the alias represents a column number and not
     * @param $apiField
     * @param $alias
     * @param string $type
     * @return $this
     */
    public function mapField($apiField, $alias, $type = Field::VARIABLE)
    {
        $this->fields[] = new Field($apiField, $alias, $type);
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
                if($field->getType() !== Field::CONSTANT && !array_key_exists($field->getAlias(), $record))
                {
                    throw new BuilderException($field->getField().' is mapped to an invalid column number '.$field->getAlias());
                }
            }
            $api = $this->buildApi();

            return new PlayerlyncImport($api, $reader, $model, $options);
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
    private function getInputFile()
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
    private function buildFields()
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
    private function buildOptions()
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
}