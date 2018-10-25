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

/**
 * Class PlayerLyncApp
 *
 * @package PlayerLync
 */
class PlayerLyncApp
{
    /**
     * @var string The client ID.
     */
    protected $clientId;

    /**
     * @var string The client secret.
     */
    protected $clientSecret;

    /**
     * @var string The username.
     */
    protected $username;

    /**
     * @var string The password.
     */
    protected $password;

    /**
     * @var string The primary org ID.
     */
    protected $primaryOrgId;

    /**
     * @var AccessToken The AccessToken
     */
    protected $accessToken;


    /**
     * Instantiates a new PlayerLyncApp object.
     *
     * @param $clientId
     * @param $clientSecret
     * @param $username
     * @param $password
     */
    public function __construct($clientId, $clientSecret, $username, $password, $primaryOrgId)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->username = $username;
        $this->password = $password;
        $this->primaryOrgId = $primaryOrgId;
    }

    /**
     * Returns the client ID.
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Returns the client secret.
     *
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * Returns the username.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Returns the password.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Returns the primary org ID.
     *
     * @return string
     */
    public function getPrimaryOrgId()
    {
        return $this->primaryOrgId;
    }

    /**
     * Returns the AccessToken
     *
     * @return AccessToken
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
}