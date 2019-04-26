<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 2019-03-01
 * Time: 14:46
 */

namespace PlMigration\Client;

use PlMigration\Exceptions\ClientException;

/**
 * Class LocalClient
 * @package PlMigration\Client
 */
class LocalClient extends RemoteClient
{
    /**
     * Check if the file exists
     * throws exception if the directory does not exist
     * @param $file
     * @return bool
     * @throws ClientException
     */
    protected function fileExists($file)
    {
        return @file_exists($file);
    }

    /**
     * @param $file
     * @return mixed
     * @throws ClientException
     */
    protected function deleteFile($file)
    {
        if(!@unlink($file))
        {
            throw new ClientException('Unable to delete file: '.$file);
        }
    }

    /**
     * @param $file
     * @param $destination
     * @return mixed
     */
    protected function moveFile($file, $destination)
    {
        rename($file, $destination);
    }

    protected function copyFile($file, $destination)
    {
        copy($file, $destination);
    }

    /**
     * Retrieve all files that are inside the directory.
     * Throws error if the directory does not exist.
     * @param string $directory
     * @param bool $includeDirectories
     * @return array
     * @throws ClientException
     */
    protected function getChildren($directory, $includeDirectories = false)
    {
        if(!$this->fileExists($directory))
        {
            throw new ClientException('Directory does not exist: '. $directory);
        }
        $files = new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);

        $arrFiles = [];
        foreach($files as $file)
        {
            if(!$includeDirectories && $file->getType() === 'dir')
            {
                continue;
            }
            $arrFiles[] = $file->getFilename();
        }

        return $arrFiles;
    }

    /**
     * Create a connection to the remote server with the information provided
     */
    public function connect()
    {
    }

    /**
     * Close the connection to the remote server
     */
    public function close()
    {
    }

    /**
     * @param $localDestination
     * @param $remoteFile
     * @return mixed
     */
    protected function downloadFile($localDestination, $remoteFile)
    {
        $this->copyFile($remoteFile, $localDestination);
    }

    /**
     * @param $remoteLocation
     * @param $localFile
     * @return mixed
     */
    protected function uploadFile($remoteLocation, $localFile)
    {
        $this->copyFile($localFile, $remoteLocation);
    }
}