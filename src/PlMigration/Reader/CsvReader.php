<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/11/18
 */

namespace PlMigration\Reader;


use Keboola\Csv\Exception;
use PlMigration\Exceptions\ReaderException;

class CsvReader implements IReader
{
    private $file;

    private $reader;

    /**
     *
     * @param $file
     * @param $delimiter
     * @param $enclosure
     * @throws ReaderException
     */
    public function __construct($file, $delimiter, $enclosure)
    {
        $this->file = $file;
        try
        {
            $this->reader = new \Keboola\Csv\CsvReader($file, $delimiter, $enclosure);
        }
        catch (Exception $e)
        {
            throw new ReaderException($e->getMessage());
        }
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getRecord()
    {
        return $this->reader->current();
    }

    public function hasNext()
    {
        $this->reader->valid();
    }

    public function next()
    {
        $this->reader->next();
    }
}