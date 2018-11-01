<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/11/18
 */

namespace PlMigration\Builder\Traits;

use PlMigration\Exceptions\BuilderException;
use PlMigration\Exceptions\ReaderException;
use PlMigration\Exceptions\WriterException;
use PlMigration\Model\Field;
use PlMigration\Reader\CsvReader;
use PlMigration\Writer\CsvWriter;

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
     * @var Field[]
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

    /**
     * @param $file
     * @return CsvWriter
     * @throws BuilderException
     */
    private function buildWriter($file)
    {
        try
        {
            return new CsvWriter($file, $this->delimiter, $this->enclosure);
        }
        catch (WriterException $e)
        {
            throw new BuilderException($e->getMessage());
        }
    }

    /**
     * @param $file
     * @return CsvReader
     * @throws BuilderException
     */
    private function buildReader($file)
    {
        try
        {
            return new CsvReader($file, $this->delimiter, $this->enclosure);
        }
        catch (ReaderException $e)
        {
            throw new BuilderException($e->getMessage());
        }
    }
}