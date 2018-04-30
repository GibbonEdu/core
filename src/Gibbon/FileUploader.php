<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon;

use Gibbon\Contracts\Database\Connection;
use Gibbon\Session;

/**
 * File Upload Class
 *
 * @version v14
 * @since   v14
 */
class FileUploader
{
    const FILE_SUFFIX_NONE = 0;
    const FILE_SUFFIX_INCREMENTAL = 1;
    const FILE_SUFFIX_ALPHANUMERIC = 2;

    /**
     * Gibbon\Contracts\Database\Connection
     */
    protected $pdo ;

    /**
     * Gibbon/session
     */
    protected $session ;

    /**
     * List of allowed file types from gibbonFileExtension table
     * @var  array
     */
    protected $fileExtensions;

    /**
     * Last error generates
     * @var  int
     */
    protected $errorCode = 0;

    /**
     * Should a suffix be added to filenames?
     * @var  bool
     */
    protected $fileSuffixType = self::FILE_SUFFIX_ALPHANUMERIC;

    /**
     * Internal hard-coded array of file types that should never be allowed
     * @var  array
     */
    protected static $illegalFileExtensions = array('js','htm','html','css','php','php3','php4','php5','php7','phtml','asp','jsp','py','svg');

    /**
     * @version  v14
     * @since    v14
     * @param    Connection  $pdo
     * @param    session     $session
     */
    public function __construct(Connection $pdo, Session $session)
    {
        $this->pdo = $pdo;
        $this->session = $session;
    }

    /**
     * Get the list of hard-coded illegal extensions.
     *
     * @version  v14
     * @since    v14
     * @return   array
     */
    public static function getIllegalFileExtensions()
    {
        return self::$illegalFileExtensions;
    }

    /**
     * Upload a file from a submitted form, checking file extensions and generating a randomized name.
     *
     * @version  v14
     * @since    v14
     * @param    string  $filename    Desired filename
     * @param    string  $sourcePath  Absolute path of the temp file to upload
     * @param    string  $destinationFolder  Relativeto the /uploads folder
     * @return   string|bool          Resulting path of the uploaded file, FALSE on failure.
     */
    public function upload($filename, $sourcePath, $destinationFolder = '')
    {
        $absolutePath = $this->session->get('absolutePath');

        // Trim and remove excess path info
        $filename = basename($filename);
        $destinationFolder = trim($destinationFolder, '/');

        // Check the existence of the temp file to upload
        if (empty($sourcePath) || !file_exists($sourcePath)) {
            $this->errorCode = UPLOAD_ERR_NO_FILE;
            return false;
        }

        // Validate the file extensions
        if (empty($filename) || !$this->isFileTypeValid($filename)) {
            $this->errorCode = UPLOAD_ERR_EXTENSION;
            return false;
        }

        // Generate a default folder based on date if one isn't provided
        if (empty($destinationFolder)) {
            $destinationFolder = $this->getUploadsFolderByDate();
        }

        // Create the destination folder if it doesn't exit
        if (is_dir($absolutePath.'/'.$destinationFolder) == false) {
            $folderCreated = mkdir($absolutePath.'/'.$destinationFolder, 0755, true);

            if (!$folderCreated) {
                $this->errorCode = UPLOAD_ERR_CANT_WRITE;
                return false;
            }
        }

        // Determine the uploaded file name and path
        $destinationName = $this->getRandomizedFilename($filename, $destinationFolder);
        $destinationPath = $absolutePath.'/'.$destinationFolder.'/'.$destinationName;

        // Perform the upload, return the relative uplaods path
        if (move_uploaded_file($sourcePath, $destinationPath)) {
            $this->errorCode = UPLOAD_ERR_OK;
            return $destinationFolder.'/'.$destinationName;
        } else {
            $this->errorCode = UPLOAD_ERR_CANT_WRITE;
            return false;
        }
    }

    /**
     * Convenience function for handling file uploads from $_FILES array. Also handles file upload errors from POST.
     *
     * @version  v14
     * @since    v14
     * @param    array  $file  Complete $_FILES['userfile'] array
     * @param    string $fileNameChange   Desired file name, minus extensions
     * @return   string|bool
     */
    public function uploadFromPost($file, $filenameChange = '')
    {
        // Check for empty data
        if (empty($file)) {
            return false;
        }

        // Pull any existing error code from the PHP upload
        $this->errorCode = (isset($file['error']))? $file['error'] : UPLOAD_ERR_OK;
        if ($this->errorCode != UPLOAD_ERR_OK) {
            return false;
        }

        // Get the file name and path for passing to upload method
        $filename = (isset($file['name']))? $file['name'] : '';
        $sourcePath = (isset($file['tmp_name']))? $file['tmp_name'] : '';

        // Optionally replace the filename, keeping the previous extension
        if (!empty($filenameChange)) {
            $filename = $filenameChange.mb_strrchr($filename, '.');
        }

        return $this->upload($filename, $sourcePath);
    }

