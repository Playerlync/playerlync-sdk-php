<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/5/18
 */

namespace PlMigration\Builder;

use PlMigration\Builder\Traits\ApiBuilderTrait;
use PlMigration\Builder\Traits\CsvBuilderTrait;
use PlMigration\Builder\Traits\ErrorLogBuilderTrait;
use PlMigration\Client\IClient;
use PlMigration\Exceptions\ClientException;
use PlMigration\Exceptions\ConnectorException;
use PlMigration\Exceptions\BuilderException;
use PlMigration\Exceptions\ExportException;
use PlMigration\Model\ExportModel;
use PlMigration\Model\Field;
use PlMigration\PlayerlyncExport;

/**
 * Class FileExportBuilder
 * Builder to the FileExport class
 * Provides clearly defined methods to setup an export process
 * @package PlMigration\Builder
 */
class FileExportBuilder
{
    use ApiBuilderTrait;
    use CsvBuilderTrait;
    use ErrorLogBuilderTrait;

    /**
     * Full path to the output file created
     * @var string
     */
    private $outputFile;

    /**
     * File containing run history
     * @var string
     */
    private $historyFile;

    /**
     * Parsed Data of the history file
     * @var object
     */
    private $historyFileData;

    /**
     * Specific format to be used in the output file (ie. time)
     * @var array
     */
    private $format = [];

    /**
     * array holding additional settings that will be used by the export
     * @var array
     */
    private $options = [];

    /**
     * Protocol to use when sending the file to an outside server location
     * @var IClient
     */
    private $protocol;

    /**
     * String holding the directory to send the created data file
     * @var string
     */
    private $destination;

    /**
     * Fields to be retrieved from the data provider
     * @var Field[]
     */
    private $fields = [];

    /**
     * ExportBuilder constructor.
     */
    public function __construct()
    {
        $this->apiVersion('v3');
    }

    /**
     * Full path to where the created exported file should be located
     * @param $file
     * @return $this
     */
    public function outputFile($file)
    {
        $this->outputFile = $file;
        return $this;
    }

    /**
     * Set the date format to be output on the exported file
     * @param mixed ...$format
     * @return $this
     */
    public function timeFormat(...$format)
    {
        $this->format['time'] = implode('', $format);
        return $this;
    }

    /**
     * Toggle to enable the exported file to include a header row with the names of the associated data.
     * By default, it is not included.
     * @param bool $include
     * @return $this
     */
    public function includeHeaders($include)
    {
        $this->options['include_headers'] = $include;
        return $this;
    }

    /**
     * File that contains run history to prevent returning of previous runs.
     * @param $file
     * @return FileExportBuilder
     */
    public function runHistoryFile($file)
    {
        $this->historyFile = $file;
        return $this;
    }

    /**
     * Add a field to be added to the output file. The order of the output files is determined by the order of the function calls
     * @param $apiField
     * @param null $headerName
     * @param string $fieldType
     * @return $this
     */
    public function addField($apiField, $headerName = null, $fieldType = Field::VARIABLE)
    {
        $headerName = $headerName ?: $apiField;
        $this->fields[] = new Field($apiField, $headerName, $fieldType);
        return $this;
    }

    /**
     * Select a directory to send the exported file to via a selected protocol client (ftp, sftp, etc)
     * @param $destination
     * @param IClient $protocol
     * @return $this
     */
    public function sendTo($destination, IClient $protocol)
    {
        $this->protocol = $protocol;
        $this->destination = $destination;
        return $this;
    }

    /**
     * Function that will export the api data to an output file and then send the file to another server if configured
     * @return mixed
     * @throws BuilderException
     * @throws ExportException
     */
    public function export()
    {
        try
        {
            $output = $this->build()->export(); //export from the API into a local file destination
        }
        catch(BuilderException $e)
        {
            $this->addError($e->getMessage());
            throw $e;
        }
        catch (ExportException $e)
        {
            throw $e;
        }

        if($this->protocol)
        {
            try
            {
                $this->protocol->connect();
                $this->protocol->put($output, $this->destination);
                $this->protocol->close();
            }
            catch (ClientException $e)
            {
                $this->addError('Failed to send file: '.$e->getMessage());
                throw new BuilderException($e->getMessage());
            }
        }

        $this->saveRunTime();

        return $output;
    }

