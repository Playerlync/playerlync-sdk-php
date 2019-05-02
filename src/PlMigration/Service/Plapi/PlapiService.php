<?php


namespace PlMigration\Service\Plapi;


trait PlapiService
{
    /**
     * @var string
     */
    protected $method;

    /**
     * @var string
     */
    protected $service;

    /**
     * Does path cleanup to minimize the chance of having a mal-constructed service path
     * @param $path
     * @return string
     */
    protected function cleanupPath($path)
    {
        if(substr($path,0,1) !== '/')
            return '/' . $path;
        return $path;
    }

    public function __toString()
    {
        return $this->service;
    }

    /**
     * @return string
     */
    public function getService(): string
    {
        return $this->service;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }
}