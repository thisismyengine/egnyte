<?php

namespace Egnyte\Model;

use Egnyte\Client as Client;
use Egnyte\Http\Request as Request;
use Egnyte\Http\Response as Response;

class File
{
    protected $request;
    protected $curl;

    public function __construct(Client $client)
    {
        $this->request = $client->request;
        $this->curl = $client->curl;
    }

    /**
     * Get metadata for specified path, eg. file/directory.
     *
     * @param string $path The full path to the remote file/directory
     *
     * @return Egnyte\Httpd\Response Response object
     */
    public function metadata($path)
    {
        return $this->request->get('/fs'.Request::path_encode($path));
    }

    /**
     * Move a file/directory.
     *
     * @param string $path        The full path to the remote file/directory
     * @param string $destination Full absolute destination path of file/directory
     * @param string $permissions Permissions of moved file or directory (NULL/keep_original/inherit_from_parent)
     *
     * @return Egnyte\Httpd\Response Response object
     */
    public function move($path, $destination, $permissions = null)
    {
        $params = [
            'action' => 'move',
            'destination' => $destination,
            'permissions' => $permissions,
        ];

        return $this->request->post_json('/fs'.Request::path_encode($path), $params);
    }

    /**
     * Delete a file/directory.
     *
     * @param string $path The full path to the remote file/directory
     *
     * @return Egnyte\Httpd\Response Response object
     */
    public function delete($path)
    {
        return $this->request->delete('/fs'.Request::path_encode($path));
    }

    /**
     * Upload a file to Egnyte.
     *
     * @param string $remote_path   Remote upload directory
     * @param string $file_name     Target file name
     * @param string $file_contents Binary contents of the file
     *
     * @return Egnyte\Httpd\Response Response object
     */
    public function upload($remote_path, $file_name, $file_contents)
    {
        // path names are passed in the URL, so they need encoding
        $path = Request::path_encode($remote_path.'/'.$file_name);

        // set a content type for the upload
        $this->curl->setHeader('Content-Type', 'application/octet-stream');

        $response = $this->request->post('/fs-content'.$path, $file_contents, [
            400 => 'Missing parameters, file filtered out, e.g. .tmp file or file is too large (>100 MB)',
            401 => 'User not authorized',
            403 => 'Not enough permissions/forbidden file upload location, e.g. /, /Shared, /Private etc.',
        ]);

        return $response;
    }

    /**
     * Create a new directory.
     *
     * @param string $parent_directory Parent directory
     * @param string $directory_name   Name of new directory
     *
     * @return Egnyte\Httpd\Response Response object
     */
    public function create_directory($parent_directory, $directory_name)
    {
        // path names are passed in the URL, so they need encoding
        $path = Request::path_encode($parent_directory.'/'.$directory_name);

        return $this->request->post_json('/fs'.$path, ['action' => 'add_folder'], [
            403 => 'User does not have permission to create directory',
            405 => 'A directory with the same name already exists',
        ]);
    }

    /**
     * Move function alias.
     */
    public function mv()
    {
        return call_user_func_array('self::move', func_get_args());
    }

    /**
     * Delete function alias.
     */
    public function rm()
    {
        return call_user_func_array('self::delete', func_get_args());
    }

    /**
     * Create directory function alias.
     */
    public function mkdir()
    {
        return call_user_func_array('self::create_directory', func_get_args());
    }
}
