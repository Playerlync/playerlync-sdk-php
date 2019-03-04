<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-02-26
 * Time: 10:11
 */

namespace PlMigration;

use PlMigration\Connectors\IConnector;
use PlMigration\Model\ImportModel;
use PlMigration\Reader\IReader;

/**
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
            $this->serverRecords = array_merge($this->serverRecords, $this->connector->getRecords(['disable_source'=> true]));
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
            return $row;

        foreach($this->mapServerData as $inputField => list($referenceField,$retrieveField))
        {
            if(!isset($row[$inputField]))
            {
                continue;
            }
            if(isset($this->serverDataMapMemo[$inputField][$row[$inputField]]))
            {
                $row[$inputField] = $this->serverDataMapMemo[$inputField][$row[$inputField]];
            }
            else
            {
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
        }
        return $row;
    }
}