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
use PlMigration\Exceptions\ClientException;

class PlapiClient
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
     * JWT access token retrieved from oauth server
     *
     * @var string
     */
    private $accessToken;

    /**
     * Primary org id value to use for Primary-Org-Id header
     *
     * @var string
     */
    private $primaryOrgId;

    /**
     * member Id of authenticated user used
     *
     * @var string
     */
    private $memberId;

    /**
     * server info
     *
     * @var array
     */
    private $serverInfo = [];

    /**
     * Array containing the given configurations for the server connection
     * @var array
     */
    private $config;

    /**
     * Unix Timestamp value of when the Oauth token will expire
     * @var float
     */
    private $expireTime;

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

        if (isset($config['default_api_version']))
        {
            $this->apiVersion = $config['default_api_version'];
        }

        if(isset($config['primary_org_id']))
        {
            $this->primaryOrgId = $config['primary_org_id'];
        }

        $this->config = $config;
        $this->setupClient($config);
        $this->ping();
        $this->authenticate($config);
    }


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
        $options = [
            'headers' => $this->getHeaders(),
            'query' => $query
        ];

        $response = $this->request('GET', $this->buildPlapiPath($path), $options);

        $this->validatePlapiOk($response);

        return $response;
    }

    /**
     * @param $path
     * @param $params
     * @return object
     * @throws ClientException
     */
    public function post($path, $params)
    {
        $options = array_merge([
            'headers' => $this->getHeaders()
        ],$params);

        $response = $this->request('POST', $this->buildPlapiPath($path), $options);

        $this->validatePlapiOk($response);

        return $response;
    }

    /**
     * @param $path
     * @param $params
     * @return object
     * @throws ClientException
     */
    public function put($path, $params)
    {
        $options = array_merge([
            'headers' => $this->getHeaders()
        ],$params);

        $response = $this->request('PUT', $this->buildPlapiPath($path), $options);

        $this->validatePlapiOk($response);

        return $response;
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
                yield new Request($request['method'], $this->buildPlapiPath($request['path']).'?upsert=1', $this->getHeaders(), json_encode($request['body']));
            }
        };
        $this->checkToken();
        return Pool::batch($this->client, $requests($requestsArray), ['concurrency' => '25']);
    }

    public function getMemberId()
    {
        return $this->memberId;
    }

    public function getPrimaryOrgId()
    {
        return $this->primaryOrgId;
    }

    /**
     * @param $config
     * @throws ClientException
     */
    private function authenticate($config)
    {
        $response = $this->request('POST', '/API/src/Scripts/OAuth/token.php',[
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'username' => $config['username'],
                'password' => $config['password']
            ]], false);

        if(isset($response->error_description))
        {
            throw new ClientException('Could not authenticate: ' . $response->error_description);
        }

        if($this->primaryOrgId === null)
        {
            $this->primaryOrgId = $response->primary_org_id;
        }
        $this->accessToken = $response->access_token;
        $this->memberId = $response->memberid;
        $this->expireTime = time() + $response->expires_in;
    }

    /**
     * @return array
     */
    private function getHeaders()
    {
        return [
            'Authorization' => 'Bearer '. $this->accessToken,
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
     * @param $uri
     * @param $options
     * @param bool $checkToken
     * @return object
     * @throws ClientException
     */
    private function request($method, $uri, $options, $checkToken = true)
    {
        try
        {
            $response = $this->client->request($method, $uri, $options);
        }
        catch (GuzzleException $e)
        {
            throw new ClientException($e->getMessage());
        }

        if($response->getStatusCode() !== 200)
        {
            throw new ClientException('Invalid status code response: '.$response->getStatusCode());
        }

        $json = json_decode($response->getBody());
        if(!$json)
        {
            throw new ClientException('Unable to decode JSON response. '.strip_tags($response->getBody()));
        }

        if($checkToken)
        {
            $this->checkToken();
        }
        return $json;
    }

    /**
     * @param $response
     * @throws ClientException
     */
    private function validatePlapiOk($response)
    {
        if($response->status === 'INVALID_REQUEST')
        {
            throw new ClientException($response->errors[0]->message);
        }
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
        if($this->expireTime - time() < 600)
        {
            $this->authenticate($this->config);
        }
    }
}