<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-04-03
 * Time: 09:40
 */

namespace PlMigration\Builder;


use PlMigration\Builder\Traits\MappedImportBuilderTrait;
use PlMigration\Exceptions\BuilderException;
use PlMigration\Exceptions\ImportException;
use PlMigration\PlayerlyncMappedImportSync;

class FileMappedImportSyncBuilder extends FileImportSyncBuilder
{
    use MappedImportBuilderTrait;

    /**
     * @param \PlMigration\Model\ImportModel $model
     * @param \PlMigration\Reader\CsvReader $reader
     * @param \PlMigration\Connectors\APIv3Connector $api
     * @param array $options
     * @return \PlMigration\PlayerlyncImportSync|PlayerlyncMappedImportSync
     * @throws BuilderException
     */
    protected function buildImporter($model, $reader, $api, $options)
    {
        if($api->getGetService() === null)
        {
            throw new BuilderException('getService() method needs to provide a playerlync API path to be able to run sync import');
        }

        try
        {
            return new PlayerlyncMappedImportSync($api, $reader, $model, $options);
        } catch (ImportException $e)
        {
            throw new BuilderException($e->getMessage());
        }

    }
}