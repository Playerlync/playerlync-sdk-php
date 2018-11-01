<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/9/18
 */

namespace PlMigration\Client;


use PlMigration\Exceptions\ClientException;

interface IClient
{
    /**
     * @param $localFile
     * @param $remoteDestination
     * @return mixed
     * @throws ClientException
     */
    public function put($localFile, $remoteDestination);

    /**
     * @param $remoteFile
     * @param $localDestination
     * @return mixed
     * @throws ClientException
     */
    public function get($remoteFile, $localDestination);

    /**
     * @throws ClientException
     */
    public function connect();

    public function close();
}