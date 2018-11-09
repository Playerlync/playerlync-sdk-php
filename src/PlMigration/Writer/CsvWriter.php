<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/5/18
 */

namespace PlMigration\Writer;

use Keboola\Csv\Exception;
use PlMigration\Exceptions\WriterException;

class CsvWriter extends \Keboola\Csv\CsvWriter implements IWriter
{
    /**
     * full file path of the of the data file to be used for writing into.
     *
     * @var string
     */
    private $file;

    /**
     * Write mode to be used on the file. By default it will create and overwrite any existing file
     *
     * @var string
     */
    private $writeMode = 'w';

    /**
     * CsvWriter constructor.
     * @param $file
     * @param $delimiter
     * @param $enclosure
     * @param array $options
     * @throws WriterException
     */
    public function __construct($file, $delimiter, $enclosure, $options = [])
    {
        $this->file = $file;

        if(isset($options['file_append']) && $options['file_append'] === true)
        {
            $this->writeMode = 'a';
        }

        try
        {
            parent::__construct($file, $delimiter, $enclosure);
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
        $this->writeRow($row);
    }

    public function getFile()
    {
        return $this->file;
    }

    protected function openCsvFile($fileName)
    {
        if(!file_exists($fileName) && $this->isAppend()) //Verify it is a real file append
        {
            $this->writeMode = 'w';
        }
        $this->filePointer = @fopen($fileName, $this->writeMode);
        if (!$this->filePointer) {
            throw new Exception(
                "Cannot open file {$fileName} " . error_get_last()['message'],
                Exception::FILE_NOT_EXISTS
            );
        }
    }

    public function isAppend()
    {
        return $this->writeMode === 'a';
    }
}