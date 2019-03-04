<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-02-26
 * Time: 13:34
 */

namespace PlMigration\Builder;


use PlMigration\Helper\ImportInterface;
use PlMigration\PlayerlyncMappedImport;

class FileMappedImportBuilder extends FileImportBuilder
{
    public function __construct()
    {
        parent::__construct();
        $this->options['mapping'] = [];
    }

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

    public function addDataFieldMap($inputApiField, $compareApiField, $retrieveField)
    {
        $this->options['mapping'][$inputApiField] = [$compareApiField, $retrieveField];
        return $this;
    }
}