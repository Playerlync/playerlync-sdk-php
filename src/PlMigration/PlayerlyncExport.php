<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/5/18
 */

namespace PlMigration;

use Closure;
use PlMigration\Connectors\APIv3Connector;
use PlMigration\Connectors\IConnector;
use PlMigration\Exceptions\ClientException;
use PlMigration\Exceptions\ConnectorException;
use PlMigration\Exceptions\ExportException;
use PlMigration\Model\ExportModel;
use PlMigration\Service\ISyncService;
use PlMigration\Service\Plapi\PlapiSyncDateService;
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
     * @var array
     */
    protected $dataCache = [];

    /**
     * @var string
     */
    protected $primaryKey;

    /**
     * @var Closure[]
     */
    protected $recordValidator = [];

    protected $syncServiceCollector;

    /**
     * Instantiate a new exporter.
     * @param IConnector $api
     * @param IWriter $writer
     * @param ExportModel $model
     * @param array $options
     * @param ISyncService|null $syncServiceCollector
     */
    public function __construct(IConnector $api, IWriter $writer, ExportModel $model, array $options = [], ISyncService $syncServiceCollector = null)
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

        if(isset($options['recordValidator']) && is_callable($options['recordValidator']))
        {
            $this->recordValidator[] = $options['recordValidator'];
        }

        $this->syncServiceCollector = $syncServiceCollector;
        if($this->syncServiceCollector instanceof PlapiSyncDateService && $api instanceof APIv3Connector)
            $this->syncServiceCollector->setClient($api->getClient());
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
                    if($this->syncServiceCollector !== null)
                        $this->syncServiceCollector->addRecord($record);

                    if($this->isValid($record) && !$this->isDuplicate($record))
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

    /**
     * @param bool $hasNext
     * @return array
     * @throws ConnectorException
     */
    public function get(&$hasNext = false)
    {
        $response = $this->api->getRecords();

        $hasNext = $this->api->hasNext();

        return $response;
    }

    /**
     * @param $record
     */
    public function writeRow($record)
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

    protected function isValid($record): bool
    {
        foreach($this->recordValidator as $validation)
        {
            if(!$validation->__invoke($record, $this->logger))
                return false;
        }
        return true;
    }
}
