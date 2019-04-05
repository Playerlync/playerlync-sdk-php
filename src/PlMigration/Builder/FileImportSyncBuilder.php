<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 11/14/18
 */

namespace PlMigration\Builder;

use PlMigration\Connectors\APIv3Connector;
use PlMigration\Exceptions\BuilderException;
use PlMigration\Exceptions\ImportException;
use PlMigration\Model\ImportModel;
use PlMigration\PlayerlyncImportSync;
use PlMigration\Reader\CsvReader;

/**
 * Builder to configure and execute the synced import process. Once configurations are ready, execute the process with the import() function
 * @package PlMigration\Builder
 */
class FileImportSyncBuilder extends FileImportBuilder
{
    /**
     * Send in a closure that will serve as a rule that will take in an array argument (representing an API record)
     * and if it meets the desired conditions the closure returns true, it will not be deleted
     * @param \Closure $closure
     * @return $this
     */
    public function exemptedDeleteRecords(\Closure $closure)
    {
        $this->options['deleteExempt'] = $closure;
        return $this;
    }

    /**
     * Build importer
     *
     * @param ImportModel $model
     * @param CsvReader $reader
     * @param APIv3Connector $api
     * @param array $options
     * @return PlayerlyncImportSync
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