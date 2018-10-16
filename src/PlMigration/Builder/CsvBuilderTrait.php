<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/11/18
 */

namespace PlMigration\Builder;

trait CsvBuilderTrait
{
    /**
     * Delimiter used by the csv writer with a default value of a comma
     * @var string
     */
    private $delimiter = ',';

    /**
     * Enclosure used by the csv write with a default value of a double quote
     * @var string
     */
    private $enclosure = '"';

    /**
     * Fields to be retrieved from the data provider
     * @var array
     */
    private $fields = [];

    public function delimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    public function enclosure($enclosure)
    {
        $this->enclosure = $enclosure;
        return $this;
    }
}