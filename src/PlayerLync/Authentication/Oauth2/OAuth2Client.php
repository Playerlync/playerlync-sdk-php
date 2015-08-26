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

namespace PlayerLync\Authentication\Oauth2;

use PlayerLync\Exceptions\PlayerLyncResponseException;
use PlayerLync\Exceptions\PlayerLyncSDKException;
use PlayerLync\PlayerLync;
use PlayerLync\PlayerLyncApp;
use PlayerLync\PlayerLyncClient;
use PlayerLync\PlayerLyncRequest;
use PlayerLync\PlayerLyncResponse;

/**
 * Class OAuth2Client
 *
 * @package PlayerLync
 */
class OAuth2Client
{
    /**
     * @const string The base authorization Url.
     */
    const BASE_AUTHORIZATION_URL = '/API/src/Scripts/OAuth/token.php';

    /**
     * The PlayerLyncApp entity.
     *
     * @var PlayerLyncApp
     */
    protected $app;

    /**
     * The PlayerLync client.
     *
     * @var PlayerLyncClient
     */
    protected $client;

    /**
     * The version of the API to use.
     *
     * @var string
     */
    protected $apiVersion;

    /**
     * The last request sent to API.
     *
     * @var PlayerLyncRequest|null
     */
    protected $lastRequest;


    /**
     * The access token returned from the OAuth request
     *
     * @var AccessToken
     */
    protected $accessToken;

    /**
     * Creates a new OAuth2Client entity.
     *
     * @param PlayerLyncApp    $app
     * @param PlayerLyncClient $client
     * @param string|null      $apiVersion The version of the PlayerLync API to use.
     */
    public function __construct(PlayerLyncApp $app, PlayerLyncClient $client, $apiVersion = null)
    {
        $this->app = $app;
        $this->client = $client;
        $this->apiVersion = $apiVersion ?: PlayerLync::DEFAULT_API_VERSION;
    }

    /**
     * Returns the last PlayerLyncRequest that was sent.
     * Useful for debugging and testing.
     *
     * @return PlayerLyncRequest|null
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * Get a valid access token for user.
     *
     * @param string $grantType
     *
     * @return AccessToken
     *
     * @throws PlayerLyncSDKException
     */
    public function getAccessToken($grantType = 'password')
    {
        $params = [
            'grant_type' => $grantType,
            'client_id' => $this->app->getClientId(),
            'client_secret' => $this->app->getClientSecret(),
            'username' => $this->app->getUsername(),
            'password' => $this->app->getPassword()
        ];

        return $this->requestAnAccessToken($params);
    }

    /**
     * Send a request to the OAuth endpoint.
     *
     * @param array $params
     *
     * @return AccessToken
     *
     * @throws PlayerLyncSDKException
     */
    protected function requestAnAccessToken(array $params)
    {
        //TODO: somewhere we need to be able to handle expired access tokens and refresh tokens
        $response = $this->sendRequestWithClientParams(OAuth2Client::BASE_AUTHORIZATION_URL, $params);
        $data = $response->getDecodedBody();

        if (!isset($data['access_token']))
        {
            throw new PlayerLyncSDKException('Access token was not returned from PlayerLync API.', 401);
        }

        return new AccessToken($data);
    }

    /**
     * Send a request to PlayerLync API for an access token.
     *
     * @param string      $endpoint
     * @param array       $params
     * @param string|null $accessToken
     *
     * @return PlayerLyncResponse
     *
     * @throws PlayerLyncResponseException
     */
    protected function sendRequestWithClientParams($endpoint, array $params, $accessToken = null)
    {
        $params += $this->getClientParams();

        $this->lastRequest = new PlayerLyncRequest(
            $this->app,
            $accessToken,
            'POST',
            $endpoint,
            $params,
            null,
            null //dont send api version in this request...oauth url is not versioned
        );

        return $this->client->sendRequest($this->lastRequest);
    }

    /**
     * Returns the client_* params for OAuth requests.
     *
     * @return array
     */
    protected function getClientParams()
    {
        return [
            'client_id' => $this->app->getClientId(),
            'client_secret' => $this->app->getClientSecret(),
        ];
    }
}
