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
    const PRIMARY_KEY = 'primary_key';
    const SECONDARY_KEY = 'secondary_key';

    private $field;
    private $type;
    private $alias;

    public function __construct($field, $alias, $type = self::VARIABLE)
    {
        $this->field = $field;
        $this->type = $type;
        $this->alias = $alias;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getAlias()
    {
        return $this->alias;
    }
}