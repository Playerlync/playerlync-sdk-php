<?php


namespace PlMigration\Helper;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PlMigration\Exceptions\ClientException;

class PlayerlyncFileClient
{
    /**
     * @var Client
     */
    private $client;

    public function __construct($plapiClient)
    {
        $this->client = $plapiClient;
    }

    /**
     * @param $file
     * @return mixed
     * @throws ClientException
     */
    public function getSecureFile($file)
    {
        try
        {
            $response = $this->client->request('GET', 'securefiles/'.$file);
        }
        catch (GuzzleException $e)
        {
            throw new ClientException($e->getMessage());
        }

        if($response->getStatusCode() !== 200)
        {
            throw new ClientException('Invalid status code response: '.$response->getStatusCode());
        }

        return $response->getBody();
    }
}