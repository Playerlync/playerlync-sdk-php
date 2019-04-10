<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/29/18
 */

namespace PlMigration\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PlMigration\Exceptions\ClientException;
use Zend\Stdlib\ResponseInterface;

class PlapiClient implements ApiClient
{
    /**
     * @var Client
     */
    private $client;

    /**
     * API version to make requests for
     *
     * @var string
     */
    private $apiVersion = 'v3';

    /**
     * Primary org id value to use for Primary-Org-Id header
     *
     * @var string
     */
    private $primaryOrgId;

    /**
     * server info
     *
     * @var array
     */
    private $serverInfo = [];

    /**
     * @var PlapiOauthManager
     */
    private $oauthManager;

    /**
     * PlapiClient constructor.
     * @param array $config
     * @throws ClientException
     */
    public function __construct(array $config = [])
    {
        $required = ['host', 'client_id', 'client_secret', 'username', 'password'];

        foreach($required as $item)
        {
            if(!isset($config[$item]))
            {
                throw new ClientException('Required "'.$item.'" not supplied in config.');
            }
        }

        if (isset($config['api_version']))
        {
            $this->apiVersion = $config['api_version'];
        }

        if(isset($config['primary_org_id']))
        {
            $this->primaryOrgId = $config['primary_org_id'];
        }

        $this->setupClient($config);
        $this->ping();
        $this->oauthManager = new PlapiOauthManager($this->client, $config);
        $this->oauthManager->authenticate();
    }

    /**
     * Instantiate the guzzle client
     * @param $config
     */
    private function setupClient($config)
    {
        $this->client = new Client([
            'base_uri' =>  $config['host'],
            'verify' => __DIR__.'/curl-ca-bundle.pem',
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'exceptions' => false,
            'cookies' => new CookieJar
        ]);
    }

    /**
     * @param $path
     * @param $query
     * @return object
     * @throws ClientException
     */
    public function get($path, $query)
    {
        $response = $this->request('GET', $path, ['query' => $query]);
        return $this->validateResponse($response);
    }

    /**
     * @param $path
     * @param $params
     * @return object
     * @throws ClientException
     */
    public function post($path, $params)
    {
        $response = $this->request('POST', $path, $params);
        return $this->validateResponse($response);
    }

    /**
     * @param $path
     * @param $params
     * @return object
     * @throws ClientException
     */
    public function put($path, $params)
    {
        $response = $this->request('PUT', $path, $params);
        return $this->validateResponse($response);
    }

    /**
     * @param $path
     * @param $params
     * @return object
     * @throws ClientException
     */
    public function delete($path, $params)
    {
        $response = $this->request('DELETE', $path, $params);
        return $this->validateResponse($response);
    }

    /**
     * @param $requestsArray
     * @return array
     */
    public function poolRequests($requestsArray)
    {
        $requests = function ($requests) {
            foreach($requests as $request)
            {
                yield new Request($request['method'], $this->buildPlapiPath($request['path']).'?upsert=1', $this->getDefaultHeaders(), json_encode($request['body']));
            }
        };
        $this->checkToken();
        return Pool::batch($this->client, $requests($requestsArray), ['concurrency' => '25']);
    }

    public function getMemberId()
    {
        return $this->oauthManager->getMemberId();
    }

    public function getPrimaryOrgId()
    {
        return $this->primaryOrgId;
    }

    /**
     * @return array
     */
    private function getDefaultHeaders()
    {
        return [
            'Authorization' => 'Bearer '. $this->oauthManager->getAccessToken(),
            'Primary-Org-Id' => $this->primaryOrgId
        ];
    }

    private function buildPlapiPath($path)
    {
        if($path[0] !== '/')
            $path = '/'.$path;
        if(strpos($path, '/' . $this->apiVersion . '/') === 0)
        {
            return '/plapi'.$path;
        }
        return '/plapi/'.$this->apiVersion.$path;
    }

    /**
     * @param $method
     * @param $servicePath
     * @param $options
     * @return Response
     * @throws ClientException
     */
    public function request($method, $servicePath, $options = [])
    {
        $options = array_merge([
            'headers' => $this->getDefaultHeaders()
        ],$options);
        try
        {
            $response = $this->client->request($method, $this->buildPlapiPath($servicePath), $options);
        }
        catch (GuzzleException $e)
        {
            throw new ClientException($e->getMessage());
        }

        if($response->getStatusCode() !== 200)
        {
            throw new ClientException('Invalid status code response: '.$response->getStatusCode());
        }

        if($response->getBody()->read(1) !== '{')
        {
            throw new ClientException('API did not return JSON response. '.strip_tags($response->getBody()->read(4096)));
        }
        $response->getBody()->rewind();

        $this->checkToken();
        return $response;
    }

    /**
     * @param Response $response
     * @return object
     * @throws ClientException
     */
    public function validateResponse($response)
    {
        $json = json_decode($response->getBody());

        if(!$json)
        {
            throw new ClientException('Unable to decode json');
        }
        if($json->status === 'INVALID_REQUEST')
        {
            throw new ClientException($json->errors[0]->message);
        }
        return $json;
    }

    /**
     * @throws ClientException
     */
    private function ping()
    {
        try
        {
            $response = $this->client->get('/plapi/v3/service/ping');
        }
        catch(GuzzleException $e)
        {
            throw new ClientException('Unable to connect to ping service');
        }

        if($header = $response->getHeader('Pl-Version'))
        {
            $this->serverInfo['version'] = $header[0];
        }
    }

    public function getServerVersion()
    {
        return array_key_exists('version', $this->serverInfo) ? $this->serverInfo['version'] : 'Unknown';
    }

    /**
     * @throws ClientException
     */
    private function checkToken()
    {
        if($this->oauthManager->needsToRenew())
        {
            $this->oauthManager->authenticate();
        }
    }
}