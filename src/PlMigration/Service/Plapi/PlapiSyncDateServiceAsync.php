<?php


namespace PlMigration\Service\Plapi;

use Closure;
use GuzzleHttp\Psr7\Request;
use PlMigration\Helper\PlapiClient;

class PlapiSyncDateServiceAsync extends PlapiSyncDateService
{
    /**
     * @var integer
     */
    private $threads = 1;

    /**
     * SyncService constructor.
     * @param string $method
     * @param string $servicePath
     * @param Closure $bodyBuilder
     * @param int $threads
     */
    public function __construct($method, $servicePath, $bodyBuilder, $threads = 1)
    {
        parent::__construct($method, $servicePath, $bodyBuilder);
        if($threads > 1)
            $this->threads = $threads;
    }

    public function tearDown($logger = null)
    {
        if(empty($this->requests))
            return [];

        $this->client->batchRequests(function(PlapiClient $client) {
            $client->debug('Running ' . count($this->requests) . ' batch requests');
            foreach($this->requests as list($method, $path, $body))
                yield new Request($method, $client->buildPlapiPath($path), $client->getDefaultHeaders(), json_encode($body));
        }, $this->threads);
    }
}