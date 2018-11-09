<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/25/18
 */

namespace PlMigration\Helper;

use Monolog\Logger;

trait LoggerTrait
{
    /** @var Logger */
    private $logger;

    public function getLogger()
    {
        return $this->logger;
    }
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function error($message, $context = [])
    {
        if($this->logger !== null)
        {
            $this->logger->error($message, $context);
        }
    }

    public function warning($message, $context = [])
    {
        if($this->logger !== null)
        {
            $this->logger->warning($message, $context);
        }
    }

    public function debug($message, $context = [])
    {
        if($this->logger !== null)
        {
            $this->logger->debug($message, $context);
        }
    }
}