<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-03-14
 * Time: 15:27
 */

namespace PlMigration\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PlMigration\Exceptions\ClientException;

class PlapiOauthManager
{
    /**
     * @var Client
     */
    private $client;

    private $client_id;

    private $client_secret;

    private $username;

    private $password;

    private $grant_type;

    /**
     * JWT access token retrieved from oauth server
     *
     * @var string
     */
    private $accessToken;

    /**
     * member Id of authenticated user used
     *
     * @var string
     */
    private $memberId;

    /**
     * Unix Timestamp value of when the Oauth token will expire
     * @var float
     */
    private $expireTime;

    /**
     * Primary org id value to use for Primary-Org-Id header
     *
     * @var string
     */
    private $primaryOrgId;

    /**
     * PlapiOauthManager constructor.
     * @param Client $client
     * @param array $config
     */
    public function __construct($client, $config)
    {
        $this->client = $client;

        $this->client_id = $config['client_id'] ?? null;
        $this->client_secret = $config['client_secret'] ?? null;
        $this->username = $config['username'] ?? null;
        $this->password = $config['password'] ?? null;
        $this->grant_type = $config['grant_type'] ?? 'password';
    }

    /**
     * @param $config
     * @throws ClientException
     */
    public function authenticate()
    {
        try {
            $response = $this->client->request('POST', '/API/src/Scripts/OAuth/token.php', [
                'form_params' => [
                    'grant_type' => $this->grant_type,
                    'client_id' => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'username' => $this->username,
                    'password' => $this->password
                ]]);
        }
        catch (GuzzleException $e)
        {
            throw new ClientException('Unable to authenticate: '.$e->getMessage());
        }

        if($response->getStatusCode() !== 200)
        {
            throw new ClientException('Invalid status code response: '.$response->getStatusCode());
        }

        $jsonResponse = json_decode($response->getBody());
        if(!$jsonResponse)
        {
            throw new ClientException('Unable to decode JSON response. '.strip_tags($response->getBody()));
        }

        if(isset($jsonResponse->error_description))
        {
            throw new ClientException('Could not authenticate: ' . $jsonResponse->error_description);
        }


        $this->primaryOrgId = $jsonResponse->primary_org_id;
        $this->accessToken = $jsonResponse->access_token;
        $this->memberId = $jsonResponse->memberid;
        $this->expireTime = time() + $jsonResponse->expires_in;
    }

    /**
     * @return bool
     */
    public function needsToRenew()
    {
        return $this->expireTime - time() < 600;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function getPrimaryOrgId()
    {
        return $this->primaryOrgId;
    }

    public function getMemberId()
    {
        return $this->memberId;
    }
}