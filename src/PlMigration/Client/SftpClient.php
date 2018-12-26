<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/26/18
 */

namespace PlMigration\Client;

use phpseclib\Net\SFTP;
use PlMigration\Exceptions\ClientException;

class SftpClient extends Client
{
    /** @var SFTP */
    private $protocol;

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
        $localDestination .= './'.pathinfo($remoteFile,PATHINFO_BASENAME);
        $this->protocol->get($remoteFile, $localDestination);
    }

    /**
     * Check if the file exists
     * throws exception if the directory does not exist
     * @param $file
     * @return bool
     * @throws ClientException
     */
    protected function remoteFileExists($file)
    {
        $fileInfo = pathinfo($file);
        $files = $this->protocol->nlist($fileInfo['dirname']);
        if($files === false)
        {
            throw new ClientException('Directory does not exist');
        }
        return in_array($fileInfo['basename'], $files, true);
    }

    /**
     * @param $remoteFile
     * @param $localFile
     * @return mixed
     */
    protected function uploadFile($remoteFile, $localFile)
    {
        $remoteFile .= '/'.pathinfo($remoteFile,PATHINFO_BASENAME);
        $this->protocol->put($remoteFile, $localFile);
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
            throw new ClientException('Incorrect login');
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
        $this->protocol->rename($remoteFile, $remoteDestination.'/'.pathinfo($remoteFile, PATHINFO_BASENAME));
    }
}