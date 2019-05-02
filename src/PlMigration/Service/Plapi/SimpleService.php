<?php


namespace PlMigration\Service\Plapi;

use PlMigration\Helper\ApiClient;
use PlMigration\Service\IService;

class SimpleService implements IService
{
    use PlapiService;

    public function __construct($method, $service)
    {
        $this->method = $method;
        $this->service = $this->cleanupPath($service);
    }

    /**
     * @param ApiClient $apiConnection
     * @param array $options
     * @return mixed
     * @throws \PlMigration\Exceptions\ClientException
     */
    public function execute(ApiClient $apiConnection, $options = [])
    {
        return $apiConnection->validateResponse($apiConnection->request($this->method, $this->service, $options));
    }
}