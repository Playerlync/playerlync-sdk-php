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
    /**
     * Not recommended for use without professional guidance
     * FileMappedImportBuilder constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->options['mapping'] = [];
        $this->options['key_map'] = [];
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

    /**
     * @param $inputApiField
     * @param $compareApiField
     * @param $retrieveField
     * @return $this
     */
    public function addDataFieldMap($inputApiField, $compareApiField, $retrieveField)
    {
        $this->options['mapping'][$inputApiField] = [$compareApiField, $retrieveField];
        return $this;
    }

    /**
     * @param $dataSourceKey
     * @param $apiKey
     */
    public function updatePrimaryKey($dataSourceKey, $apiKey)
    {
        $this->options['key_map'] = [$apiKey, $dataSourceKey];
        return $this;
    }
}