    /**
     * Get an absolute uploads folder path based on UNIX timestamp.
     *
     * @version  v14
     * @since    v14
     * @param    string  $timestamp
     * @return   string|bool Returns the path, FALSE on failure.
     */
    public function getUploadsFolderByDate($timestamp = 0)
    {
        if (empty($timestamp)) $timestamp = time();

        return 'uploads/'.date('Y', $timestamp).'/'.date('m', $timestamp);
    }

    /**
     * Randomize the provided filename by adding an alphanumeric string and ensuring uniqueness.
     *
     * @version  v14
     * @since    v14
     * @param    string  $filename
     * @return   string|bool  Returns the filename, FALSE on failure.
     */
    public function getRandomizedFilename($filename, $destinationFolder)
    {
        if ($this->fileSuffixType == self::FILE_SUFFIX_NONE) {
            return $filename;
        }

        $extension = mb_substr(mb_strrchr(strtolower($filename), '.'), 1);

        $name = mb_substr($filename, 0, mb_strrpos($filename, '.'));
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);

        for ($count = 0; $count < 100; $count++) {
            if ($this->fileSuffixType == self::FILE_SUFFIX_INCREMENTAL) {
                $suffix = ($count > 0)? '_'.$count : '';
            } else {
                $suffix = '_'.randomPassword(16);
            }

            $randomizedFilename = $name.$suffix.'.'.$extension;

            if (!(file_exists($destinationFolder.'/'.$randomizedFilename))) {
                return $randomizedFilename;
            }
        }

        return false;
    }

    /**
     * Lazy load an array of the File Extensions from DB. Optionally loads specific types of extensions (accepts array or CSV list).
     *
     * @version  v14
     * @since    v14
     * @return   array
     */
    public function getFileExtensions($type = '')
    {
        if (!isset($this->fileExtensions) || !empty($type)) {
            $this->fileExtensions = array();

            if (!empty($type)) {
                $type = (is_array($type))? implode(',', $type) : $type;

                $data = array('types' => strtolower($type));
                $sql = "SELECT LOWER(extension) FROM gibbonFileExtension WHERE FIND_IN_SET(LOWER(type), :types) ORDER BY type, name";
            } else {
                $data = array();
                $sql = "SELECT LOWER(extension) FROM gibbonFileExtension ORDER BY type, name";
            }

            $result = $this->pdo->executeQuery($data, $sql);

            if ($result && $result->rowCount() > 0) {
                $fileExtensionsPreFilter = $result->fetchAll(\PDO::FETCH_COLUMN, 0);

                foreach ($fileExtensionsPreFilter as $extension) {
                    // Prevent illegal file extensions
                    if (!in_array($extension, $this->getIllegalFileExtensions())) {
                        array_push($this->fileExtensions, $extension);
                    }
                }
            }
        }

        return $this->fileExtensions;
    }

    /**
     * Get the valid extensions as CSV; helper method for validation fields.
     *
     * @version  v14
     * @since    v14
     * @return   array
     */
    public function getFileExtensionsCSV()
    {
        return implode(',', array_map(function ($str) { return "'.".$str."'"; }, $this->getFileExtensions()));
    }

    /**
     * Set the file extensions from an array. Overrides the list normally retrieved from the database.
     *
     * @version  v14
     * @since    v14
     * @param    array  $extensions
     */
    public function setFileExtensions($extensions)
    {
        if (empty($extensions) || !is_array($extensions)) return false;

        $this->fileExtensions = array();

        foreach ($extensions as $extension) {
            if (!in_array($extension, $this->getIllegalFileExtensions())) {
                array_push($this->fileExtensions, $extension);
            }
        }

        return true;
    }

    /**
     * Checks the extension of the filename provided against the list of valid extensions. Handle extensions without full filename.
     *
     * @version  v14
     * @since    v14
     * @param    string  $filename
     * @return   bool
     */
    public function isFileTypeValid($filename)
    {
        if (mb_stripos($filename, '.') !== false) {
            $extension = mb_substr(mb_strrchr(strtolower($filename), '.'), 1);
        } else {
            $extension = strtolower($filename);
        }

        if (in_array($extension, $this->getIllegalFileExtensions())) {
            return false;
        }

        return in_array($extension, $this->getFileExtensions());
    }

    public function setFileSuffixType($value)
    {
        $this->fileSuffixType = $value;
    }

    /**
     * Return the last error generated by the uploader.
     *
     * @version  v14
     * @since    v14
     * @return   int
     */
    public function getLastError()
    {
        switch ($this->errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $message = __('The uploaded file exceeds the maximum file size.');
                break;
            case UPLOAD_ERR_NO_FILE:
            case UPLOAD_ERR_PARTIAL:
                $message = __('The uploaded file was missing or only partially uploaded.');
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = __('System configuration is missing a temporary folder, or folder is unwritable.');
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = __('Failed to write file to disk.');
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = __('File upload prevented due to an invalid extension.');
                break;
            case UPLOAD_ERR_OK:
                $message = '';
                break;

            default:
                $message = __('Unknown upload error.');
                break;
        }

        return $message;
    }
}
