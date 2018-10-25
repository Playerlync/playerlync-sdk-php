<?php

/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 PlayerLync
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace PlayerLync;

use PlayerLync\Authentication\Oauth2\AccessToken;
use PlayerLync\Authentication\Oauth2\OAuth2Client;
use PlayerLync\Exceptions\PlayerLyncSDKException;
use PlayerLync\FileUpload\PlayerLyncFile;
use PlayerLync\HttpClients\PlayerLyncCurlHttpClient;

/**
 * Class PlayerLync
 *
 * @package PlayerLync
 */
class PlayerLync
{

    /**
     * @const string Version number of the PlayerLync PHP SDK.
     */
    const VERSION = '4000.1.0';

    /**
     * @const string Default PlayerLync API version for requests.
     */
    const DEFAULT_API_VERSION = 'v2';

    /**
     * @const string The default grant type for OAuth.
     */
    const OAUTH_GRANT_TYPE = 'password';

    /**
     * @var PlayerLyncApp The PlayerLyncApp entity.
     */
    protected $app;

    /**
     * @var PlayerLyncClient The PlayerLync client service.
     */
    protected $client;

    /**
     * @var OAuth2Client The OAuth 2.0 client service.
     */
    protected $oAuth2Client;

    /**
     * @var string The host url for the PlayerLync API
     */
    protected $apiHost;

    /**
     * @var AccessToken The AccessToken returned from the OAuth2 client
     */
    protected $accessToken;

    /**
     * @var PlayerLyncResponse The last response returned from the API
     */
    protected $lastResponse;

    /**
     * Instantiates a new PlayerLync super-class object.
     *
     * @param array $config
     *
     * @throws PlayerLyncSDKException
     */
    public function __construct(array $config = [])
    {
        $this->apiHost = isset($config['host']);
        if (!$this->apiHost)
        {
            throw new PlayerLyncSDKException('Required "host" key not supplied in config.');
        }
        else
        {
            $this->apiHost = $config['host'];
        }

        $clientId = isset($config['client_id']);
        if (!$clientId)
        {
            throw new PlayerLyncSDKException('Required "client_id" key not supplied in config.');
        }

        $clientSecret = isset($config['client_secret']);
        if (!$clientSecret)
        {
            throw new PlayerLyncSDKException('Required "client_secret" key not supplied in config.');
        }

        $username = isset($config['username']);
        if (!$username)
        {
            throw new PlayerLyncSDKException('Required "username" key not supplied in config.');
        }

        $password = isset($config['password']);
        if (!$password)
        {
            throw new PlayerLyncSDKException('Required "password" key not supplied in config.');
        }

        if (isset($config['default_api_version']))
        {
            $this->defaultApiVersion = $config['default_api_version'];
        }
        else
        {
            $this->defaultApiVersion = static::DEFAULT_API_VERSION;
        }

        $this->app = new PlayerLyncApp($config['client_id'], $config['client_secret'], $config['username'], $config['password'], $config['primary_org_id']);

        $httpClientHandler = new PlayerLyncCurlHttpClient();

        $this->client = new PlayerLyncClient($this->apiHost, $this->defaultApiVersion, $httpClientHandler);

        $this->oauthClient = $this->getOAuth2Client();

        $this->accessToken = $this->oauthClient->getAccessToken();


    }

    /**
     * Returns the host url for the PlayerLync API
     *
     * @return mixed
     */
    public function getApiHost()
    {
        return $this->apiHost;
    }


    /**
     * Returns the PlayerLyncApp entity.
     *
     * @return PlayerLyncApp
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Returns the PlayerLyncClient service.
     *
     * @return PlayerLyncClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Returns the OAuth 2.0 client service.
     *
     * @return OAuth2Client
     */
    public function getOAuth2Client()
    {
        if (!$this->oAuth2Client instanceof OAuth2Client)
        {
            $app = $this->getApp();
            $client = $this->getClient();
            $this->oAuth2Client = new OAuth2Client($app, $client, $this->defaultApiVersion);
        }

        return $this->oAuth2Client;
    }

