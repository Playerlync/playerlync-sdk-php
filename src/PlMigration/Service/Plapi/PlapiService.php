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

    /**
     * extract all strings inside curly brackets as they will be used as the key to match with the parent service
     * @param string $servicePath
     * @return array
     */
    protected function parseKeysInPath($servicePath)
    {
        $results = preg_match_all('/\/{(.*?)}/', $servicePath, $matches);
        if($results && isset($matches[1]))
        {
            return $matches[1];
        }
        return [];
    }

    /**
     * Replace the primary keys in the service with actual values that are returned by the parent service results
     * @param string $serviceTemplate
     * @param object $data
     * @param array $keys
     * @return string
     */
    protected function buildServicePath($serviceTemplate, $data, $keys)
    {
        foreach($keys as $id)
        {
            if(isset($data->$id))
                $serviceTemplate = str_replace('{'.$id.'}', $data->$id, $serviceTemplate);
        }
        return $serviceTemplate;
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
