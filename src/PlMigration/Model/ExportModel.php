<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/5/18
 */

namespace PlMigration\Model;

use PlMigration\Model\Field\ExportField;

class ExportModel
{
    /**
     * @var ExportField[]
     */
    private $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @param mixed $data
     * @return array
     */
    public function fillModel($data)
    {
        $output = [];
        foreach($this->fields as $field)
        {
            $value = $field->getAlias()->getValue($data);
            foreach($field->getExtra() as $extraAction)
            {
                $value = $extraAction->execute($value);
            }
            $output[$field->getField()] = $value;
        }

        return $output;
    }

    public function getHeaders()
    {
        return array_keys($this->fields);
    }
}