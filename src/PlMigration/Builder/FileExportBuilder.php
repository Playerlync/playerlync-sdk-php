<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/5/18
 */

namespace PlMigration\Builder;

use Closure;
use PlMigration\Builder\Helper\ExportBuilder;
use PlMigration\Builder\Traits\ApiBuilderTrait;
use PlMigration\Builder\Traits\CsvBuilderTrait;
use PlMigration\Client\RemoteClient;
use PlMigration\Exceptions\ClientException;
use PlMigration\Exceptions\ConnectorException;
use PlMigration\Exceptions\BuilderException;
use PlMigration\Exceptions\ExportException;
use PlMigration\Helper\DataFunctions\DateFormatter;
use PlMigration\Helper\ISyncDataUpdate;
use PlMigration\Helper\Notifications\Attachable;
use PlMigration\Helper\RawDataValidate;
use PlMigration\Model\ExportModel;
use PlMigration\Model\Field\ExportField;
use PlMigration\PlayerlyncExport;

/**
 * Builder to configure and execute the export process. Once configurations are ready, execute the process with the export() function
 * @package PlMigration\Builder
 */
class FileExportBuilder extends ExportBuilder
{
    use ApiBuilderTrait;
    use CsvBuilderTrait;

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
     * fs
     * @var string
     */
    private $historyFileDateAppend;

    /**
     * Whether or not the history file
     * @var bool
     */
    private $updateHistoryFile = true;

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
     * @var RemoteClient
     */
    private $protocol;

    /**
     * String holding the directory to send the created data file
     * @var string
     */
    private $destination;

    /**
     * @var ISyncDataUpdate
     */
    private $syncDateService;

    /**
     * @var RawDataValidate
     */
    private $recordValidator;

    /**
     * ExportBuilder constructor.
     */
    public function __construct()
    {
        $this->resetData();
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
     * @param string|\Closure $dateAppend The API field that will be used to connect the time offset from the history file. By default, a create_date between filter will be created
     * @return FileExportBuilder
     */
    public function runHistoryFile($file, $dateAppend = null)
    {
        $this->historyFile = $file;
        if(!empty($dateAppend))
        {
            if(is_string($dateAppend))
            {
                $this->historyFileDateAppend = function ($time) use ($dateAppend) {
                    return $dateAppend.'|between|'.$time.'|'.time();
                };
            }
            else
            {
                $this->historyFileDateAppend = $dateAppend;
            }
        }
        else
        {
            $this->historyFileDateAppend = function ($time) {
                return 'create_date|between|'.$time.'|'.time();
            };
        }
        return $this;
    }

    /**
     * Toggle to decide if the history file should be updated when the export process is finished
     * Default value: true
     * This value will be reset to the default value when the export method is executed
     * @param bool $update
     * @return $this
     */
    public function updateHistoryFileAfterProcess($update)
    {
        $this->updateHistoryFile = $update;
        return $this;
    }

    /**
     * Add a primary key to prevent duplicate API records to be inserted by the API field key set
     * @param $primaryKey
     * @return $this
     */
    public function primaryKey($primaryKey)
    {
        $this->options['primaryKey'] = $primaryKey;
        return $this;
    }

    /**
     * Add a validation step for each record before going into the output format to validate that we want to use it
     * @param Closure $func Closure function that will take an array argument that will contain the raw api data to validate.
     * the function will return true if we want to continue with the export process on the record, or false to skip.
     * @return $this
     */
    public function inputValidator(Closure $func)
    {
        $this->recordValidator = new RawDataValidate($func);
        return $this;
    }

    /**
     * Select a directory to send the exported file to via a selected protocol client (ftp, sftp, etc)
     * @param string $destination File path of the remote server location to send file
     * @param RemoteClient $protocol
     * @return $this
     */
    public function sendTo($destination, RemoteClient $protocol)
    {
        $this->protocol = $protocol;
        $this->destination = $destination;
        return $this;
    }

    /**
     * @param ISyncDataUpdate $syncService
     * @return $this
     */
    public function syncDatesToServer($syncService)
    {
        $this->syncDateService = $syncService;
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
            $exporter = $this->build();
            $exporter->export(); //export from the API into a local file destination
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
                $this->protocol->upload($this->outputFile, $this->destination);
                $this->protocol->close();
            }
            catch (ClientException $e)
            {
                $this->addWarning('Failed to send file: '.$e->getMessage());
            }
        }

