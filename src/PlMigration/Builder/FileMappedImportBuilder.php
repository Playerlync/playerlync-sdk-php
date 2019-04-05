<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-02-26
 * Time: 13:34
 */

namespace PlMigration\Builder;


use PlMigration\Builder\Traits\MappedImportBuilderTrait;
use PlMigration\Helper\ImportInterface;
use PlMigration\PlayerlyncMappedImport;

class FileMappedImportBuilder extends FileImportBuilder
{
    use MappedImportBuilderTrait;

    /**
     * Build PlayerlyncImport object
     * @param $model
     * @param $reader
     * @param $api
     * @param $options
     * @return ImportInterface
     */
    protected function buildImporter($model, $reader, $api, $options)
    {
        return new PlayerlyncMappedImport($api, $reader, $model, $options);
    }
}