<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/26/18
 */

namespace PlMigration\Client;

use PlMigration\Exceptions\ClientException;

abstract class Client implements IClient
{
    protected $allowOverwrite = false;

    /**
     * Download a remote file from the server into a local destination
     * @param $remoteFile
     * @param $localDestination
     * @return bool
     * @throws ClientException
     */
    public function get($remoteFile, $localDestination)
    {
        if($this->remoteFileExists($remoteFile))
        {
            return $this->downloadFile($localDestination, $remoteFile);
        }

        throw new ClientException($remoteFile.' file does not exist.');
    }

    /**
     * Upload a local file into a remote location in the server.
     * If the file already exists, it will not be allowed to be overwritten by default
     * @param $localFile
     * @param $remoteLocation
     * @return bool
     * @throws ClientException
     */
    public function put($localFile, $remoteLocation)
    {
        if(substr($remoteLocation,-1) !== '/')
        {
            $remoteLocation .= '/';
        }

        $remoteLocation .= pathinfo($localFile, PATHINFO_BASENAME);

        if(!$this->allowOverwrite && $this->remoteFileExists($remoteLocation))
        {
            throw new ClientException('File already exists in the server');
        }

        return $this->uploadFile($remoteLocation, $localFile);
    }

    /**
     * @param $remoteFile
     * @throws ClientException
     */
    public function delete($remoteFile)
    {
        if($this->remoteFileExists($remoteFile))
        {
            $this->deleteFile($remoteFile);
        }
    }

    /**
     * @param $remoteFile
     * @param $remoteDestination
     * @return mixed|void
     * @throws ClientException
     */
    public function move($remoteFile, $remoteDestination)
    {
        if($this->remoteFileExists($remoteFile) && $this->remoteFileExists($remoteDestination))
        {
            $this->moveFile($remoteFile, $remoteDestination);
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param $localDestination
     * @param $remoteFile
     * @return mixed
     */
    abstract protected function downloadFile($localDestination, $remoteFile);

    /**
     * Check if the file exists
     * throws exception if the directory does not exist
     * @param $file
     * @return bool
     * @throws ClientException
     */
    abstract protected function remoteFileExists($file);

    /**
     * @param $remoteLocation
     * @param $localFile
     * @return mixed
     */
    abstract protected function uploadFile($remoteLocation, $localFile);

    /**
     * @param $remoteFile
     * @return mixed
     */
    abstract protected function deleteFile($remoteFile);

    /**
     * @param $remoteFile
     * @param $remoteDestination
     * @return mixed
     */
    abstract protected function moveFile($remoteFile, $remoteDestination);
}