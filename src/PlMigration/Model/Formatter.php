<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/9/18
 */

namespace PlMigration\Model;


class Formatter
{
    /**
     * @param float $value
     * @param $timeFormat
     * @return string
     */
    public static function formatTime($value, $timeFormat)
    {
        return date($timeFormat, $value);
    }
}