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

namespace PlayerLync\Exceptions;

use PlayerLync\PlayerLyncResponse;

/**
 * Class PlayerLyncResponseException
 *
 * @package PlayerLync
 */
class PlayerLyncResponseException extends PlayerLyncSDKException
{
    /**
     * @var PlayerLyncResponse The response that threw the exception.
     */
    protected $response;

    /**
     * @var array Decoded response.
     */
    protected $responseData;

    /**
     * Creates a PlayerLyncResponseException.
     *
     * @param PlayerLyncResponse     $response          The response that threw the exception.
     * @param PlayerLyncSDKException $previousException The more detailed exception.
     */
    public function __construct(PlayerLyncResponse $response, PlayerLyncSDKException $previousException = null)
    {
        $this->response = $response;
        $this->responseData = $response->getDecodedBody();

        $errorMessage = $this->get('message', 'Unknown error from PlayerLync API.');
        $errorCode = $this->get('code', -1);

        parent::__construct($errorMessage, $errorCode, $previousException);
    }

    /**
     * Checks isset and returns that or a default value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function get($key, $default = null)
    {
        if (isset($this->responseData['errors'][$key]))
        {
            return $this->responseData['errors'][$key];
        }

        return $default;
    }

    /**
     * A factory for creating the appropriate exception based on the response from PlayerLync API.
     *
     * @param PlayerLyncResponse $response The response that threw the exception.
     *
     * @return PlayerLyncResponseException
     */
    public static function create(PlayerLyncResponse $response)
    {
        $data = $response->getDecodedBody();

//        //oauth error is not standard API format, so check this first
//        $isAuthError = isset($data['error']) && isset($data['error_description']);
//
//        if ($isAuthError)
//        {
//            $code = 148002;
//            return new static($response, new PlayerLyncAuthenticationException($data['error_description'], $code));
//        }
        if ($data['errors']['code'] >= 148000 && $data['errors']['code'] <= 148010)
        {
            return new static($response, new PlayerLyncAuthenticationException($data['errors']['message'], $data['errors']['code']));
        }
        else
        {
            return new static($response, new PlayerLyncSDKException($data['errors']['message'], $data['errors']['code']));
        }
    }

    /**
     * Returns the HTTP status code
     *
     * @return int
     */
    public function getHttpStatusCode()
    {
        return $this->response->getHttpStatusCode();
    }

    /**
     * Returns the sub-error code
     *
     * @return int
     */
    public function getSubErrorCode()
    {
        return $this->get('error_subcode', -1);
    }

    /**
     * Returns the error type
     *
     * @return string
     */
    public function getErrorType()
    {
        return $this->get('type', '');
    }

    /**
     * Returns the raw response used to create the exception.
     *
     * @return string
     */
    public function getRawResponse()
    {
        return $this->response->getBody();
    }

    /**
     * Returns the decoded response used to create the exception.
     *
     * @return array
     */
    public function getResponseData()
    {
        return $this->responseData;
    }

    /**
     * Returns the response entity used to create the exception.
     *
     * @return PlayerLyncResponse
     */
    public function getResponse()
    {
        return $this->response;
    }
}
