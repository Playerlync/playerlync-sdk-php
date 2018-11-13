<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/9/18
 */

namespace PlMigration\Client;


use PlMigration\Exceptions\ClientException;

/**
 * Interface IClient
 * @package PlMigration\Client
 *
 */
interface IClient
{
    /**
     * Upload a file to the directory desired.
     *
     * @param $localFile
     * @param $remoteDestination
     * @return mixed
     * @throws ClientException
     */
    public function put($localFile, $remoteDestination);

    /**
     * Download a remote file to a local location
     * @param $remoteFile
     * @param $localDestination
     * @return mixed
     * @throws ClientException
     */
    public function get($remoteFile, $localDestination);

    /**
     * Create a connection to the remote server with the information provided
     * @throws ClientException
     */
    public function connect();

    /**
     * Close the connection to the remote server
     */
    public function close();
}