        if($this->updateHistoryFile)
            $this->saveRunTime();

        $this->errorLog->close();
        if($this->notificationManager !== null)
        {
            $this->sendNotifications();
        }
        $exporter->tearDown();
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
            if($this->errorLog !== null)
                $this->addAttachment($this->errorLog->getHandlers()[0]->getUrl(), Attachable::LOG_FILE);
            $this->historyFileData = $this->verifyHistoryFile();
            $writer = $this->buildWriter($this->outputFile);
            $this->addAttachment($writer->getFile(), Attachable::OUTPUT_FILE);
            if($this->getService === null)
            {
                throw new BuilderException('getService() method needed to provide a playerlync API path to run export');
            }
            $api = $this->buildApi($this->errorLog);
            $model = new ExportModel($this->buildExportFields($api->getStructure(), $api->getTimeFields()));
            $this->addLastRunTimeFilter($this->historyFileData, pathinfo($this->outputFile,PATHINFO_BASENAME));
            $api->setQueryParams($this->queryParams);

            $this->options['logger'] = $this->errorLog;
            if($this->syncDateService !== null)
            {
                $this->syncDateService->setClient($api->getClient());
            }

            $exporter = new PlayerlyncExport($api, $writer, $model, $this->options);
            $this->addExportActions($exporter);
            $this->resetData();
            return $exporter;
        }
        catch(ConnectorException $e)
        {
            $this->resetData();
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
            $fileData = json_decode(file_get_contents($this->historyFile));
        }

        return $fileData ?? new \stdClass();
    }

    /**
     * Validate fields the are added to the output file are valid
     * @param array $structure
     * @param array $timeFields
     * @return array
     * @throws BuilderException
     */
    private function buildExportFields($structure, $timeFields)
    {
        $structure = array_keys($structure);
        $fields = $this->buildFields();
        /** @var ExportField $field */
        foreach($fields as $field)
        {
            foreach($field->getAliasFields() as $refField)
            {
                if(!in_array($refField, $structure, true))
                {
                    throw new BuilderException("Unknown field \"{$refField}\" provided that is not returned in the service");
                }
                if($this->dateFormat !== null && in_array($refField, $timeFields))
                    $field->addExtra($this->dateFormat);
            }
        }
        return $fields;
    }

    /**
     * Append the last run time query parameter to have GET service return a subset of records
     * @param object $historyFile
     * @param string $type
     */
    private function addLastRunTimeFilter($historyFile, $type)
    {
        if(isset($historyFile->$type))
        {
            $additionalFilter = $this->historyFileDateAppend->__invoke($historyFile->$type);
            if(!empty($additionalFilter))
            {
                if(!empty($this->queryParams['filter']))
                    $this->queryParams['filter'] .= ',';
                $this->queryParams['filter'] .= $additionalFilter;
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
            if(!file_put_contents($this->historyFile, json_encode($this->historyFileData,JSON_PRETTY_PRINT)))
            {
                $this->addError('Unable to save history file');
                throw new BuilderException('Unable to save history file.');
            }
        }
    }

    /**
     * Add actions to manipulate raw data or tear down functions. Order of addition matters
     * @param PlayerlyncExport $exporter
     */
    protected function addExportActions($exporter)
    {
        if($this->syncDateService !== null)
        {
            $cloneService = clone $this->syncDateService;
            $exporter->addDataCheck($cloneService);
            $exporter->addTearDown($cloneService);
        }

        if($this->recordValidator !== null)
        {
            $exporter->addDataCheck(clone $this->recordValidator);
        }
    }

    protected function resetData()
    {
        parent::resetData();
        $this->recordValidator = null;
        $this->syncDateService = null;
        $this->updateHistoryFile = true;
        $this->historyFileDateAppend = null;
    }
}
