<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 9/25/18
 */

namespace PlMigration\Writer;

use PlMigration\Exceptions\WriterException;

/**
 * Class TransactionLogger
 */
class TransactionLogger
{
    private $enclosure;
    private $delimiter;
    private $successFile;
    private $failureFile;

    /**
     * TransactionLogger constructor.
     * @param $directory
     * @param $fileName
     * @param $enclosure
     * @param $delimiter
     * @throws WriterException
     */
    public function __construct($directory, $fileName, $enclosure, $delimiter)
    {
        if(!is_dir($directory) || !is_writable($directory))
        {
            throw new WriterException('Unable to write or directory does not exist: '.$directory);
        }
        $this->enclosure = $enclosure;
        $this->delimiter = $delimiter;
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $fileName = pathinfo($fileName, PATHINFO_FILENAME);
        $this->successFile = $directory.'/'.$fileName.'_success_'.date('Ymd_His').'.'.$fileExt;
        $this->failureFile = $directory.'/'.$fileName.'_failure_'.date('Ymd_His').'.'.$fileExt;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->write($headers, $this->successFile);
        if(!\in_array('error', $headers, true))
        {
            $headers[] = 'error';
        }
        $this->write($headers, $this->failureFile);
    }

    /**
     * @param array $record
     */
    public function addSuccess(array $record)
    {
        $this->write($record, $this->successFile);
    }

    /**
     * @param array $record
     */
    public function addFailure(array $record)
    {
        $this->write($record, $this->failureFile);
    }

    /**
     * @param $record
     * @param $file
     * @param int $mode
     */
    private function write($record, $file, $mode = FILE_APPEND)
    {
        $string = $this->enclosure.implode($this->enclosure.$this->delimiter.$this->enclosure, $record).$this->enclosure.PHP_EOL;
        if(false === @file_put_contents($file, $string, $mode))
        {
            throw new WriterException('Unable to write into transaction file.');
        }
    }
}