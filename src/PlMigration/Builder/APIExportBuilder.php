<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/5/18
 */

namespace PlMigration\Builder;

use Keboola\Csv\Exception;
use PlMigration\Builder\Traits\ApiBuilderTrait;
use PlMigration\Builder\Traits\CsvBuilderTrait;
use PlMigration\Builder\Traits\ErrorLogBuilderTrait;
use PlMigration\Client\IClient;
use PlMigration\Connectors\APIConnector;
use PlMigration\Exceptions\ClientException;
use PlMigration\Exceptions\ConnectorException;
use PlMigration\Exceptions\BuilderException;
use PlMigration\Model\ExportModel;
use PlMigration\Model\Field;
use PlMigration\PlayerlyncExport;
use PlMigration\Writer\CsvWriter;

class APIExportBuilder
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
     * @var bool
     */
    private $options = [];

    /**
     *
     * @var IClient
     */
    private $protocol;

    /**
     *
     * @var string
     */
    private $destination;

    /**
     * ExportBuilder constructor.
     */
    public function __construct()
    {
        $this->apiVersion('v3');
    }

    /**
     * Full path of where the exported file should be located
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

    public function includeHeaders()
    {
        $this->options['include_headers'] = true;
        return $this;
    }

    /**
     * File that contains run history to prevent returning of previous runs.
     * @param $file
     * @return APIExportBuilder
     */
    public function runHistoryFile($file)
    {
        $this->historyFile = $file;
        return $this;
    }

    /**
     * Point to a file to log errors
     * @param $logFile
     * @return $this
     */
    public function errorLog($logFile)
    {
        $this->setupErrorLog($logFile, 'ExportLog');
        return $this;
    }

    /**
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

    public function sendTo($destination, IClient $protocol)
    {
        $this->protocol = $protocol;
        $this->destination = $destination;
        return $this;
    }

    /**
     * @return mixed
     * @throws BuilderException
     */
    public function export()
    {
        $exporter = $this->build();
        try
        {
            $output = $exporter->export();
        }
        catch (\Exception $e)
        {
            $this->writeError('Failed to send file: '.$e->getMessage());
            throw new BuilderException($e->getMessage());
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
                $this->writeError('Failed to send file: '.$e->getMessage());
                throw new BuilderException($e->getMessage());
            }
        }

        $this->saveRunTime();

        return $output;
    }

    /**
     * @return PlayerlyncExport
     * @throws BuilderException
     */
    protected function build()
    {
        $this->historyFileData = $this->verifyHistoryFile();
        $fields = $this->buildFields();
        $model = new ExportModel($fields, $this->format);

        try
        {
            $writer = new CsvWriter($this->outputFile, $this->delimiter, $this->enclosure);
        }
        catch (Exception $e)
        {
            $this->writeError($e->getMessage());
            throw new BuilderException($e->getMessage());
        }

        try
        {
            $api = new APIConnector($this->service, $this->queryParams, $this->hostSettings);
            $model->setTimeFields($api->getTimeFields());
            $this->addLastRunTimeFilter($this->historyFileData, pathinfo($this->outputFile,PATHINFO_BASENAME), $api->getStructure());
            $api->setQueryParams($this->queryParams);
        }
        catch (ConnectorException $e)
        {
            $this->writeError($e->getMessage());
            throw new BuilderException($e->getMessage());
        }

        $this->options['logger'] = $this->errorLog;

        return new PlayerlyncExport($api, $writer, $model, $this->options);
    }

    /**
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
     * @return array
     * @throws BuilderException
     */
    private function buildFields()
    {
        $fields = [];
        /** @var Field $field */
        foreach($this->fields as $field)
        {
            if($field->getField() === null && $field->getHeader() === null)
            {
                $this->writeError('Field and header cannot both be null');
                throw new BuilderException('Field and header cannot both be null');
            }
            if(array_key_exists($field->getHeader(), $fields))
            {
                $this->writeError('Attempted to insert duplicate header: '.$field->getHeader());
                throw new BuilderException('Attempted to insert duplicate header: '.$field->getHeader());
            }

            $fields[$field->getHeader()] = $field;
        }
        return $fields;
    }

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