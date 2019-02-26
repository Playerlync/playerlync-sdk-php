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
use PlMigration\Helper\DataFunctions\DateFormatter;
use PlMigration\Model\ExportModel;
use PlMigration\Model\Field\ExportField;
use PlMigration\Model\Field\Field;
use PlMigration\PlayerlyncExport;

/**
 * Builder to configure and execute the export process. Once configurations are ready, execute the process with the export() function
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
     * Specific date format to be used in the output file (ie. time)
     * @var DateFormatter
     */
    private $dateFormat;

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
     * @var ExportField[]
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
     * Set the full path to where the created exported file will be located.
     * If the directory does not exist, it will NOT be created and return an error
     * @param string $file
     * @return $this
     */
    public function outputFile($file)
    {
        $this->outputFile = $file;
        return $this;
    }

    /**
     * Set the date format to be output on the exported file.
     * The date can be created by using the constants in TimeFormat.php
     * @param string ...$format
     * @return $this
     */
    public function timeFormat(...$format)
    {
        $this->dateFormat = new DateFormatter(implode('', $format));
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
     * File that configures history file to prevent returning of previous runs.
     * @param string $file Filepath of the history file, including the file name
     * @return FileExportBuilder
     */
    public function runHistoryFile($file)
    {
        $this->historyFile = $file;
        return $this;
    }

    /**
     * Add a field to be added to the output.
     * The order of the output fields is determined by the sequence of the function calls
     * @param string $apiAlias The field from the playerlync API
     * @param string|null $outputName The output name to be used for the output (such as the header name)
     * @param string $fieldType The type of field that is being created. Refer to Field.php constants for types available. Default is Field::VARIABLE
     * @return $this
     */
    public function addField($apiAlias, $outputName = null, $fieldType = Field::VARIABLE)
    {
        $outputName = $outputName ?: $apiAlias;
        $this->fields[] = new ExportField($outputName, $apiAlias, $fieldType);
        return $this;
    }

    /**
     * Select a directory to send the exported file to via a selected protocol client (ftp, sftp, etc)
     * @param string $destination File path of the remote server location to send file
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
            $model = new ExportModel($this->buildFields($api->getStructure(), $api->getTimeFields()));
            if($api->getGetService() === null)
            {
                throw new BuilderException('getService() method needs to provide a playerlync API path to run export');
            }

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
     * @param array $structure
     * @param array $timeFields
     * @return array
     * @throws BuilderException
     */
    private function buildFields($structure, $timeFields)
    {
        $structure = array_keys($structure);
        $fields = [];
        /** @var ExportField $field */
        foreach($this->fields as $field)
        {
            if($field->getField() === null && $field->getAlias() === null)
            {
                throw new BuilderException('Field and header cannot both be null');
            }
            if(array_key_exists($field->getField(), $fields))
            {
                throw new BuilderException('Attempted to insert duplicate header: '.$field->getField());
            }
            if($field->getType() !== Field::CONSTANT)
            {
                foreach($field->getAliasFields() as $refField)
                {
                    if(!\in_array($refField, $structure, true))
                    {
                        throw new BuilderException("Unknown field \"{$refField}\" provided that is not returned in the service");
                    }

                    if($this->dateFormat !== null && in_array($refField, $timeFields))
                        $field->addExtra($this->dateFormat);
                }
            }

            $fields[$field->getField()] = $field;
        }
        $this->fields = [];
        return $fields;
    }

    /**
     * Append the last run time query parameter to have GET service return a subset of records
     * @param object $historyFile
     * @param string $type
     * @param array $structure
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