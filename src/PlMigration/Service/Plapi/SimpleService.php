<?php


namespace PlMigration\Service\Plapi;

use PlMigration\Exceptions\ClientException;
use PlMigration\Helper\ApiClient;
use PlMigration\Service\IService;

class SimpleService implements IService
{
    use PlapiService;

    public function __construct($method, $servicePath)
    {
        $this->method = $method;
        $this->service = $this->cleanupPath($servicePath);
    }

    /**
     * @param ApiClient $apiConnection
     * @param array $options
     * @return mixed
     * @throws ClientException
     */
    public function execute(ApiClient $apiConnection, $options = [])
    {
        return $apiConnection->validateResponse($apiConnection->request($this->method, $this->service, $options));
    }

    /**
     * @param ApiClient $apiClient
     * @return mixed
     * @throws ClientException
     */
    public function getStructure(ApiClient $apiClient)
    {
        return $apiClient->validateResponse($apiClient->request($this->method, $this->service, ['query' => ['structure'=> 1]]))->data->structure;
    }
}
