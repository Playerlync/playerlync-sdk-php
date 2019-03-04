<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-02-26
 * Time: 15:50
 */

namespace PlMigration\Model\Field;

class IfElseAlias implements IAlias
{
    /**
     * @var IAlias
     */
    private $if;

    /**
     * @var IAlias
     */
    private $else;

    /**
     * @var array
     */
    private $fields;

    /**
     * Create an alias that will be used on a field to run an if else statement if there are 2 ways of handling a value.
     * Example:
     * data:
     * {
     *  "field1":"generic",
     *  "field2":"less generic"
     * }
     *
     * if field2 is set, return "less generic", else return "generic"
     *
     * IfExistsElseAlias constructor.
     * @param string|IAlias $ifFieldExists
     * @param string|IAlias $elseField
     */
    public function __construct($ifFieldExists, $elseField)
    {
        $this->if = $ifFieldExists instanceof IAlias ? $ifFieldExists : new SimpleAlias($ifFieldExists);
        $this->else = $elseField instanceof IAlias ? $elseField : new SimpleAlias($elseField);

        $this->fields = array_merge($this->if->getReferenceFields(), $this->else->getReferenceFields());
    }

    /**
     * Return the value that is appropriate
     * @param $record
     * @return string
     */
    public function getValue($record)
    {
        if(!empty($this->if->getValue($record)))
            return $this->if->getValue($record);
        return $this->else->getValue($record);
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