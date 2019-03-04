<?php
/**
 * Created by PhpStorm.
 * User: mloayza-auqui
 * Date: 10/9/18
 */

namespace PlMigration\Client;


use PlMigration\Exceptions\ClientException;

/**
 * Interface IClient
 * @package PlMigration\Client
 *
 */
interface IClient
{
    /**
     * Delete a file in the remote server
     * @param $file
     * @throws ClientException
     */
    public function delete($file);

    /**
     * Move a remote file from one spot in the server to another.
     * @param $file
     * @param $destination
     * @return mixed
     */
    public function move($file, $destination);

    /**
     * Retrieve a list of files found in a specified directory and filter by a specific file pattern if provided
     * @param string $directory file path to get files from in
     * @param string $filePattern file pattern (files-start-wit*)
     * @return array
     * @throws ClientException
     */
    public function getDirectoryFiles($directory, $filePattern = null);
}