<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/5/18
 */

namespace PlMigration;

use Closure;
use PlMigration\Connectors\IConnector;
use PlMigration\Exceptions\ConnectorException;
use PlMigration\Exceptions\ExportException;
use PlMigration\Helper\IRawDataCheck;
use PlMigration\Helper\TearDownAction;
use PlMigration\Model\ExportModel;
use PlMigration\Writer\IWriter;
use PlMigration\Helper\LoggerTrait;

class PlayerlyncExport
{
    use LoggerTrait;
    /**
     * connection object to be used
     * @var IConnector
     */
    private $api;

    /**
     * Record write handler to output file
     * @var IWriter
     */
    private $writer;

    /**
     * Template for the output data to be used
     * @var ExportModel
     */
    private $model;

    /**
     * @var array
     */
    protected $cache = [];

    /**
     * @var string
     */
    protected $primaryKey;

    /**
     * @var IRawDataCheck[]
     */
    protected $rawDataCheckActions = [];

    /**
     * @var TearDownAction[]
     */
    protected $tearDownActions = [];

    /**
     * Instantiate a new exporter.
     * @param IConnector $api
     * @param IWriter $writer
     * @param ExportModel $model
     * @param array $options
     */
    public function __construct(IConnector $api, IWriter $writer, ExportModel $model, array $options = [])
    {
        $this->api = $api;
        $this->writer = $writer;
        $this->model = $model;

        if(isset($options['include_headers']) && $options['include_headers'] === true && !$this->writer->isAppend())
        {
            $this->writer->writeRecord($model->getHeaders());
        }

        if(isset($options['logger']))
        {
            $this->setLogger($options['logger']);
        }

        if(isset($options['primaryKey']))
        {
            $this->primaryKey = $options['primaryKey'];
        }
    }

    /**
     * @throws ExportException
     */
    public function export()
    {
        try
        {
            do
            {
                $records = $this->get($hasNext);

                foreach($records as $record)
                {
                    if($this->check($record) && !$this->isDuplicate($record))
                    {
                        $this->writeRow((array)$record);
                    }
                }
            }
            while($hasNext);
        }
        catch(\Exception $e)
        {
            $this->error($e->getMessage());
            throw new ExportException($e->getMessage());
        }

        return $this->writer->getFile();
    }

    public function tearDown()
    {
        foreach($this->tearDownActions as $action)
        {
            $action->tearDown($this->logger);
        }
    }

    protected function check($data): bool
    {
        foreach($this->rawDataCheckActions as $action)
        {
            if($action->checkRawData($data, $this->logger) === false)
                return false;
        }
        return true;
    }

    /**
     * @param IRawDataCheck $action
     */
    public function addDataCheck($action)
    {
        $this->rawDataCheckActions[] = $action;
    }

    /**
     * @param TearDownAction $action
     */
    public function addTearDown($action)
    {
        $this->tearDownActions[] = $action;
    }

    /**
     * @param bool $hasNext
     * @return array
     * @throws ConnectorException
     */
    protected function get(&$hasNext = false)
    {
        $response = $this->api->getRecords();

        $hasNext = $this->api->hasNext();

        return $response;
    }

    /**
     * @param $record
     */
    protected function writeRow($record)
    {
        $row = $this->model->fillModel($record);

        $this->writer->writeRecord($row);

        if($this->primaryKey !== null)
            $this->cache[$record[$this->primaryKey]] = true;
    }

    /**
     * Determine whether the record is considered duplicate with the given primary key value
     * @param $apiRecord
     * @return bool
     */
    protected function isDuplicate($apiRecord)
    {
        if($this->primaryKey !== null)
        {
            if(array_key_exists($apiRecord->{$this->primaryKey}, $this->cache))
                return true;
        }
        return false;
    }
}
