<?php


namespace PlMigration\Service\Plapi;

use Closure;
use GuzzleHttp\Psr7\Request;
use PlMigration\Helper\ApiClient;
use PlMigration\Helper\PlapiClient;
use PlMigration\Service\ISyncService;

class PlapiSyncDateService extends SimpleService implements ISyncService
{
    /**
     * @var array
     */
    private $requests;

    /**
     * @var array
     */
    private $keys;

    /**
     * @var Closure
     */
    private $bodyBuilder;

    /**
     * @var PlapiClient
     */
    private $client;

    /**
     * SyncService constructor.
     * @param string $method
     * @param string $servicePath
     * @param Closure $bodyBuilder
     */
    public function __construct($method, $servicePath, $bodyBuilder)
    {
        parent::__construct($method, $servicePath);
        $this->keys = $this->parseKeysInPath($servicePath);
        $this->requests = [];
        $this->bodyBuilder = $bodyBuilder;
    }

    /**
     * @param ApiClient $apiConnection
     * @param array $options
     * @return mixed
     */
    public function execute(ApiClient $apiConnection, $options = [])
    {
        return [];
    }

    public function getStructure(ApiClient $apiClient)
    {
        return [];
    }

    public function addRecord($data)
    {
        if($encoded = $this->buildBody($data))
            $this->requests[] = [$this->method, $this->buildServicePath($this->service, $data, $this->keys), json_encode($encoded)];
    }

    private function buildBody($data)
    {
        return $this->bodyBuilder->__invoke($data);
    }

    public function sendUpdate()
    {
        if(empty($this->requests))
            return [];
        
        $this->client->batchRequests(function(PlapiClient $client) {
            $client->debug('Running ' . count($this->requests) . ' batch requests');
            foreach($this->requests as list($method, $path, $body))
                yield new Request($method, $client->buildPlapiPath($path), $client->getDefaultHeaders(), $body);
        }, 25);
    }

    public function setClient($client)
    {
        $this->client = $client;
    }
}