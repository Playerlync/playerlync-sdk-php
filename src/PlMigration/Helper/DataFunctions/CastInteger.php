<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-01-22
 */

namespace PlMigration\Helper\DataFunctions;

/**
 * Class CastInteger
 * @package PlMigration\Helper\DataFunctions
 */
class CastInteger implements IValueManipulator
{
    /**
     * Remove leading zeroes and decimals in numbers
     * ie. 00001234.23333 => 1234
     * @param string $value
     * @return string
     */
    public function execute($value)
    {
        if(is_numeric($value))
        {
            return (string)(int)$value;
        }
        return $value;
    }
}