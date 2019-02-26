<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-02-25
 * Time: 11:16
 */

namespace PlMigration\Model\Field;

class Alias implements IAlias
{
    /**
     * @var array
     */
    protected $fields = [];

    /**
     * Alias constructor.
     * @param $field
     */
    public function __construct($field)
    {
        $this->fields[] = $field;
    }

    /**
     * Return the value that should be alias from the raw data provided
     * @param $record
     * @return string
     */
    public function getValue($record)
    {
        return $record[$this->fields[0]];
    }

    /**
     * Return the fields that are used by this alias
     * @return array
     */
    public function getReferenceFields()
    {
        return $this->fields;
    }
}