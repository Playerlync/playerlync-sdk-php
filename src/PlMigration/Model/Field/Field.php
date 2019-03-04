<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/9/18
 */

namespace PlMigration\Model\Field;

use PlMigration\Helper\DataFunctions\IValueManipulator;

/**
 * Class that creates Field objects to be used by the Models
 * @package PlMigration\Model
 */
abstract class Field
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
     * Field that is required by the data source to not be empty to be able to be imported/exported
     */
    const REQUIRED = 'required';

    /**
     * The type of field that it is. Refer to constants available
     * @var string
     */
    protected $type;

    /**
     * Name of the field object as it is recognized by the Playerlync system.
     */
    protected $field;

    /**
     * The alias to be recognized by another system for the field name
     */
    protected $alias;

    /**
     * Array of extra functions to allow data value manipulation for the Field used like type checking, value validation, etc.
     * @var IValueManipulator[]
     */
    protected $extra = [];

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

        if($alias instanceof IAlias)
        {
            $this->alias = $alias;
        }
        else
        {
            $this->alias = $this->buildAlias($type, $alias);
        }

        if(!is_array($extra))
        {
            $extra = [$extra];
        }

        foreach($extra as $extraFunctionality)
        {
            if($extraFunctionality instanceof IValueManipulator)
            {
                $this->extra[] = $extraFunctionality;
            }
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
     * Get field alias
     * @return IAlias
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

    /**
     * Add an extra manipulation function for the field
     * @param IValueManipulator $manipulator
     */
    public function addExtra(IValueManipulator $manipulator)
    {
        $this->extra[] = $manipulator;
    }

    /**
     * @param string $type
     * @param string $aliasString
     * @return IAlias
     */
    protected function buildAlias($type, $aliasString)
    {
        if($aliasString === null)
            return null;
        if($type === self::CONSTANT)
        {
            return new ConstantAlias($aliasString);
        }
        if(substr_count($aliasString, '%') > 1)
        {
            return new FormattedAlias($aliasString);
        }
        return new SimpleAlias($aliasString);
    }

    /**
     * @return array
     */
    public function getAliasFields()
    {
        return $this->getAlias()->getReferenceFields();
    }
}