    /**
     * Returns the last response returned from API.
     *
     * @return PlayerLyncResponse|null
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }


    /**
     * Returns the default AccessToken entity.
     *
     * @return AccessToken|null
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Sets the default access token to use with requests.
     *
     * @param AccessToken|string $accessToken The access token to save.
     *
     * @throws \InvalidArgumentException
     */
    public function setAccessToken($accessToken)
    {
        if (is_string($accessToken))
        {
            $this->accessToken = new AccessToken($accessToken);

            return;
        }

        if ($accessToken instanceof AccessToken)
        {
            $this->accessToken = $accessToken;

            return;
        }

        throw new \InvalidArgumentException('The default access token must be of type "string" or PlayerLync\AccessToken');
    }

    /**
     * Returns the default API version.
     *
     * @return string
     */
    public function getDefaultApiVersion()
    {
        return $this->defaultApiVersion;
    }

    /**
     * Sends a GET request to API and returns the result.
     *
     * @param string      $endpoint
     * @param array|null  $params
     * @param string|null $apiVersion
     *
     * @return PlayerLyncResponse
     *
     * @throws PlayerLyncSDKException
     */
    public function get($endpoint, array $params = [], $apiVersion = null)
    {
        return $this->sendRequest(
            'GET',
            $endpoint,
            $params,
            $this->accessToken,
            $apiVersion
        );
    }

    /**
     * Sends a POST request to API and returns the result.
     *
     * @param string $endpoint
     * @param array  $params
     * @param null   $apiVersion
     *
     * @return PlayerLyncResponse
     *
     * @throws PlayerLyncSDKException
     */
    public function post($endpoint, array $params = [], $apiVersion = null)
    {
        return $this->sendRequest(
            'POST',
            $endpoint,
            $params,
            $this->accessToken,
            $apiVersion
        );
    }

    /**
     * Sends a PUT request to API and returns the result.
     *
     * @param string      $endpoint
     * @param array       $params
     * @param string|null $apiVersion
     *
     * @return PlayerLyncResponse
     *
     * @throws PlayerLyncSDKException
     */
    public function put($endpoint, array $params = [], $apiVersion = null)
    {
        return $this->sendRequest(
            'PUT',
            $endpoint,
            $params,
            $this->accessToken,
            $apiVersion
        );
    }

    /**
     * Sends a DELETE request to API and returns the result.
     *
     * @param string      $endpoint
     * @param array       $params
     * @param string|null $apiVersion
     *
     * @return PlayerLyncResponse
     *
     * @throws PlayerLyncSDKException
     */
    public function delete($endpoint, array $params = [], $apiVersion = null)
    {
        return $this->sendRequest(
            'DELETE',
            $endpoint,
            $params,
            $this->accessToken,
            $apiVersion
        );
    }


    /**
     * Sends a request to API and returns the result.
     *
     * @param string                  $method
     * @param string                  $endpoint
     * @param array                   $params
     * @param AccessToken|string|null $accessToken
     * @param string|null             $apiVersion
     *
     * @return PlayerLyncResponse
     *
     * @throws PlayerLyncSDKException
     */
    public function sendRequest($method, $endpoint, array $params = [], $accessToken = null, $apiVersion = null)
    {
        $apiVersion = $apiVersion ?: $this->defaultApiVersion;
        $request = $this->request($method, $endpoint, $params, $accessToken, $apiVersion);

        return $this->lastResponse = $this->client->sendRequest($request);
    }


    /**
     * Instantiates a new PlayerLyncRequest entity.
     *
     * @param string                  $method
     * @param string                  $endpoint
     * @param array                   $params
     * @param AccessToken|string|null $accessToken
     * @param string|null             $apiVersion
     *
     * @return PlayerLyncRequest
     *
     * @throws PlayerLyncSDKException
     */
    public function request($method, $endpoint, array $params = [], $accessToken = null, $apiVersion = null)
    {
        $apiVersion = $apiVersion ?: $this->defaultApiVersion;

        return new PlayerLyncRequest(
            $this->app,
            $accessToken,
            $method,
            $endpoint,
            $params,
            $apiVersion
        );
    }

    /**
     * Factory to create PlayerLyncFiles's.
     *
     * @param string $pathToFile
     *
     * @return PlayerLyncFile
     *
     * @throws PlayerLyncSDKException
     */
    public function fileToUpload($pathToFile)
    {
        return new PlayerLyncFile($pathToFile);
    }


}