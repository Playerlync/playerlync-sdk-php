<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/9/18
 */

namespace PlMigration\Model;

use PlMigration\Helper\DataFunctions\IValueManipulator;

/**
 * Class that creates Field objects to be used by the Models
 * @package PlMigration\Model
 */
class Field
{
    /**
     * Constant type for field.
     * A field of this type will always output the value $field property when extracting the information.
     * Useful for exporting data constants or adding constant data not provided in a data source.
     */
    const CONSTANT = 'constant';

    /**
     * Generic default field type.
     */
    const VARIABLE = 'default';

    /**
     * Field that is treated as the primary key from the data. Usable for memoization during imports.
     */
    const PRIMARY_KEY = 'primary_key';

    /**
     * Field that is treated as the secondary key from the data. Usable for memoization during imports.
     * Useless if the PRIMARY_KEY constant has not been used yet.
     */
    const SECONDARY_KEY = 'secondary_key';

    /**
     * Name of the field object as it is recognized by the playerlync system.
     * @var string
     */
    private $field;

    /**
     * The type of field that it is.
     * @var string
     */
    private $type;

    /**
     * The alias name to be recognized by another system outside of playerlync.
     * @var string
     */
    private $alias;

    /**
     * Array of extra functions to allow data value manipulation for the Field used like type checking, value validation, etc.
     * @var IValueManipulator[]
     */
    private $extra = [];

    /**
     * Create a field to be used by the model class for importing and exporting
     * @param string $field Name of the field object
     * @param string $alias Alias name or pointer from the outside data
     * @param string $type Type of field to be recognized
     * @param IValueManipulator[] $extra
     */
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

    /**
     * Get the field type
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the field name
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Get field alias name
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Get extra functionality of Field object
     * @return IValueManipulator[]
     */
    public function getExtra()
    {
        return $this->extra;
    }
}