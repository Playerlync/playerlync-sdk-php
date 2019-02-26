<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-02-25
 * Time: 11:13
 */

namespace PlMigration\Model\Field;


interface IAlias
{
    /**
     * Return the value that should be alias from the raw data provided
     * @param $record
     * @return string
     */
    public function getValue($record);

    /**
     * Return the field(s) that are used by this alias
     * @return array
     */
    public function getReferenceFields();
}