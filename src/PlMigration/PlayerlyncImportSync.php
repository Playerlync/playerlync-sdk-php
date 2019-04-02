<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 11/13/18
 */

namespace PlMigration;

use PlMigration\Connectors\IConnector;
use PlMigration\Exceptions\ClientException;
use PlMigration\Exceptions\ConnectorException;
use PlMigration\Exceptions\ImportException;
use PlMigration\Model\ImportModel;
use PlMigration\Reader\IReader;

/**
 * An importer to use when
 * Class PlayerlyncImportSynchronizer
 * @package PlMigration
 */
class PlayerlyncImportSync extends PlayerlyncImport
{
    /**
     * PlayerlyncImportSynchronizer constructor.
     *
     * @param IConnector $connector
     * @param IReader $reader
     * @param ImportModel $model
     * @param array $options
     * @throws ImportException
     */
    public function __construct(IConnector $connector, IReader $reader, ImportModel $model, array $options = [])
    {
        parent::__construct($connector, $reader, $model, $options);

        $this->counter = [
            'success' => 0,
            'failed' => 0,
            'deleted' => 0];

        if($model->getPrimaryKey() === null)
        {
            throw new ImportException('The primary key, and secondary key if applicable, are required to run the synchronized import');
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
                $this->insertRecords($record, $this->reader->valid());
            }
            else
            {
                $this->insertRecord($record);
            }
        }

        $this->debug('Imported '.$this->counter['success'].' records successfully.');
        $this->debug('Finished importing, moving on to syncing records');

        do
        {
            try
            {
                $records = $this->get($hasNext);
            }
            catch (ConnectorException $e)
            {
                $this->error($e->getMessage());
                break;
            }
            $recordCount = count($records);
            foreach($records as $index => $record)
            {
                $record = (array)$record;
                //If the record exists in the data that has been memoized by the import, we don't delete it
                if($this->isDuplicate($record))
                {
                    continue;
                }

                $force = ($recordCount === ($index + 1)) && !$hasNext;
                if($this->connector->supportBatch())
                {
                    $this->deleteRecords($record, $force);
                }
                else
                {
                    $this->deleteRecord($record);
                }
            }
        }
        while($hasNext);

        $this->sendActivityRecord($startTime, microtime(true));
    }

    /**
     * Send an activity record to track the changes occurred
     * @param $startTime
     * @param $endTime
     */
    public function sendActivityRecord($startTime, $endTime)
    {
        $message = "{$this->counter['success']} record(s) updated, {$this->counter['deleted']} record(s) deleted, {$this->counter['failed']} record(s) failed to update";
        $data = [
            'activity_start_date' => $startTime,
            'activity_end_date'   => $endTime,
            'activity_duration'   => $endTime - $startTime,
            'activity_type'       => 'data_import_sync',
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
     * @param bool $hasNext
     * @return array
     * @throws ConnectorException
     */
    protected function get(&$hasNext = false)
    {
        $response = $this->connector->getRecords(['source'=> 'sdk']);
        $hasNext = $this->connector->hasNext();
        return $response;
    }

    protected function deleteRecords($record, $force)
    {
        $record['delete_date'] = time(); //this will cause the records to be deleted
        $this->queue['deleted'][] = $record;

        if(!$force && count($this->queue['deleted']) < 50)
        {
            return null;
        }

        $responses = $this->connector->insertRecords($this->queue['deleted']);
        if($responses === null)
        {
            return;
        }

        foreach($responses as $index => $response)
        {
            if($response instanceof ClientException)
            {
                $this->error('Failed to delete record: '.$response->getMessage(), $this->queue['deleted'][$index]);
            }
            else
            {
                $this->deleted($this->queue['deleted'][$index]);
            }
            unset($this->queue['deleted'][$index]);
        }
    }

    protected function deleteRecord($record)
    {
        $record['delete_date'] = time(); //this will cause the record to be deleted
        try
        {
            $this->connector->insertRecord($record);
        }
        catch (ConnectorException $e)
        {
            $this->error('Failed to delete record: '.$e->getMessage(), $record);
        }
    }

    protected function deleted($record)
    {
        ++$this->counter['deleted'];
    }
}