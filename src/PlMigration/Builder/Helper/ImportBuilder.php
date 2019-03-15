<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-03-14
 * Time: 15:22
 */

namespace PlMigration\Builder\Helper;


use PlMigration\Builder\Traits\ErrorLogBuilderTrait;
use PlMigration\Exceptions\BuilderException;
use PlMigration\Helper\DataFunctions\IValueManipulator;
use PlMigration\Model\Field\Field;
use PlMigration\Model\Field\IAlias;
use PlMigration\Model\Field\ImportField;

abstract class ImportBuilder
{
    use ErrorLogBuilderTrait;

    /**
     * Fields to be used in the import
     * @var array
     */
    protected $fields = [];

    /**
     * Add a field to be mapped from the outside source to the playerlync system.
     * Unlike addField(), this function provides more flexibility by allowing multiple pieces of outside data point to a single point in Playerlync data, and vice versa.
     * However, it causes a higher likelyhood of bad data mapping.
     * DO NOT mix the addField() and mapField() together for an import. This will result in confusing results.
     * example:
     * data file contents:
     *   testlogin,smith,denver,qwerty
     * $importer
     * ->mapField('first_name', 0) //first_name field will read the first column value (testlogin)
     * ->mapField('last_name', 1) //last_name field will read the second column value (smith)
     * ->mapField('member', 0) //member field will ALSO read the first column value (testlogin)
     * ->mapField('password', 3) //password field will read the fourth column value (qwerty)
     * ->mapField('location', 2) //location field will read the third column value (denver)
     *
     * @param string $apiField The name of the field to be recognized by the Playerlync API
     * @param string|IAlias $alias The alias point from the external data source.
     * @param string $type The type that the field belongs to
     * @param array|IValueManipulator $extra Additional functionality to be done on the field before being inserted into the Playerlync system.
     * @return $this
     */
    public function mapField($apiField, $alias, $type = Field::VARIABLE, $extra = [])
    {
        $this->fields[] = new ImportField($apiField, $alias, $type, $extra);
        return $this;
    }

    abstract protected function build();

    abstract public function import();

    /**
     * Verify and re-organize the field data for the model
     * The fields will be deleted once verified
     * @return array
     * @throws BuilderException
     */
    protected function buildFields()
    {
        $fields = [];

        foreach($this->fields as $fieldInfo)
        {
            if(array_key_exists($fieldInfo->getField(), $fields))
            {
                throw new BuilderException('Attempting to add duplicate field: '. $fieldInfo->getField());
            }
            $fields[$fieldInfo->getField()] = $fieldInfo;
        }
        $this->fields = [];
        return $fields;
    }
}