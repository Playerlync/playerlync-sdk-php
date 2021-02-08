<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/17/18
 */

namespace PlMigration\Builder\Traits;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
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
     * Determine the log level
     * @var int
     */
    private $logLevel;

    /**
     * Number of maximum days to allow log file rotation
     * @var int
     */
    private $maxDays = 0;

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

    protected function addWarning($message)
    {
        if($this->errorLog !== null)
        {
            $this->errorLog->warning($message);
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
     * Add a notice message
     * @param string $message
     */
    protected function addNotice($message)
    {
        if($this->errorLog !== null)
        {
            $this->errorLog->notice($message);
        }
    }

    /**
     * Set the location of where the file error/debug log will reside
     * @param string $file
     * @param int $logLevel
     * @return $this
     */
    public function errorLog($file, $logLevel = Logger::ERROR)
    {
        return $this->rotatingErrorLog($file, 0, $logLevel);
    }

    /**
     * Create a rotating error log file where logging messages will be stored
     * @param string $file
     * @param int $maxDays number of days to store the error log files
     * @param int $logLevel
     * @return $this
     */
    public function rotatingErrorLog($file, $maxDays, $logLevel = Logger::ERROR)
    {
        $this->errorLogFile = $file;
        $this->logLevel = $logLevel;
        $this->maxDays = $maxDays;
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
            if($this->maxDays > 0)
            {
                $handler = new RotatingFileHandler($this->errorLogFile, $this->maxDays, $this->logLevel);
            }
            else
            {
                $handler = new StreamHandler($this->errorLogFile, $this->logLevel);
            }
            $handler->setFormatter(new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context%\n"));
            $this->errorLog = new Logger($logName);
            $this->errorLog->pushHandler($handler);
        }
        catch (\Exception $e)
        {
            throw new BuilderException('Failed to create error log file. '.$e->getMessage());
        }
    }
}