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

use PlayerLync\Exceptions\PlayerLyncSDKException;
use PlayerLync\HttpClients\PlayerLyncCurlHttpClient;
use PlayerLync\HttpClients\PlayerLyncHttpClientInterface;

/**
 * Class PlayerLyncClient
 *
 * @package PlayerLync
 */
class PlayerLyncClient
{
    /**
     * @const string V2 PLAPI API Url.
     */
    const PLAPI_BASE_PATH = '/plapi';

    /**
     * @const int The timeout in seconds for a normal request.
     */
    const DEFAULT_REQUEST_TIMEOUT = 60;

    /**
     * @const int The timeout in seconds for a request that contains file uploads.
     */
    const DEFAULT_FILE_UPLOAD_REQUEST_TIMEOUT = 3600;

    /**
     * @const int The timeout in seconds for a request that contains video uploads.
     */
    const DEFAULT_VIDEO_UPLOAD_REQUEST_TIMEOUT = 7200;

    /**
     * @var \PlayerLync\HttpClients\PlayerLyncHttpClientInterface HTTP client handler.
     */
    protected $httpClientHandler;

    /**
     * @var string The host url for the PlayerLync API
     */
    protected $host;

    /**
     * @var string The PlayerLync API version to be prepended to endpoints
     */
    protected $apiVersion;

    /**
     * @var int The number of calls that have been made to API.
     */
    public static $requestCount = 0;

    /**
     * Instantiates a new PlayerLyncClient object.
     *
     * @param                                    $host
     * @param                                    $apiVersion
     * @param PlayerLyncHttpClientInterface|null $httpClientHandler
     */
    public function __construct($host, $apiVersion, PlayerLyncHttpClientInterface $httpClientHandler = null)
    {
        $this->host = $host;
        $this->apiVersion = $apiVersion;
        $this->httpClientHandler = $httpClientHandler ?: $this->detectHttpClientHandler();
    }

    /**
     * Sets the HTTP client handler.
     *
     * @param PlayerLyncHttpClientInterface $httpClientHandler
     */
    public function setHttpClientHandler(PlayerLyncHttpClientInterface $httpClientHandler)
    {
        $this->httpClientHandler = $httpClientHandler;
    }

    /**
     * Returns the HTTP client handler.
     *
     * @return PlayerLyncHttpClientInterface
     */
    public function getHttpClientHandler()
    {
        return $this->httpClientHandler;
    }

    /**
     * Detects which HTTP client handler to use.
     *
     * @return PlayerLyncHttpClientInterface
     */
    public function detectHttpClientHandler()
    {
        return new PlayerLyncCurlHttpClient();
        //return function_exists('curl_init') ? new PlayerLyncCurlHttpClient() : new PlayerLyncStreamHttpClient();
    }

    /**
     * Returns the host url for the PlayerLync API
     *
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Returns the base PLAPI URL.
     *
     * @return string
     */
    public function getBasePlayerLyncUrl()
    {
        return $this->host;
    }

    /**
     * Prepares the request for sending to the client handler.
     *
     * @param PlayerLyncRequest $request
     *
     * @return array
     */
    public function prepareRequestMessage(PlayerLyncRequest $request)
    {
        $url = $this->getBasePlayerLyncUrl() . $request->getUrl();

        // If we're sending files they should be sent as multipart/form-data
        if ($request->containsFileUploads())
        {
            $requestBody = $request->getMultipartBody();
            $request->setHeaders([
                'Content-Type' => 'multipart/form-data; boundary=' . $requestBody->getBoundary(),
            ]);
        }
        else
        {
            //if ($this->apiVersion)
            if (preg_match('/\/plapi\//',$url))
            {
                $requestBody = $request->getJsonEncodedBody();
                $request->setHeaders([
                    'Content-Type' =>  "application/json; charset=utf-8",
                ]);
            }
            else
            {
                $requestBody = $request->getUrlEncodedBody();
                $request->setHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]);

            }

        }

        return [
            $url,
            $request->getMethod(),
            $request->getHeaders(),
            $requestBody->getBody(),
        ];
    }

    /**
     * Makes the request to API and returns the result.
     *
     * @param PlayerLyncRequest $request
     *
     * @return PlayerLyncResponse
     *
     * @throws PlayerLyncSDKException
     */
    public function sendRequest(PlayerLyncRequest $request)
    {
        if (get_class($request) === 'PlayerLyncRequest')
        {
            $request->validateAccessToken();
        }

        list($url, $method, $headers, $body) = $this->prepareRequestMessage($request);

        // Since file uploads can take a while, we need to give more time for uploads
        $timeOut = static::DEFAULT_REQUEST_TIMEOUT;
        if ($request->containsFileUploads())
        {
            $timeOut = static::DEFAULT_FILE_UPLOAD_REQUEST_TIMEOUT;
        }
        elseif ($request->containsVideoUploads())
        {
            $timeOut = static::DEFAULT_VIDEO_UPLOAD_REQUEST_TIMEOUT;
        }

        // Should throw `PlayerLyncSDKException` exception on HTTP client error.
        // Don't catch to allow it to bubble up.
        $rawResponse = $this->httpClientHandler->send($url, $method, $body, $headers, $timeOut);

        static::$requestCount++;

        $returnResponse = new PlayerLyncResponse(
            $request,
            $rawResponse->getBody(),
            $rawResponse->getHttpResponseCode(),
            $rawResponse->getHeaders()
        );

        if ($returnResponse->isError())
        {
            throw $returnResponse->getThrownException();
        }

        return $returnResponse;
    }
}