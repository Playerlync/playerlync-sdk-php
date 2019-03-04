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
     * Check if the file exists
     * throws exception if the directory does not exist
     * @param $file
     * @return bool
     * @throws ClientException
     */
    abstract protected function fileExists($file);

    /**
     * @param $file
     * @return mixed
     */
    abstract protected function deleteFile($file);

    /**
     * @param $file
     * @param $destination
     * @return mixed
     */
    abstract protected function moveFile($file, $destination);

    /**
     * Retrieve all files that are inside the directory.
     * Throws error if the directory does not exist.
     * @param string $directory
     * @param bool $includeDirectories
     * @return array
     * @throws ClientException
     */
    abstract protected function getChildren($directory, $includeDirectories = false);

    /**
     * Retrieve a list of files found in a specified directory and filter by a specific file pattern if provided
     * @param string $directory file path to get information from
     * @param string $filePattern file pattern (files-start-wit*)
     * @return array
     * @throws ClientException
     */
    public function getDirectoryFiles($directory, $filePattern = null)
    {
        $files = $this->getChildren($directory);

        if($filePattern !== null)
        {
            $filePattern = substr($filePattern, 0, -1);
        }

        foreach($files as $key => $file)
        {
            if(!empty($filePattern) && strpos($file,$filePattern) !== 0)
            {
                unset($files[$key]);
            }
        }

        return $files;
    }

    /**
     * @param $file
     * @throws ClientException
     */
    public function delete($file)
    {
        if($this->fileExists($file))
        {
            $this->deleteFile($file);
        }
    }

    /**
     * @param $file
     * @param $destination
     * @return mixed|void
     * @throws ClientException
     */
    public function move($file, $destination)
    {
        if($this->fileExists($file) && $this->fileExists($destination))
        {
            $destination .= '/'.pathinfo($file, PATHINFO_BASENAME);

            $this->moveFile($file, $destination);
        }
    }
}