<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 11/14/18
 */

namespace PlMigration\Builder;

use PlMigration\Connectors\APIConnector;
use PlMigration\Exceptions\BuilderException;
use PlMigration\Exceptions\ImportException;
use PlMigration\PlayerlyncImportSync;

/**
 * Class FileImportSyncBuilder
 * Builder for the FileImportSync class
 * @package PlMigration\Builder
 */
class FileImportSyncBuilder extends FileImportBuilder
{
    /**
     * Build importer to have connection to a get service and a post service
     * @param $model
     * @param $reader
     * @param ApiConnector $api
     * @param $options
     * @return \PlMigration\PlayerlyncImport|PlayerlyncImportSync
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
            return new PlayerlyncImportSync($api, $reader, $model, $options);
        } catch (ImportException $e)
        {
            throw new BuilderException($e->getMessage());
        }
    }
}