<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-04-04
 * Time: 08:25
 */

namespace PlMigration\Builder\Traits;


trait MappedImportBuilderTrait
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
     * @return $this
     */
    public function updatePrimaryKey($dataSourceKey, $apiKey)
    {
        $this->options['key_map'] = [$apiKey, $dataSourceKey];
        return $this;
    }
}