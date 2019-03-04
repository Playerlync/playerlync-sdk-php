<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-02-25
 * Time: 11:46
 */

namespace PlMigration\Model\Field;

/**
 * Class ConstantAlias
 * @package PlMigration\Model
 */
class ConstantAlias implements IAlias
{
    private $value;

    /**
     * Alias constructor.
     * @param $constantValue
     */
    public function __construct($constantValue)
    {
        $this->value = $constantValue;
    }

    /**
     * Return the constant value
     * @param $record
     * @return string
     */
    public function getValue($record)
    {
        return $this->value;
    }

    /**
     * Returns an empty array since no fields are used to determine the value
     * @return array
     */
    public function getReferenceFields()
    {
        return [];
    }
}