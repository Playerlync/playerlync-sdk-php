<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/9/18
 */

namespace PlMigration\Transfer;

use FtpClient\FtpClient;
use FtpClient\FtpException;
use PlMigration\Exceptions\TransferException;

class FtpTransfer implements ITransfer
{
    /** @var FtpClient */
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
     * FtpTransfer constructor.
     * @param $host - name to connect ftp to
     * @param $username - login username for the ftp connection
     * @param $password - login password for the ftp connection
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
     * @throws TransferException
     */
    public function connect()
    {
        try
        {
            $this->protocol = new FtpClient();
            $this->protocol->connect($this->host, $this->ssl, $this->port);
            $this->protocol->login($this->username, $this->password);
        }
        catch(FtpException $e)
        {
            throw new TransferException($e->getMessage());
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
     * Upload a local file into a remote location in the server
     * @param $localFile
     * @param $remoteLocation
     * @return bool
     * @throws TransferException
     */
    public function put($localFile, $remoteLocation)
    {
        if(substr($remoteLocation,-1) !== '/')
        {
            $remoteLocation .= '/';
        }
        if(!$this->directoryExists($remoteLocation))
        {
            throw new TransferException('"'.$remoteLocation.'" is not a directory');
        }

        $remoteLocation .= pathinfo($localFile, PATHINFO_BASENAME);
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

    private function directoryExists($location)
    {
        try
        {
            return $this->protocol->isDir($location);
        }
        catch (FtpException $e)
        {
            return false;
        }
    }
}