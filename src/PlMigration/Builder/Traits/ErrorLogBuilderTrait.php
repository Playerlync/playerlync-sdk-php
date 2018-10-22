<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/17/18
 */

namespace PlMigration\Builder\Traits;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

trait ErrorLogBuilderTrait
{
    /**
     * Store errors into an error log file
     * @var Logger
     */
    private $errorLog;

    private function writeError($message)
    {
        echo $message.PHP_EOL;
        if($this->errorLog !== null)
        {
            $this->errorLog->error($message);
        }
    }

    private function setupErrorLog($logFile,$logName)
    {
        try
        {
            $handler = new StreamHandler($logFile, Logger::ERROR);
            $this->errorLog = new Logger($logName);
            $this->errorLog->pushHandler($handler);
        }
        catch (\Exception $e)
        {
            echo 'Failed to create error log'.PHP_EOL;
        }
    }
}