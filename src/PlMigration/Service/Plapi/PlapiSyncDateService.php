<?php


namespace PlMigration\Service\Plapi;

use Closure;
use PlMigration\Exceptions\ClientException;
use PlMigration\Helper\ApiClient;
use PlMigration\Helper\ISyncDataUpdate;
use PlMigration\Helper\PlapiClient;

/**
 * Class to append to export process to update data on records once after the main process has finished.
 * Class PlapiSyncDateService
 * @package PlMigration\Service\Plapi
 */
class PlapiSyncDateService extends SimpleService implements ISyncDataUpdate
{
    /**
     * @var array
     */
    protected $requests;

    /**
     * @var array
     */
    protected $keys;

    /**
     * @var Closure
     */
    protected $bodyBuilder;

    /**
     * @var PlapiClient
     */
    protected $client;

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

    public function checkRawData($data, $logger = null): bool
    {
        if($body = $this->bodyBuilder->__invoke($data))
            $this->requests[] = [$this->method, $this->buildServicePath($this->service, $data, $this->keys), $body];
        return true;
    }

    public function tearDown($logger = null)
    {
        if(empty($this->requests))
            return [];
        
        foreach($this->requests as list($method, $path, $body))
        {
            try {
                $this->client->request($method, $path, ['json' => $body]);
            } catch (ClientException $e) {
                $this->client->warning('Service failed: '. $e->getMessage());
            }
        }
    }

    public function setClient($client)
    {
        $this->client = $client;
    }
}