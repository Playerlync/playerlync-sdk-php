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

use PlayerLync\Authentication\OAuth2\AccessToken;
use PlayerLync\Exceptions\PlayerLyncSDKException;
use PlayerLync\FileUpload\PlayerLyncFile;
use PlayerLync\FileUpload\PlayerLyncVideo;
use PlayerLync\Http\RequestBodyMultipart;
use PlayerLync\Http\RequestBodyUrlEncoded;
use PlayerLync\Url\PlayerLyncUrlManipulator;

/**
 * Class PlayerLyncRequest
 *
 * @package PlayerLync
 */
class PlayerLyncRequest
{
    /**
     * @var PlayerLyncApp The PlayerLync app entity.
     */
    protected $app;

    /**
     * @var string|null The access token to use for this request.
     */
    protected $accessToken;

    /**
     * @var string The HTTP method for this request.
     */
    protected $method;

    /**
     * @var string The API endpoint for this request.
     */
    protected $endpoint;

    /**
     * @var array The headers to send with this request.
     */
    protected $headers = [];

    /**
     * @var array The parameters to send with this request.
     */
    protected $params = [];

    /**
     * @var array The files to send with this request.
     */
    protected $files = [];

    /**
     * @var string API version to use for this request.
     */
    protected $apiVersion;

    /**
     * Creates a new Request entity.
     *
     * @param PlayerLyncApp|null      $app
     * @param AccessToken|string|null $accessToken
     * @param string|null             $method
     * @param string|null             $endpoint
     * @param array|null              $params
     * @param string|null             $apiVersion
     */
    public function __construct(PlayerLyncApp $app = null, $accessToken = null, $method = null, $endpoint = null, array $params = [], $apiVersion = null)
    {
        $this->setApp($app);
        $this->setAccessToken($accessToken);
        $this->setMethod($method);
        $this->setEndpoint($endpoint);
        $this->setParams($params);
        $this->apiVersion = $apiVersion;

        if ($accessToken)
        {
            $this->setHeaders(['Authorization' => 'Bearer ' . $accessToken->getAccessToken()]);
        }
    }

    /**
     * Set the access token for this request.
     *
     * @param AccessToken|string
     *
     * @return PlayerLyncRequest
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * Return the access token for this request.
     *
     * @return string|null
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Return the access token for this request an an AccessToken entity.
     *
     * @return AccessToken|null
     */
    public function getAccessTokenEntity()
    {
        return $this->accessToken ? new AccessToken($this->accessToken) : null;
    }

    /**
     * Set the PlayerLyncApp entity used for this request.
     *
     * @param PlayerLyncApp|null $app
     */
    public function setApp(PlayerLyncApp $app = null)
    {
        $this->app = $app;
    }

    /**
     * Return the PlayerLyncApp entity used for this request.
     *
     * @return PlayerLyncApp
     */
    public function getApp()
    {
        return $this->app;
    }


