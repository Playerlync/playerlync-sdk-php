<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/22/18
 */

namespace PlMigration\Model;

use PlMigration\Exceptions\ModelException;
use PlMigration\Model\Field\Field;
use PlMigration\Model\Field\ImportField;

class ImportModel
{
    /** @var ImportField[] */
    private $apiFields;

    /**
     * @var string
     */
    private $primaryKey;

    /**
     * @var string
     */
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

    /**
     * @param $record
     * @return array
     * @throws ModelException
     */
    public function fillModel($record)
    {
        $row = [];
        foreach($this->apiFields as $field => $fieldInfo)
        {
            $value = $fieldInfo->getAlias()->getValue($record);

            if(empty($value) && $fieldInfo->getType() === Field::OPTIONAL)
            {
                continue;
            }
            if(empty($value) && ($fieldInfo->getType() === Field::REQUIRED || $fieldInfo->getType() === Field::PRIMARY_KEY))
            {
                throw new ModelException('Required field cannot be empty: '. $field);
            }
            foreach($fieldInfo->getExtra() as $extraAction)
            {
                $value = $extraAction->execute($value);
            }
            $row[$field] = $value;
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