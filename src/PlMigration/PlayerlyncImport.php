<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/22/18
 */

namespace PlMigration;

use PlMigration\Connectors\IConnector;
use PlMigration\Exceptions\ClientException;
use PlMigration\Exceptions\ConnectorException;
use PlMigration\Helper\LoggerTrait;
use PlMigration\Model\ImportModel;
use PlMigration\Reader\IReader;
use PlMigration\Writer\TransactionLogger;

class PlayerlyncImport
{
    use LoggerTrait;

    /**
     * Connection protocol to the server
     * @var IConnector
     */
    private $api;

    /**
     * Model holding template for mapping information into corresponding fields
     * @var ImportModel
     */
    private $model;

    /**
     * Data file reader
     * @var IReader
     */
    private $reader;

    /**
     * Transaction logger that will separate successful & failed records from the data file that couldn't make it into
     * the server
     * @var TransactionLogger
     */
    private $transactionLogger;

    /**
     * Counter of successful and failed records during the import process
     * @var array
     */
    private $counter;

    private $queue;
    /**
     * Instantiate a new exporter.
     * @param IConnector $api
     * @param IReader $reader
     * @param ImportModel $model
     * @param array $options
     */
    public function __construct(IConnector $api, IReader $reader, ImportModel $model, array $options = [])
    {
        $this->api = $api;
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
    }

    public function import()
    {
        $startTime = microtime(true);
        while($this->reader->valid())
        {
            $record = $this->getRecord();
            if($this->api->supportBatch())
            {
                $this->insertRecords($record, $this->reader->valid());
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
    public function insertRecord($record)
    {
        try
        {
            $row = $this->model->setApiFields($record);
            $this->api->insertRecord($row);
        }
        catch(ConnectorException $e)
        {
            $this->failure($record, $e->getMessage());
        }
        $this->success($record);
    }

    public function insertRecords($record, $moreExist = true)
    {
        $row = $this->model->setApiFields($record);
        $this->queue[] = $row;

        $responses = $this->api->insertRecords($this->queue, !$moreExist);
        if($responses === null)
            return;

        foreach($responses as $index => $response)
        {
            if($response instanceof ClientException)
            {
                $this->failure($this->queue[$index], $response->getMessage());
            }
            else
            {
                $this->success($this->queue[$index]);
            }
            unset($this->queue[$index]);
        }
    }

    private function failure($record, $errorMessage)
    {
        ++$this->counter['failed'];
        $this->error('Failed to import record: '.$errorMessage);
        if($this->transactionLogger !== null)
        {
            $record[] = $errorMessage;
            $this->transactionLogger->addFailure($record);
        }
    }

    private function success($record)
    {
        ++$this->counter['success'];
        if($this->transactionLogger !== null)
        {
            $this->transactionLogger->addSuccess($record);
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
    public function sendActivityRecord($startTime, $endTime)
    {
        $message = "{$this->counter['success']} records updated, {$this->counter['failed']} records failed";
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
            $response = $this->api->insertActivityRecord($data);
        }
        catch (ConnectorException $e)
        {
            $this->error('Failed to insert activity. '.$e->getMessage());
        }
    }
}