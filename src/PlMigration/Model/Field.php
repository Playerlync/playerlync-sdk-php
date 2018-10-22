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
    private $header;

    public function __construct($field, $header, $type = self::VARIABLE)
    {
        $this->field = $field;
        $this->type = $type;
        $this->header = $header;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getHeader()
    {
        return $this->header;
    }
}