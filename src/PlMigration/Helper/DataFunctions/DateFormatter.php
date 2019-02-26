<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-02-25
 * Time: 16:10
 */

namespace PlMigration\Helper\DataFunctions;

/**
 * Class DateFormatter
 * @package PlMigration\Helper\DataFunctions
 */
class DateFormatter implements IValueManipulator
{
    private $format;

    /**
     * DateFormatter constructor.
     * @param string $format Date format to convert epoch values into
     */
    public function __construct($format)
    {
        $this->format = $format;
    }

    /**
     * Formats timestamp values into a desired date format
     * @param string $value
     * @return false|string
     */
    public function execute($value)
    {
        if(is_numeric($value))
            return date($this->format, $value);
        return $value;
    }
}