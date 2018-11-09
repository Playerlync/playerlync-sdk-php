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
use PlMigration\Reader\CsvReader;
use PlMigration\Writer\CsvWriter;

trait CsvBuilderTrait
{
    /**
     * array of options allowed on the data file writer/reader
     *
     * @var array
     */
    private $csvOptions = [];
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
     * Toggle to allow export to use the export for the
     * @param bool $append
     * @return $this
     */
    public function writeFileAppend($append)
    {
        $this->csvOptions['file_append'] = $append;
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
            return new CsvWriter($file, $this->delimiter, $this->enclosure, $this->csvOptions);
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