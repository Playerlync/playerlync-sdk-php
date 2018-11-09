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

    private $primaryKey;

    private $secondaryKey;

    /**
     * ImportModel constructor.
     * @param array $fields
     */
    public function __construct(array $fields)
    {
        $this->apiFields = $fields;

        foreach($this->apiFields as $fieldInfo)
        {
            if($fieldInfo->getType() === Field::PRIMARY_KEY)
            {
                $this->primaryKey = $fieldInfo->getField();
            }

            if($fieldInfo->getType() === Field::SECONDARY_KEY)
            {
                $this->secondaryKey = $fieldInfo->getField();
            }
        }
    }

    public function setApiFields($record)
    {
        $row = [];
        foreach($this->apiFields as $field => $fieldInfo)
        {
            if($fieldInfo->getType() === Field::CONSTANT)
            {
                $row[$field] = $fieldInfo->getAlias();
            }
            else
            {
                $row[$field] = trim($record[$fieldInfo->getAlias()]);
            }
        }
        return $row;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getSecondaryKey()
    {
        return $this->secondaryKey;
    }

    public function getFields()
    {
        return $this->apiFields;
    }
}