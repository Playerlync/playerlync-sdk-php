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

/**
 * Trait containing configurations for using a csv file in exporting/importing data
 * @package PlMigration\Builder\Traits
 */
trait CsvBuilderTrait
{
    /**
     * array of options allowed on the data file writer/reader
     *
     * @var array
     */
    private $csvOptions = [];

    /**
     * Delimiter used by the csv writer/reader. Default value: ,
     * @var string
     */
    private $delimiter = ',';

    /**
     * Enclosure used by the csv writer/reader. Default value: "
     * @var string
     */
    private $enclosure = '"';

    /**
     * Set the recognized delimiter character between fields.
     * @param string $delimiter
     * @return $this
     */
    public function delimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * Set the recognized enclosure character around fields.
     * @param string $enclosure
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
     * Disabled by default.
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
     * @param string $file File path to the location of the csv file to be created
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
     * @param string $file File path to the location of the csv file to be found
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