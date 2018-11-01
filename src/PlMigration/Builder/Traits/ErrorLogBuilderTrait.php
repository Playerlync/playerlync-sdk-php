<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/17/18
 */

namespace PlMigration\Builder\Traits;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PlMigration\Exceptions\BuilderException;

trait ErrorLogBuilderTrait
{
    /**
     * Store errors into an error log file
     * @var Logger
     */
    private $errorLog;

    /**
     * Path to the location of the error log created
     */
    private $errorLogFile;

    private function addError($message)
    {
        //echo $message.PHP_EOL;
        if($this->errorLog !== null)
        {
            $this->errorLog->error($message);
        }
    }

    private function addDebug($message)
    {
        //echo $message.PHP_EOL;
        if($this->errorLog !== null)
        {
            $this->errorLog->debug($message);
        }
    }

    public function errorLog($file)
    {
        $this->errorLogFile = $file;
        return $this;
    }

    /**
     * @param $logName
     * @throws BuilderException
     */
    private function buildErrorLog($logName)
    {
        if(!$this->errorLogFile === null)
        {
            return;
        }

        try
        {
            $handler = new StreamHandler($this->errorLogFile, Logger::ERROR);
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