    /**
     * Validate that an access token exists for this request.
     *
     * @throws PlayerLyncSDKException
     */
    public function validateAccessToken()
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken)
        {
            throw new PlayerLyncSDKException('You must provide an access token.');
        }
    }


    /**
     * Set the HTTP method for this request.
     *
     * @param string
     *
     * @return PlayerLyncRequest
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);
    }

    /**
     * Return the HTTP method for this request.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Validate that the HTTP method is set.
     *
     * @throws PlayerLyncSDKException
     */
    public function validateMethod()
    {
        if (!$this->method)
        {
            throw new PlayerLyncSDKException('HTTP method not specified.');
        }

        if (!in_array($this->method, ['GET', 'POST', 'PUT', 'DELETE']))
        {
            throw new PlayerLyncSDKException('Invalid HTTP method specified.');
        }
    }

    /**
     * Set the endpoint for this request.
     *
     * @param string
     *
     * @return PlayerLyncRequest
     *
     * @throws PlayerLyncSDKException
     */
    public function setEndpoint($endpoint)
    {
        $filterParams = array(); //future, add array of params you want to always remove from endpoint
        $this->endpoint = PlayerLyncUrlManipulator::removeParamsFromUrl($endpoint, $filterParams);

        return $this;
    }

    /**
     * Return the HTTP method for this request.
     *
     * @return string
     */
    public function getEndpoint()
    {
        // For batch requests, this will be empty
        return $this->endpoint;
    }

    /**
     * Generate and return the headers for this request.
     *
     * @return array
     */
    public function getHeaders()
    {
        $headers = static::getDefaultHeaders();

        return array_merge($this->headers, $headers);
    }

    /**
     * Set the headers for this request.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * Set the params for this request.
     *
     * @param array $params
     *
     * @return PlayerLyncRequest
     *
     * @throws PlayerLyncSDKException
     */
    public function setParams(array $params = [])
    {
        $params = $this->sanitizeFileParams($params);
        $this->dangerouslySetParams($params);

        return $this;
    }

    /**
     * Set the params for this request without filtering them first.
     *
     * @param array $params
     *
     * @return PlayerLyncRequest
     */
    public function dangerouslySetParams(array $params = [])
    {
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    /**
     * Iterate over the params and pull out the file uploads.
     *
     * @param array $params
     *
     * @return array
     */
    public function sanitizeFileParams(array $params)
    {
        foreach ($params as $key => $value)
        {
            if ($value instanceof PlayerLyncFile)
            {
                $this->addFile($key, $value);
                unset($params[$key]);
            }
        }

        return $params;
    }

    /**
     * Add a file to be uploaded.
     *
     * @param string         $key
     * @param PlayerLyncFile $file
     */
    public function addFile($key, PlayerLyncFile $file)
    {
        $this->files[$key] = $file;
    }

    /**
     * Removes all the files from the upload queue.
     */
    public function resetFiles()
    {
        $this->files = [];
    }

    /**
     * Get the list of files to be uploaded.
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Let's us know if there is a file upload with this request.
     *
     * @return boolean
     */
    public function containsFileUploads()
    {
        return !empty($this->files);
    }

    /**
     * Let's us know if there is a video upload with this request.
     *
     * @return boolean
     */
    public function containsVideoUploads()
    {
        foreach ($this->files as $file)
        {
            if ($file instanceof PlayerLyncVideo)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the body of the request as multipart/form-data.
     *
     * @return RequestBodyMultipart
     */
    public function getMultipartBody()
    {
        $params = $this->getPostParams();

        return new RequestBodyMultipart($params, $this->files);
    }

    /**
     * Returns the body of the request as Url-encoded.
     *
     * @return RequestBodyUrlEncoded
     */
    public function getUrlEncodedBody()
    {
        $params = $this->getPostParams();

        return new RequestBodyUrlEncoded($params);
    }

    /**
     * Generate and return the params for this request.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Only return params on POST and PUT requests.
     *
     * @return array
     */
    public function getPostParams()
    {
        if ($this->getMethod() === 'POST' || $this->getMethod() === 'PUT')
        {
            return $this->getParams();
        }

        return [];
    }

    /**
     * The API version used for this request.
     *
     * @return string
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * Generate and return the Url for this request.
     *
     * @return string
     */
    public function getUrl()
    {
        $this->validateMethod();

        $apiVersion = PlayerLyncUrlManipulator::forceSlashPrefix($this->apiVersion);
        $apiPlapiVersion = PlayerLyncUrlManipulator::forcePLAPIPrefix($apiVersion);
        $endpoint = PlayerLyncUrlManipulator::forceSlashPrefix($this->getEndpoint());

        $url = $apiPlapiVersion . $endpoint;

        if ($this->getMethod() !== 'POST' && $this->getMethod() !== 'PUT')
        {
            $params = $this->getParams();
            $url = PlayerLyncUrlManipulator::appendParamsToUrl($url, $params);
        }

        return $url;
    }

    /**
     * Return the default headers that every request should use.
     *
     * @return array
     */
    public static function getDefaultHeaders()
    {
        return [
            'User-Agent' => 'pl-php-' . PlayerLync::VERSION,
            'Accept-Encoding' => '*',
        ];
    }
}
