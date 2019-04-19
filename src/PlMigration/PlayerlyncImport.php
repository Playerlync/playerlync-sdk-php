<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/22/18
 */

namespace PlMigration;

use Monolog\Logger;
use PlMigration\Builder\Helper\ImportBuilder;
use PlMigration\Connectors\IConnector;
use PlMigration\Exceptions\ClientException;
use PlMigration\Exceptions\ConnectorException;
use PlMigration\Helper\ImportInterface;
use PlMigration\Helper\LoggerTrait;
use PlMigration\Model\ImportModel;
use PlMigration\Reader\IReader;
use PlMigration\Writer\TransactionLogger;

class PlayerlyncImport implements ImportInterface
{
    use LoggerTrait;

    /**
     * Connection protocol to the server
     * @var IConnector
     */
    protected $connector;

    /**
     * Model holding template for mapping information into corresponding fields
     * @var ImportModel
     */
    protected $model;

    /**
     * Data file reader
     * @var IReader
     */
    protected $reader;

    /**
     * Transaction logger that will separate successful & failed records from the data file that couldn't make it into
     * the server
     * @var TransactionLogger
     */
    protected $transactionLogger;

    /**
     * Counter of successful and failed records during the import process
     * @var array
     */
    protected $counter;

    /**
     * Array that holds the request data to be sent in batch.
     *
     * @var array
     */
    protected $queue = [];

    /**
     * Array holding data of unique records already inserted
     *
     * @var array
     */
    protected $cache = [];

    /**
     * The descriptor of where the records come from. Used to validate that records come from a specific source.
     * Can also be used to separate between different import source types when deleting in sync imports.
     * (ie. differentiating between different types of org imports to not touch all organizations)
     * Default value: sdk
     * @var string
     */
    protected $source = ImportBuilder::DEFAULT_SOURCE;

    /**
     * Instantiate a new importer.
     * @param IConnector $connector
     * @param IReader $reader
     * @param ImportModel $model
     * @param array $options
     */
    public function __construct(IConnector $connector, IReader $reader, ImportModel $model, array $options = [])
    {
        $this->connector = $connector;
        $this->reader = $reader;
        $this->model = $model;
        $this->counter = ['success' => 0,
            'failed' => 0];

        if(isset($options['transaction_log']))
        {
            $this->transactionLogger = $options['transaction_log'];
        }
        if(isset($options['logger']))
        {
            $this->setLogger($options['logger']);
        }

        if(isset($options['source']))
        {
            $this->source = $options['source'];
        }

        if(array_key_exists('include_headers', $options) && $options['include_headers'] === true)
        {
            $this->reader->next(); //Skip the first row of the data as it contains the header names
        }
    }

    public function import()
    {
        $this->debug('Importing '.$this->reader.' into playerlync API');
        $startTime = microtime(true);
        while($this->reader->valid())
        {
            $record = $this->getRecord();
            if($this->connector->supportBatch())
            {
                $this->insertRecords($record, !$this->reader->valid());
            }
            else
            {
                $this->insertRecord($record);
            }
        }

        $this->sendActivityRecord($startTime, microtime(true));
    }

    /**
     * @param $record
     */
    protected function insertRecord($record)
    {
        $row = $this->fillModelWithData($record);
        try
        {
            if(!$this->isAllowedToInsert($row, $record))
            {
                return;
            }
            $this->connector->insertRecord($row);
            $this->success($record, $row);
        }
        catch(ConnectorException $e)
        {
            $this->failure($record, $e->getMessage(), $row);
        }
    }

    protected function insertRecords($record, $force = false)
    {
        $row = $this->fillModelWithData($record);

        if(!$this->isAllowedToInsert($row, $record))
        {
            return;
        }

        $this->queue['raw'][] = $record;
        $this->queue['formatted'][] = $row;

        if(!$force && count($this->queue['formatted']) < 50)
        {
            return null;
        }

        $responses = $this->connector->insertRecords($this->queue['formatted']);

        foreach($responses as $index => $response)
        {
            if($response instanceof ClientException)
            {
                $this->failure($this->queue['raw'][$index], $response->getMessage(), $this->queue['formatted'][$index]);
            }
            else
            {
                $this->success($this->queue['raw'][$index], $this->queue['formatted'][$index]);
            }
            unset($this->queue['raw'][$index], $this->queue['formatted'][$index]);
        }
    }

