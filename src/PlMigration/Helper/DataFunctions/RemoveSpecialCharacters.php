<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2018-12-04
 */

namespace PlMigration\Helper\DataFunctions;


class RemoveSpecialCharacters implements IValueManipulator
{
    public function execute($value)
    {
        return preg_replace('/[:\*\?"%<>#|\/\\\\]/', '~~', $value);
    }
}