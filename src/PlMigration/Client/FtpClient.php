<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/9/18
 */

namespace PlMigration\Client;

use FtpClient\FtpException;
use PlMigration\Exceptions\ClientException;

class FtpClient implements IClient
{
    /** @var \FtpClient\FtpClient */
    private $protocol;
    /** @var string */
    private $host;
    /** @var int int */
    private $port;
    /** @var string */
    private $username;
    /** @var string */
    private $password;
    /** @var bool */
    protected $ssl = false;

    /**
     * FtpConstructor constructor.
     * @param string $host - host name of the ftp server
     * @param string $username - login username for the ftp connection
     * @param string $password - login password for the ftp connection
     * @param int $port - port number that the ftp is connected to (default port 21)
     */
    public function __construct($host, $username, $password, $port = 21)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Attempt to connect to the host and login
     * @throws ClientException
     */
    public function connect()
    {
        try
        {
            $this->protocol = new \FtpClient\FtpClient();
            $this->protocol->connect($this->host, $this->ssl, $this->port);
            $this->protocol->login($this->username, $this->password);
        }
        catch(FtpException $e)
        {
            throw new ClientException($e->getMessage());
        }
    }

    /**
     * Close connection
     */
    public function close()
    {
        $this->protocol->close();
    }

    /**
     * Upload a local file into a remote location in the server. If the file already exists, it will not be allowed to be
     * overwritten
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

        if($this->remoteFileExists($remoteLocation))
        {
            throw new ClientException('File already exists in the server');
        }

        return $this->protocol->put($remoteLocation, $localFile, FTP_BINARY);
    }

    /**
     * Download a remote file from the server into a local destination
     * @param $remoteFile
     * @param $localDestination
     * @return bool
     */
    public function get($remoteFile, $localDestination)
    {
        return $this->protocol->get($localDestination, $remoteFile, FTP_BINARY);
    }

    /**
     * @param $file
     * @return bool
     * @throws ClientException
     */
    private function remoteFileExists($file)
    {
        try
        {
            $list = $this->protocol->nlist(pathinfo($file,PATHINFO_DIRNAME));
        }
        catch(FtpException $e)
        {
            throw new ClientException($e->getMessage());
        }

        $found = false;
        foreach($list as $item)
        {
            if(strpos($item,$file) !== false)
            {
                $found = true;
                break;
            }
        }
        return $found;
    }
}