    /**
     * Validate data provided and build the export objects to start export process
     * @return PlayerlyncExport
     * @throws BuilderException
     */
    protected function build()
    {
        try
        {
            $this->buildErrorLog('ExportLog');
            $this->historyFileData = $this->verifyHistoryFile();
            $writer = $this->buildWriter($this->outputFile);
            $api = $this->buildApi($this->errorLog);
            $model = new ExportModel($this->buildFields($api->getStructure()), $this->format);
            if($api->getGetService() === null)
            {
                throw new BuilderException('getService() method needs to provide a playerlync API path to run export');
            }

            $model->setTimeFields($api->getTimeFields());
            $this->addLastRunTimeFilter($this->historyFileData, pathinfo($this->outputFile,PATHINFO_BASENAME), $api->getStructure());
            $api->setQueryParams($this->queryParams);

            $this->options['logger'] = $this->errorLog;

            return new PlayerlyncExport($api, $writer, $model, $this->options);
        }
        catch(ConnectorException $e)
        {
            throw new BuilderException($e->getMessage());
        }
    }

    /**
     * Verify the history file has been created correctly
     * @throws BuilderException
     */
    private function verifyHistoryFile()
    {
        $fileData = null;
        if($this->historyFile !== null)
        {
            if(!file_exists($this->historyFile) && !touch($this->historyFile))
            {
                throw new BuilderException('Unable to create history file in '.$this->historyFile);
            }
            $fileData = \json_decode(file_get_contents($this->historyFile));
        }

        return $fileData !== null ? $fileData : new \stdClass();
    }

    /**
     * Validate fields the are added to the output file are valid
     * @param $structure
     * @return array
     * @throws BuilderException
     */
    private function buildFields($structure)
    {
        $structure = array_keys($structure);
        $fields = [];
        /** @var Field $field */
        foreach($this->fields as $field)
        {
            if($field->getField() === null && $field->getAlias() === null)
            {
                throw new BuilderException('Field and header cannot both be null');
            }
            if(array_key_exists($field->getAlias(), $fields))
            {
                throw new BuilderException('Attempted to insert duplicate header: '.$field->getAlias());
            }
            if($field->getType() !== Field::CONSTANT && !\in_array($field->getField(), $structure, true))
            {
                throw new BuilderException("Unknown field \"{$field->getField()}\" provided that is not returned in the service");
            }

            $fields[$field->getAlias()] = $field;
        }
        $this->fields = [];
        return $fields;
    }

    /**
     * Append the last run time query parameter to have GET service return a subset of records
     * @param $historyFile
     * @param $type
     * @param $structure
     */
    private function addLastRunTimeFilter($historyFile, $type, $structure)
    {
        if(isset($historyFile->$type))
        {
            if(!empty($this->queryParams['filter']))
            {
                $this->queryParams['filter'] .= ',';
            }
            if(array_key_exists('system_create_date', $structure))
            {
                $this->queryParams['filter'] .= 'system_create_date|gteq|'.$historyFile->$type;
            }
            else
            {
                $this->queryParams['filter'] .= 'create_date|gteq|'.$historyFile->$type;
            }
        }
        $historyFile->$type = time();
    }

    /**
     * Update the last execution time into the history configuration file
     * @throws BuilderException
     */
    private function saveRunTime()
    {
        if($this->historyFile && $this->historyFileData)
        {
            if(!file_put_contents($this->historyFile, \json_encode($this->historyFileData,JSON_PRETTY_PRINT)))
            {
                throw new BuilderException('Unable to save history file.');
            }
        }
    }
}