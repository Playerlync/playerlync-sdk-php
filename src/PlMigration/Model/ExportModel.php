<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/5/18
 */

namespace PlMigration\Model;

class ExportModel
{
    private $fields = [];

    private $specialFields = [
        'constant' => [],
        'time' => []
    ];

    private $formats = [
        'time'=> null
    ];

    public function __construct(array $fields, $formats)
    {
        /** @var Field $field */
        foreach($fields as $header => $field)
        {
            $this->parseField($header,$field);
        }

        $this->formats = array_merge($this->formats, $formats);
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    public function fillModel($data)
    {
        $model = $this->fields;

        foreach($model as $index => $mapValue)
        {
            if(array_key_exists($index, $this->specialFields['constant']))
            {
                $model[$index] = $this->specialFields['constant'][$index];
                continue;
            }
            if($mapValue === null)
            {
                $model[$index] = null;
                continue;
            }

            if(array_key_exists($mapValue, $data))
            {
                $model[$index] = $this->getFormattedValue($mapValue, $data[$mapValue]);
            }
        }

        return $model;
    }

    public function getHeaders()
    {
        return array_keys($this->fields);
    }

    /**
     * @param $header
     * @param Field $field
     */
    private function parseField($header, Field $field)
    {
        $value = $field->getField();
        if($field->getType() === Field::CONSTANT)
        {
            $value = null;
            $this->specialFields['constant'][$header] = $field->getField();
        }
        $this->fields[$header] = $value;
    }

    private function getFormattedValue($field, $value)
    {
        if(\in_array($field, $this->specialFields['time'],true))
        {
            $value = Formatter::formatTime($value, $this->formats['time']);
        }

        return $value;
    }

    public function setTimeFields($fields)
    {
        $this->specialFields['time'] = $fields;
    }
}