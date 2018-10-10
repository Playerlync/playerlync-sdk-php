<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/9/18
 */

namespace PlMigration\Model;


class Field
{
    const CONSTANT = 'constant';
    const VARIABLE = 'default';

    private $field;
    private $type;

    public function __construct($field, $type = self::VARIABLE)
    {
        $this->field = $field;
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getField()
    {
        return $this->field;
    }
}