<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2018-12-04
 */

namespace PlMigration\Helper\DataFunctions;

/**
 * Class IValueManipulator
 * @package PlMigration\Helper\DataFunctions
 */
interface IValueManipulator
{
    public function execute($value);
}