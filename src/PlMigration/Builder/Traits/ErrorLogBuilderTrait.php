<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/17/18
 */

namespace PlMigration\Builder\Traits;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PlMigration\Exceptions\BuilderException;

trait ErrorLogBuilderTrait
{
    /**
     * Logger object
     * @var Logger
     */
    private $errorLog;

    /**
     * Location of the error log file
     */
    private $errorLogFile;

    /**
     * Add an error message
     * @param $message
     */
    private function addError($message)
    {
        if($this->errorLog !== null)
        {
            $this->errorLog->error($message);
        }
    }

    /**
     * Add a debug message
     * @param $message
     */
    private function addDebug($message)
    {
        if($this->errorLog !== null)
        {
            $this->errorLog->debug($message);
        }
    }

    /**
     * Set the location of where the file error/debug log will reside
     * @param $file
     * @return $this
     */
    public function errorLog($file)
    {
        $this->errorLogFile = $file;
        return $this;
    }

    /**
     * Create the error log with the settings chosen
     * @param $logName
     * @throws BuilderException
     */
    private function buildErrorLog($logName)
    {
        if($this->errorLogFile === null)
        {
            return;
        }

        try
        {
            $handler = new StreamHandler($this->errorLogFile, Logger::DEBUG);
            $handler->setFormatter(new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context%\n"));
            $this->errorLog = new Logger($logName);
            $this->errorLog->pushHandler($handler);
        }
        catch (\Exception $e)
        {
            echo $e->getMessage();
            throw new BuilderException('Failed to create error log file. '.$e->getMessage());
        }
    }
}