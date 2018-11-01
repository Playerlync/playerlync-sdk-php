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

    private $options = [];

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
     * Get file to use for importing. If the protocol is set, it will grab from the server specified (ftp, http, etc).
     * Otherwise it will try to retrieve the file locally.
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
     * @return $this
     */
    public function hasHeaders()
    {
        $this->options['include_headers'] = true;
        return $this;
    }

    public function transactionLog($transactionLogDir)
    {
        if($transactionLogDir === '')
            $transactionLogDir = '.';

        $this->transactionLogDir = $transactionLogDir;
        return $this;
    }

    /**
     * @param string $apiField
     * @param string $type
     * @return $this
     */
    public function addField($apiField, $type = Field::VARIABLE)
    {
        $this->mapField($apiField, count($this->fields), $type);
        return $this;
    }

    /**
     * @param $apiField
     * @param $columnNumber
     * @param string $type
     * @return $this
     */
    public function mapField($apiField, $columnNumber, $type = Field::VARIABLE)
    {
        $this->fields[$columnNumber][] = new Field($apiField, $columnNumber, $type);
        return $this;
    }

    /**
     * @throws BuilderException
     */
    public function import()
    {
        $this->build()->import();
    }

    /**
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
     * Verify there are no duplicate fields
     * @return array
     * @throws BuilderException
     */
    private function buildFields()
    {
        $fields = [];

        foreach($this->fields as $columnIndex => $mappedFields)
        {
            /** @var Field $fieldInfo */
            foreach($mappedFields as $fieldInfo)
            {
                if(array_key_exists($fieldInfo->getField(), $fields))
                {
                    throw new BuilderException('Attempting to add duplicate field: '. $fieldInfo->getField());
                }
                $fields[$fieldInfo->getField()] = $fieldInfo;
            }
        }
        $this->fields = [];
        return $fields;
    }

    /**
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