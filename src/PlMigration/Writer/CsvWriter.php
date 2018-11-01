<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/5/18
 */

namespace PlMigration\Writer;

use Keboola\Csv\Exception;
use PlMigration\Exceptions\WriterException;

class CsvWriter implements IWriter
{
    private $writer;

    private $file;

    /**
     * CsvWriter constructor.
     * @param $file
     * @param $delimiter
     * @param $enclosure
     * @throws WriterException
     */
    public function __construct($file, $delimiter, $enclosure)
    {
        $this->file = $file;
        try
        {
            $this->writer = new \Keboola\Csv\CsvWriter($file, $delimiter, $enclosure);
        }
        catch (\Exception $e)
        {
            throw new WriterException($e->getMessage());
        }
    }

    /**
     * @param $row
     * @throws Exception
     */
    public function writeRecord($row)
    {
        $this->writer->writeRow($row);
    }

    public function getFile()
    {
        return $this->file;
    }
}