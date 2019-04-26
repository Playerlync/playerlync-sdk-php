<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-03-14
 * Time: 15:15
 */

namespace PlMigration\Builder\Helper;


use PlMigration\Builder\Traits\ErrorLogBuilderTrait;
use PlMigration\Builder\Traits\NotificationTrait;
use PlMigration\Exceptions\BuilderException;
use PlMigration\Model\Field\ExportField;
use PlMigration\Model\Field\Field;

abstract class ExportBuilder
{
    use ErrorLogBuilderTrait;
    use NotificationTrait;

    /**
     * Fields to be retrieved from the data provider
     * @var ExportField[]
     */
    private $fields = [];

    /**
     * Add a field to be added to the output.
     * The order of the output fields is determined by the sequence of the function calls
     * @param string $apiAlias The field from the playerlync API
     * @param string|null $outputName The output name to be used for the output (such as the header name)
     * @param string $fieldType The type of field that is being created. Refer to Field.php constants for types available. Default is Field::VARIABLE
     * @param array $extra A list of objects that will allow value manipulation after the
     * @return $this
     */
    public function addField($apiAlias, $outputName = null, $fieldType = Field::VARIABLE, ...$extra)
    {
        $outputName = $outputName ?: $apiAlias;
        $this->fields[] = new ExportField($outputName, $apiAlias, $fieldType, $extra);
        return $this;
    }

    /**
     * Add a constant value field to the output
     * @param $apiAlias
     * @param null $outputName
     * @return $this
     */
    public function addConstant($apiAlias, $outputName = null)
    {
        return $this->addField($apiAlias, $outputName, Field::CONSTANT);
    }

    abstract protected function build();

    abstract public function export();

    /**
     * Validate fields the are added to the output file are valid
     * @return array
     * @throws BuilderException
     */
    protected function buildFields()
    {
        $fields = [];
        /** @var ExportField $field */
        foreach($this->fields as $field)
        {
            if($field->getField() === null && $field->getAlias() === null)
            {
                throw new BuilderException('Field and header cannot both be null');
            }
            if(array_key_exists($field->getField(), $fields))
            {
                throw new BuilderException('Attempted to insert duplicate header: '.$field->getField());
            }
            $fields[$field->getField()] = $field;
        }
        $this->fields = [];
        return $fields;
    }
}