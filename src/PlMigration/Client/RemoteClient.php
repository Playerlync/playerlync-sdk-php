<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-03-01
 * Time: 14:42
 */

namespace PlMigration\Client;


use PlMigration\Exceptions\ClientException;

/**
 * Class RemoteClient
 * @package PlMigration\Client
 */
abstract class RemoteClient extends Client implements IRemoteClient
{
    protected $protocol;
    /**
     * @param $localDestination
     * @param $remoteFile
     * @return mixed
     */
    abstract protected function downloadFile($localDestination, $remoteFile);

    /**
     * @param $remoteLocation
     * @param $localFile
     * @return mixed
     */
    abstract protected function uploadFile($remoteLocation, $localFile);

    /**
     * Download a remote file from the server into a local destination
     * @param $remoteFile
     * @param $localDestination
     * @return string
     * @throws ClientException
     */
    public function download($remoteFile, $localDestination)
    {
        if($this->fileExists($remoteFile))
        {
            if(substr($localDestination,-1) !== '/')
            {
                $localDestination .= '/';
            }
            $localDestination .= pathinfo($remoteFile,PATHINFO_BASENAME);
            $this->downloadFile($localDestination, $remoteFile);
            return pathinfo($remoteFile, PATHINFO_BASENAME);
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
    public function upload($localFile, $remoteLocation)
    {
        if(substr($remoteLocation,-1) !== '/')
        {
            $remoteLocation .= '/';
        }

        $remoteLocation .= pathinfo($localFile, PATHINFO_BASENAME);

        if(!$this->allowOverwrite && $this->fileExists($remoteLocation))
        {
            throw new ClientException('File already exists in the server');
        }

        return $this->uploadFile($remoteLocation, $localFile);
    }

    public function __destruct()
    {
        if($this->protocol !== null)
            $this->close();
    }
}