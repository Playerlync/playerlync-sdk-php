<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/9/18
 */

namespace PlMigration\Model;

use PlMigration\Helper\DataFunctions\IValueManipulator;

class Field
{
    const CONSTANT = 'constant';
    const VARIABLE = 'default';
    const PRIMARY_KEY = 'primary_key';
    const SECONDARY_KEY = 'secondary_key';

    private $field;
    private $type;
    private $alias;
    /**
     * @var IValueManipulator[]
     */
    private $extra = [];

    public function __construct($field, $alias, $type = self::VARIABLE, $extra = [])
    {
        $this->field = $field;
        $this->type = $type;
        $this->alias = $alias;

        if(!is_array($extra))
        {
            $extra = [$extra];
        }

        foreach($extra as $extraFunctionality)
        {
            if($extraFunctionality instanceof IValueManipulator)
                $this->extra[] = $extraFunctionality;
        }
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

    /**
     * @return IValueManipulator[]
     */
    public function getExtra()
    {
        return $this->extra;
    }
}