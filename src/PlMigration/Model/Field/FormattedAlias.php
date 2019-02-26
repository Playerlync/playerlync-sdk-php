<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-02-25
 * Time: 10:43
 */

namespace PlMigration\Model\Field;

class FormattedAlias extends Alias
{
    /**
     * @var string Format string template
     */
    private $format;

    /**
     * takes a string that can have multiple fields surrounded by % to be able to get the fields to be used
     * Sample: %1% - %4% (Takes two fields, field 1 and field 4)
     * FieldMerge constructor.
     * @param string $formattedField
     */
    public function __construct($formattedField)
    {
        $this->format = $formattedField;
        $this->parseFields($formattedField);
    }

    /**
     * Takes in the raw source data and build the output
     * @param array $record
     * @return mixed|string
     */
    public function getValue($record)
    {
        $output = $this->format;
        foreach($this->fields as $field)
        {
            $output = str_replace("%$field%", $record[$field], $output);
        }
        return $output;
    }

    private function parseFields($format)
    {
        preg_match_all('/\%(.*?)\%/',$format, $results);

        if(isset($results[1]))
        {
            foreach($results[1] as $result)
            {
                if(!empty($result))
                    $this->fields[] = $result;
            }
        }
    }
}