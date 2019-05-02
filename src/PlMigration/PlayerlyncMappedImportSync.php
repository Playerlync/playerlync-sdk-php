<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-04-03
 * Time: 08:59
 */

namespace PlMigration;

use PlMigration\Connectors\IConnector;
use PlMigration\Exceptions\ConnectorException;
use PlMigration\Helper\MappedImportTrait;
use PlMigration\Model\ImportModel;
use PlMigration\Reader\IReader;

class PlayerlyncMappedImportSync extends PlayerlyncImportSync
{
    use MappedImportTrait;

    /**
     * @param IConnector $connector
     * @param IReader $reader
     * @param ImportModel $model
     * @param array $options
     * @throws Exceptions\ImportException
     */
    public function __construct(IConnector $connector, IReader $reader, ImportModel $model, array $options = [])
    {
        parent::__construct($connector, $reader, $model, $options);

        $this->setMappedData($options);
    }

    public function import()
    {
        $this->getServerRecords();
        parent::import();
    }

    protected function deleteRecords($record, $force)
    {
        $this->deleteRecord($record);
    }

    protected function deleteRecord($record)
    {
        if($this->isDuplicate($record))
        {
            return;
        }

        if($this->isExemptFromDelete($record))
        {
            return;
        }

        try
        {
            $this->connector->deleteRecord($record);
            $this->deleted($record);
        }
        catch (ConnectorException $e)
        {
            $this->error('Failed to delete record: '.$e->getMessage(), $record);
        }
    }
}