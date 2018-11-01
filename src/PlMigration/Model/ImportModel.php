<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/22/18
 */

namespace PlMigration\Model;


class ImportModel
{
    /** @var Field[] */
    private $apiFields;

    /**
     * ImportModel constructor.
     * @param array $fields
     */
    public function __construct(array $fields)
    {
        $this->apiFields = $fields;
    }

    public function setApiFields($record)
    {
        $row = [];
        foreach($this->apiFields as $field => $fieldInfo)
        {
            $row[$field] = $record[$fieldInfo->getAlias()];
        }
        return $row;
    }
}