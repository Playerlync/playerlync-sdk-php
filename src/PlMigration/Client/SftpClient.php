<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/26/18
 */

namespace PlMigration\Client;

use phpseclib\Net\SFTP;
use PlMigration\Exceptions\ClientException;

class SftpClient extends RemoteClient
{
    /** @var SFTP */
    protected $protocol;

    /** @var string */
    private $host;
    /** @var int int */
    private $port;
    /** @var string */
    private $username;
    /** @var string */
    private $password;

    private $options;

    /**
     * SftpClient constructor.
     * @param $host
     * @param $username
     * @param $password
     * @param int $port
     * @param array $options
     */
    public function __construct($host, $username, $password, $port = 22, $options = [])
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
        $this->options = $options;
    }

    /**
     * @param $localDestination
     * @param $remoteFile
     * @return mixed
     */
    protected function downloadFile($localDestination, $remoteFile)
    {
        $this->protocol->get($remoteFile, $localDestination);
    }

    /**
     * Check if the file exists
     * throws exception if the directory does not exist
     * @param $file
     * @return bool
     * @throws ClientException
     */
    protected function fileExists($file)
    {
        $fileInfo = pathinfo($file);
        $files = $this->getChildren($fileInfo['dirname'], false);
        return in_array($fileInfo['basename'], $files, true);
    }

    /**
     * @param $remoteFile
     * @param $localFile
     * @return mixed
     */
    protected function uploadFile($remoteFile, $localFile)
    {
        $result = $this->protocol->put($remoteFile, $localFile, SFTP::SOURCE_LOCAL_FILE);
        if($result !== true)
            throw new ClientException("Failed to upload file ($localFile) to ($remoteFile)");
        return $result;
    }

    /**
     * @throws ClientException
     */
    public function connect()
    {
        $this->protocol = new SFTP($this->host, $this->port);
        $this->protocol->sendKEXINITLast();
        $result = $this->protocol->login($this->username, $this->password);

        if(!$result)
        {
            throw new ClientException('Failed to login to SFTP server');
        }
    }

    public function close()
    {
        if($this->protocol !== null)
            $this->protocol = null;
    }

    protected function deleteFile($remoteFile)
    {
        $this->protocol->delete($remoteFile, false);
    }

    protected function moveFile($remoteFile, $remoteDestination)
    {
        $this->protocol->rename($remoteFile, $remoteDestination);
    }

    /**
     *
     * @param $directory
     * @param bool $includeDirectories
     * @return array
     * @throws ClientException
     */
    protected function getChildren($directory, $includeDirectories = true)
    {
        $this->protocol->setListOrder(true);
        $this->protocol->setListOrder('mtime', SORT_ASC);
        $files = $this->protocol->nlist($directory);
        if($files === false)
        {
            throw new ClientException('Directory does not exist');
        }

        if(!$includeDirectories)
        {
            foreach ($files as $key => $file)
            {
                if(pathinfo($file, PATHINFO_EXTENSION) === '')
                    unset($files[$key]);
            }
        }
        return $files;
    }
}