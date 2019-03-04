<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-02-27
 * Time: 08:52
 */

namespace PlMigration\Model\Field;


class ClosureAlias implements IAlias
{
    /**
     * @var \Closure
     */
    private $callable;

    /**
     * @var array
     */
    private $fields;

    /**
     * CallableAlias constructor.
     * @param \Closure $callable An anonymous function that takes in a data source array of key => values to use to return a value
     * @param array $fields An array that lists the data sources fields being used in this function. used for data validation.
     */
    public function __construct(\Closure $callable, array $fields)
    {
        $this->callable = $callable;
        $this->fields = $fields;
    }

    /**
     * Return the value that should be alias from the raw data provided
     * @param $record
     * @return string
     */
    public function getValue($record)
    {
        return $this->callable->__invoke($record);
    }

    /**
     * Return the field(s) that are used by this alias
     * @return array
     */
    public function getReferenceFields()
    {
        return $this->fields;
    }
}