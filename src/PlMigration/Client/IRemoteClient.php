<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-03-01
 * Time: 13:59
 */

namespace PlMigration\Client;


use PlMigration\Exceptions\ClientException;

interface IRemoteClient
{
    /**
     * Upload a file to the directory desired.
     *
     * @param $localFile
     * @param $remoteDestination
     * @return mixed
     * @throws ClientException
     */
    public function upload($localFile, $remoteDestination);

    /**
     * Download a remote file to a local location
     * @param $remoteFile
     * @param $localDestination
     * @return mixed
     * @throws ClientException
     */
    public function download($remoteFile, $localDestination);

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