    /**
     * Setup the data to be taken to the API
     * @param array $record
     * @return array
     */
    protected function fillModelWithData($record)
    {
        try
        {
            $row = $this->model->fillModel($record);
        } catch (Exceptions\ModelException $e)
        {
            $this->failure($record, $e->getMessage(), $record);
            return [];
        }
        $row['source'] = $this->source;
        $row['sync_date'] = time();
        return $row;
    }

    protected function failure($record, $errorMessage, array $context = [], $logLevel = Logger::ERROR)
    {
        ++$this->counter['failed'];
        if($logLevel === Logger::ERROR)
            $this->error('Failed to import record: '.$errorMessage, $context);
        elseif($logLevel === Logger::NOTICE)
            $this->notice('Failed to import record: '.$errorMessage, $context);
        elseif($logLevel === Logger::WARNING)
            $this->warning('Failed to import record: '.$errorMessage, $context);
        if($this->transactionLogger !== null)
        {
            $record[] = $errorMessage;
            $this->transactionLogger->addFailure($record);
        }
    }

    protected function success($rawRecord, $apiRecord)
    {
        ++$this->counter['success'];
        $this->addToMemo($apiRecord);
        $this->debug('Successful insert', $apiRecord);
        if($this->transactionLogger !== null)
        {
            $this->transactionLogger->addSuccess($rawRecord);
        }
    }

    public function getRecord()
    {
        $record = $this->reader->getRecord();
        $this->reader->next();
        return $record;
    }

    /**
     * Send an activity record to track the changes occurred
     * @param $startTime
     * @param $endTime
     */
    protected function sendActivityRecord($startTime, $endTime)
    {
        $message = "{$this->counter['success']} records updated, {$this->counter['failed']} records failed";
        $this->debug('Import took '.($endTime - $startTime) . ' seconds.');
        $this->debug('Summary: '.$message);
        $data = [
            'activity_start_date' => $startTime,
            'activity_end_date'   => $endTime,
            'activity_duration'   => $endTime - $startTime,
            'activity_type'       => 'data_import',
            'activity_sub_type'   => 'sdk tool',
            'internal'            => 1,
            'activity_info'       => $message
        ];
        try
        {
            $this->connector->insertActivityRecord($data);
        }
        catch (ConnectorException $e)
        {
            $this->error('Failed to insert activity. '.$e->getMessage());
        }
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function isDuplicate($data)
    {
        if($this->model->getPrimaryKey() !== null)
        {
            if($this->model->getSecondaryKey() !== null)
            {
                return array_key_exists($data[$this->model->getPrimaryKey()].','.$data[$this->model->getSecondaryKey()], $this->cache);
            }
            return array_key_exists($data[$this->model->getPrimaryKey()], $this->cache);
        }
        return false;
    }

    protected function addToMemo($data)
    {
        if($this->model->getPrimaryKey() !== null)
        {
            if($this->model->getSecondaryKey() !== null)
            {
                $this->cache[$data[$this->model->getPrimaryKey()].','.$data[$this->model->getSecondaryKey()]] = true;
            }
            else
            {
                $this->cache[$data[$this->model->getPrimaryKey()]] = true;
            }
        }
    }

    /**
     * @param $apiData
     * @param $rawRecord
     * @return bool
     */
    protected function isAllowedToInsert(array $apiData, array $rawRecord)
    {
        if(empty($apiData))
        {
            return false;
        }

        if($this->isDuplicate($apiData))
        {
            $this->failure($rawRecord, 'Prevented to insert duplicate record based on the primary key', $apiData, Logger::NOTICE);
            return false;
        }

        return true;
    }
}