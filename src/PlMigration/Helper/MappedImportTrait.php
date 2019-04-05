<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-04-03
 * Time: 09:00
 */

namespace PlMigration\Helper;

use PlMigration\Exceptions\ConnectorException;


trait MappedImportTrait
{
    /**
     * @var array
     */
    private $serverRecords = [];

    /**
     * @var array
     */
    private $serverDataMapMemo = [];

    protected $mapServerData;

    protected $primaryKey;

    protected $mapKey;

    protected function setMappedData($options)
    {
        if(isset($options['mapping']))
        {
            $this->mapServerData = $options['mapping'];
        }
        if(isset($options['key_map']) && !empty($options['key_map']))
        {
            list($this->primaryKey,$this->mapKey) = $options['key_map'];
            $this->connector->setOptions(['primary_key'=> $this->primaryKey]);
        }
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

    /**
     * Retrieve all records that reside in the database to create a data map
     */
    protected function getServerRecords()
    {
        do {
            $serverRecords = $this->connector->getRecords([]);
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
        $this->connector->setOptions(['page'=> 1]);
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
}