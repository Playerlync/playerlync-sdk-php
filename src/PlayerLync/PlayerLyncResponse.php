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

use PlayerLync\Exceptions\PlayerLyncResponseException;
use PlayerLync\Exceptions\PlayerLyncSDKException;

/**
 * Class PlayerLyncResponse
 *
 * @package PlayerLync
 */
class PlayerLyncResponse
{
    /**
     * @var int The HTTP status code response from API.
     */
    protected $httpStatusCode;

    /**
     * @var array The headers returned from API.
     */
    protected $headers;

    /**
     * @var string The raw body of the response from API.
     */
    protected $body;

    /**
     * @var array The decoded body of the API response.
     */
    protected $decodedBody = [];

    /**
     * @var string The status of the API response
     */
    protected $status;

    /**
     * @var string The API action called within the API service
     */
    protected $apiAction;

    /**
     * @var int The page number of the current response
     */
    protected $page;

    /**
     * @var int The total number of pages available for the requested resource
     */
    protected $totalPages;

    /**
     * @var int The max number of records to the response would return
     */
    protected $limit;

    /**
     * @var int The number of records returned in the response
     */
    protected $items;

    /**
     * @var int The total number of records available for the requested resource
     */
    protected $totalItems;

    /**
     * @var string The API version of the endpoint
     */
    protected $apiVersion;

    /**
     * @var float The number of milliseconds it took to process the request
     */
    protected $responseTime;

    /**
     * @var array The response data
     */
    protected $data;

    /**
     * @var array|null The errors generated processing the request
     */
    protected $errors;

    /**
     * @var PlayerLyncRequest The original request that returned this response.
     */
    protected $request;

    /**
     * @var PlayerLyncSDKException The exception thrown by this request.
     */
    protected $thrownException;

    /**
     * Creates a new Response entity.
     *
     * @param PlayerLyncRequest $request
     * @param string|null       $body
     * @param int|null          $httpStatusCode
     * @param array|null        $headers
     */
    public function __construct(PlayerLyncRequest $request, $body = null, $httpStatusCode = null, array $headers = [])
    {
        $this->request = $request;
        $this->body = $body;
        $this->httpStatusCode = $httpStatusCode;
        $this->headers = $headers;

        $this->decodeBody();

    }

    /**
     * Convert the raw response into an array if possible.
     *
     * PlayerLync will return standard JSON responses, or nothing (but that would be a bug)
     */
    public function decodeBody()
    {
        $this->decodedBody = json_decode($this->body, true);

        if ($this->decodedBody === null)
        {
            $this->decodedBody = [];
            parse_str($this->body, $this->decodedBody);
        }

        if (!is_array($this->decodedBody))
        {
            $this->decodedBody = [];
        }

        $this->processDecodedBody();

        if ($this->errors)
        {
            $this->makeException();
        }
    }

    /**
     * Sets the properties of the PlayerLyncRepsonse from the standard PlayerLync API JSON response body
     */
    private function processDecodedBody()
    {
        if (is_array($this->decodedBody))
        {
            if (isset($this->decodedBody['status']))
            {
                $this->status = $this->decodedBody['status'];
            }

            if (isset($this->decodedBody['apiaction']))
            {
                $this->apiAction = $this->decodedBody['apiaction'];
            }

            if (isset($this->decodedBody['apiversion']))
            {
                $this->apiVersion = $this->decodedBody['apiversion'];
            }

            if (isset($this->decodedBody['page']))
            {
                $this->page = $this->decodedBody['page'];
            }

            if (isset($this->decodedBody['totalpages']))
            {
                $this->totalPages = $this->decodedBody['totalpages'];
            }

            if (isset($this->decodedBody['limit']))
            {
                $this->limit = $this->decodedBody['limit'];
            }

            if (isset($this->decodedBody['items']))
            {
                $this->items = $this->decodedBody['items'];
            }

            if (isset($this->decodedBody['totalitems']))
            {
                $this->totalItems = $this->decodedBody['totalitems'];
            }

            if (isset($this->decodedBody['responsetime']))
            {
                $this->responseTime = $this->decodedBody['responsetime'];
            }

            if (isset($this->decodedBody['data']))
            {
                $this->data = $this->decodedBody['data'];
            }

            if (isset($this->decodedBody['errors']) || isset($this->decodedBody['error']))
            {
                if (isset($this->decodedBody['errors']))
                {
                    $this->errors = $this->decodedBody['errors'];
                }
                else
                {
                    if (isset($this->decodedBody['error']))
                    {
                        //this is an OAuth error, so lets convert it to our standard error format
                        $this->decodedBody['errors'] = array('code' => 148002, 'domain' => null, 'reason' => $this->decodedBody['error'], 'message' => $this->decodedBody['error_description']);
                        unset($this->decodedBody['error']);
                        unset($this->decodedBody['error_description']);

                        $this->errors = $this->decodedBody;
                    }
                }

            }


        }
    }

    /**
     * Instantiates an exception to be thrown later.
     */
    public function makeException()
    {
        $this->thrownException = PlayerLyncResponseException::create($this);
    }

    /**
     * Return the original request that returned this response.
     *
     * @return PlayerLyncRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Return the PlayerLyncApp entity used for this response.
     *
     * @return PlayerLyncApp
     */
    public function getApp()
    {
        return $this->request->getApp();
    }

    /**
     * Return the access token that was used for this response.
     *
     * @return string|null
     */
    public function getAccessToken()
    {
        return $this->request->getAccessToken();
    }

    /**
     * Return the HTTP status code for this response.
     *
     * @return int
     */
    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

    /**
     * Return the HTTP headers for this response.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Return the raw body response.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Return the decoded body response.
     *
     * @return array
     */
    public function getDecodedBody()
    {
        return $this->decodedBody;
    }

    /**
     * Return the status of the API response
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Return the API action called within the API service
     *
     * @return string
     */
    public function getApiAction()
    {
        return $this->apiAction;
    }

    /**
     * Return the page number of the current response
     *
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Return the total number of pages available for the requested resource
     *
     * @return int
     */
    public function getTotalPages()
    {
        return $this->totalPages;
    }

    /**
     * Return the max number of records to the response would return
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Return the number of records returned in the response
     *
     * @return int
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Return the total number of records available for the requested resource
     *
     * @return int
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }

    /**
     * Return the version of the PlayerLync API that returned this response.
     *
     * @return string
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * Return the number of milliseconds it took to process the request
     *
     * @return float
     */
    public function getResponseTime()
    {
        return $this->responseTime;
    }

    /**
     * Return the response data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Return the errors generated processing the request
     *
     * @return array|null
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Returns true if API returned an error message.
     *
     * @return boolean
     */
    public function isError()
    {
        return isset($this->decodedBody['errors']);
    }

    /**
     * Throws the exception.
     *
     * @throws PlayerLyncSDKException
     */
    public function throwException()
    {
        throw $this->thrownException;
    }

    /**
     * Returns the exception that was thrown for this request.
     *
     * @return PlayerLyncSDKException|null
     */
    public function getThrownException()
    {
        return $this->thrownException;
    }


}
