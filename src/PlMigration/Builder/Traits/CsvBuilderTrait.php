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

    /**
     * Set the recognized delimiter character between fields.
     * @param $delimiter
     * @return $this
     */
    public function delimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * Set the recognized enclosure characted between fields.
     * @param $enclosure
     * @return $this
     */
    public function enclosure($enclosure)
    {
        $this->enclosure = $enclosure;
        return $this;
    }

    /**
     * Toggle to allow export to use the export for multiple executions into a file.
     * Used by the export application for running the same service with different filter settings into one file.
     * @param bool $append
     * @return $this
     */
    public function writeFileAppend($append)
    {
        $this->csvOptions['file_append'] = $append;
        return $this;
    }

    /**
     * Build the csv writer object with the settings desired
     * @param $file
     * @return CsvWriter
     * @throws BuilderException
     */
    protected function buildWriter($file)
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
     * Build the csv reader object with the settings desired
     * @param $file
     * @return CsvReader
     * @throws BuilderException
     */
    protected function buildReader($file)
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