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

/**
 * Trait containing settings to enable the error logging during exporting or importing
 * @package PlMigration\Builder\Traits
 */
trait ErrorLogBuilderTrait
{
    /**
     * Logger object
     * @var Logger
     */
    protected $errorLog;

    /**
     * Location of the error log file
     * @var string
     */
    private $errorLogFile;

    /**
     * Add an error message
     * @param string $message
     */
    protected function addError($message)
    {
        if($this->errorLog !== null)
        {
            $this->errorLog->error($message);
        }
    }

    /**
     * Add a debug message
     * @param string $message
     */
    protected function addDebug($message)
    {
        if($this->errorLog !== null)
        {
            $this->errorLog->debug($message);
        }
    }

    /**
     * Set the location of where the file error/debug log will reside
     * @param string $file
     * @return $this
     */
    public function errorLog($file)
    {
        $this->errorLogFile = $file;
        return $this;
    }

    /**
     * Create the error log with the settings chosen
     * @param string $logName
     * @throws BuilderException
     */
    protected function buildErrorLog($logName)
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