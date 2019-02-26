<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2018-12-04
 */

namespace PlMigration\Helper\DataFunctions;

/**
 * Class RemoveSpecialCharacters
 * @package PlMigration\Helper\DataFunctions
 */
class RemoveSpecialCharacters implements IValueManipulator
{
    /**
     * Replace a list of characters that are not allowed with ~~ because some fields in the Playerlync API don't allow them
     * List of characters that are filtered: [ : \ / < > ? " % # | *
     * ie. member?name => member~~name
     * @param $value
     * @return string
     */
    public function execute($value)
    {
        return preg_replace('/[:\*\?"%<>#|\/\\\\]/', '~~', $value);
    }
}