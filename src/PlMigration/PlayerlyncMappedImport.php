<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-02-26
 * Time: 10:11
 */

namespace PlMigration;

use PlMigration\Connectors\IConnector;
use PlMigration\Exceptions\ConnectorException;
use PlMigration\Model\ImportModel;
use PlMigration\Reader\IReader;

/**
 * Imported created for playerlync data types that can't be run with PlayerlyncImport class.
 * NOT RECOMMENDED FOR USE UNLESS NECESSARY
 * CONTAINS PERFORMANCE ISSUES ON BIG DATA SETS
 * Class PlayerlyncImportPostPut
 * @package PlMigration
 */
class PlayerlyncMappedImport extends PlayerlyncImport
{
    /**
     * @var array
     */
    private $serverRecords = [];

    /**
     * @var array
     */
    private $mapServerData = [];

    /**
     * @var array
     */
    private $serverDataMapMemo = [];

    private $primaryKey;

    private $mapKey;

    /**
     * PlayerlyncImportPostPut constructor.
     * @param IConnector $connector
     * @param IReader $reader
     * @param ImportModel $model
     * @param array $options
     */
    public function __construct(IConnector $connector, IReader $reader, ImportModel $model, array $options = [])
    {
        parent::__construct($connector, $reader, $model, $options);

        if(isset($options['mapping']))
        {
            $this->mapServerData = $options['mapping'];
        }
        if(isset($options['key_map']))
        {
            list($this->primaryKey,$this->mapKey) = $options['key_map'];
            $this->connector->setOptions(['primary_key'=> $this->primaryKey]);
        }
    }

    /**
     * Override to add grabbing server records of the importing type to be able to do data mapping
     */
    public function import()
    {
        $this->debug('Importing '.$this->reader.' into playerlync API');

        $this->getServerRecords();
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
     * Retrieve all records that reside in the database to create a data map
     */
    protected function getServerRecords()
    {
        do {
            $serverRecords = $this->connector->getRecords(['disable_source'=> true]);
            if($this->primaryKey !== null)
            {
                foreach($serverRecords as $record)
                {
                    $this->serverRecords[$record->{$this->mapKey}] = $record;
                }
            }
            else
            {
                $this->serverRecords = array_merge($this->serverRecords, $serverRecords);
            }

        }while($this->connector->hasNext());
    }

    /**
     * Override function to also handle the data mapping after the API request data is created.
     * @param array $record
     * @return array
     */
    protected function fillModelWithData($record)
    {
        $row = parent::fillModelWithData($record);

        if(empty($row))
        {
            return $row;
        }

        if($this->primaryKey !== null && array_key_exists($row[$this->mapKey], $this->serverRecords))
        {
            $row[$this->primaryKey] = $this->serverRecords[$row[$this->mapKey]]->{$this->primaryKey};
        }

        foreach($this->mapServerData as $inputField => list($referenceField,$retrieveField))
        {
            if(!isset($row[$inputField])) //If the data doesn't have the input field, skip mapping
            {
                continue;
            }

            if(isset($this->serverDataMapMemo[$inputField][$row[$inputField]])) //If the value exists in the memo map, grab it
            {
                $row[$inputField] = $this->serverDataMapMemo[$inputField][$row[$inputField]];
                continue;
            }

            foreach($this->serverRecords as $serverRecord)
            {
                if($serverRecord->$referenceField == $row[$inputField])
                {
                    $this->serverDataMapMemo[$inputField][$row[$inputField]] = $serverRecord->$retrieveField;
                    $row[$inputField] = $serverRecord->$retrieveField;
                    break;
                }
            }
        }
        return $row;
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
            if(isset($row[$this->primaryKey]))
            {
                $this->connector->updateRecord($row);
            }
            else
            {
                $this->connector->insertRecord($row);
            }

            $this->success($record, $row);
        }
        catch(ConnectorException $e)
        {
            $this->failure($record, $e->getMessage(), $row);
        }
    }
}