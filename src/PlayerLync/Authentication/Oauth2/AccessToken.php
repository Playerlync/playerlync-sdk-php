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

/**
 * Class AccessToken
 *
 * @package PlayerLync\Authentication\Oauth2
 */
class AccessToken
{
    /**
     *
     * @var string The access_token string
     */
    protected $accessToken;

    /** @var integer|null The number of seconds in which access token will expire */
    protected $expiresIn;

    /** @var \DateTime|null The date / time the access token will expire */
    protected $expires;

    /** @var string The type of access token */
    protected $tokenType;

    /** @var string|null The refresh token string */
    protected $refreshToken;

    /** @var string The memberID associated with the access token */
    protected $memberId;

    /** @var boolean Specifies if the member associated with the access token has admin privileges on the server */
    protected $isAdmin;

    /** @var array The full data object returned from the token request */
    protected $data;

    /**
     * Creates a new AccessToken entity.
     *
     * @param array $data Other token data.
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;

        $this->accessToken = $data['access_token'];
        $this->expiresIn = $data['expires_in'];
        $this->tokenType = $data['token_type'];
        $this->refreshToken = $data['refresh_token'];


        if (isset($data['expires_in']))
        {
            $this->expires = new \DateTime();
            $this->expires->add(new \DateInterval(sprintf('PT%sS', $data['expires_in'])));
        }
//        if (isset($data['refresh_token'])) {
//            $this->refreshToken = new self($data['refresh_token'], 'refresh_token');
//        }
    }

    /**
     * Returns a bool indicating if the access token is expired or not
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->expires !== null && $this->expires->getTimestamp() < time();
    }

    /**
     * Returns the data / time the token expires
     *
     * @return \DateTime|null
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * Returns the full data object returned from the token request
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

//    public function getScope()
//    {
//        return isset($this->data['scope']) ? $this->data['scope'] : '';
//    }

    /**
     * Returns the access_token string
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Returns the refresh_token string
     *
     * @return string|null
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * Returns the type of access token
     *
     * @return string
     */
    public function getTokenType()
    {
        return $this->tokenType;
    }

}
