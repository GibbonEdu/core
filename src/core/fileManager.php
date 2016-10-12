<?php
/**
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
/**
 */
namespace Gibbon\core;

use Gibbon\core\view ;

/**
 * File Management
 *
 * @version	6th August 2016
 * @since	4th July 2016
 * @author	Craig Rayner
 * @package	Gibbon
 */
class fileManager
{
	/**
	 * @var	Gibbon\view	
	 */
	private $view;

	/**
	 * @var	boolean
	 */
	private $ok;

	/**
	 * @var	string
	 */
	public $fileName;

	/**
	 * @var	string
	 */
	public $content;

	/**
	 * @var	string
	 */
	public $flash;

	/**
	 * Constructor
	 *
     * @version	4th July 2016
	 * @since	4th July 2016
	 * @param	Gibbon\view		$view
	 * @return	void
	 */
	public function __construct(view $view)
	{
		$this->view = $view;
		$this->ok = false;
		$this->fileName = '';
		$this->flash = 'flash';
		return ;
	}

	/**
	 * File Manage
	 *
     * @version	1st August 2016
	 * @since	4th July 2016
	 * @param	string		$sourceName	
	 * @param	string		$storeName	
	 * @return	boolean
	 */
	public function fileManage($sourceName, $storeName)
	{
		$this->ok = false;
		$this->fileName = '';
		if (! empty($_FILES[$sourceName]['tmp_name'])) {
			$path = $this->generatePath();
			$unique = false;
			$count = -1;
			do {
				$x = str_pad(strval(++$count), 3, '0', STR_PAD_LEFT) ;
				$this->fileName = $path . $storeName."_" . $x . strrchr($_FILES[$sourceName]['name'], '.');
				if ( ! (file_exists(GIBBON_ROOT .  ltrim($this->fileName, '/')))) $unique = true;
			} while (! $unique && $count < 100);
			if (! move_uploaded_file($_FILES[$sourceName]['tmp_name'], GIBBON_ROOT .  ltrim($this->fileName, '/'))) {
				$this->fileName = '';
				$this->view->insertMessage(array('The file %1$s was not saved successfully.', array($_FILES[$sourceName]['name'])), 'warning', false, $this->flash);
				return $this->ok = false;
			}
		}
		return $this->ok = true ;
	}

	/**
	 * generate Path
	 *
     * @version	6th July 2016
	 * @since	4th July 2016
	 * @return	string
	 */
	public function generatePath()
	{
		$time = strtotime('now');
		if (! is_dir(GIBBON_ROOT.'/uploads/'.date('Y', $time).'/'.date('m', $time))) 
			mkdir(GIBBON_ROOT.'/uploads/'.date('Y', $time).'/'.date('m', $time), 0775, true);

		return '/uploads/'.date('Y', $time).'/'.date('m', $time).'/';
	}

	/**
	 * valid Image
	 *
     * @version	13th July 2016
	 * @since	4th July 2016
	 * @param	integer/array		$width
	 * @param	integer		$height
	 * @param	float		$minRatio
	 * @param	float		$maxRatio
	 * @return	boolean
	 */
	public function validImage($width, $height = null, $minRatio = null, $maxRatio = null)
	{
		if (is_array($width))
		{
			$height = $width[1];
			$minRatio = isset($width[2]) ? $width[2] : null;
			$maxRatio = isset($width[3]) ? $width[3] : null;
			$width = $width[0];
		}
		if (is_null($minRatio)) $minRatio = $height/$width - 0.1;
		if (is_null($maxRatio)) $maxRatio = $height/$width + 0.1;
		if (! empty($this->fileName)) {
			$size1 = getimagesize(GIBBON_ROOT .  ltrim($this->fileName, '/'));
			$this->width = $size1[0];
			$this->height = $size1[1];
			$this->ratio = $this->width / $this->height ;
			if ($this->width > $width || $this->height > $height || $this->ratio < $minRatio || $this->ratio > $maxRatio) {
				unlink(GIBBON_ROOT .  ltrim($this->fileName, '/'));
				$this->fileName = '';
				$c = $name = pathinfo($this->fileName);
				$this->view->insertMessage(array('The image %1$s was not saved as it failed to meet size requirements.', array(basename($this->fileName))), 'warning', false, $this->flash);
				return $this->ok = false;
			}
		}
		return $this->ok = true ;
	}

	/**
	 * Extract ZIP File
	 *
     * @version	6th August 2016
	 * @since	1st August 2016
	 * @param	string		$zipFile
	 * @param	string		$sourceName	
	 * @param	string		$storeName	
	 * @return	boolean
	 */
	public function extractZIPFile($zipFile, $sourceName, $storeName)
	{
		$this->ok = false;
		$this->fileName = '';
		$path = $this->generatePath();
		$unique = false;
		$count = -1;
		$fileInfo = pathinfo($sourceName);
		do {
			$x = str_pad(strval(++$count), 3, '0', STR_PAD_LEFT);
			$this->fileName = $path . $storeName."_" . $x . '.' . $fileInfo['extension'];
			if (! file_exists(GIBBON_ROOT .  ltrim($this->fileName, '/'))) $unique = true;
		} while (! $unique && $count < 100);
        if (! copy('zip://'.$zipFile.'#'.$sourceName, GIBBON_ROOT . ltrim($this->fileName, '/'))) {
			$this->fileName = '';
			$this->view->insertMessage(array('The file %1$s was not saved successfully.', array($sourceName)), 'warning', false, $this->flash);
			return $this->ok = false;
		}
		return $this->ok = true ;
	}

	/**
	 * Extract File Content
	 *
     * @version	16th September 2016
	 * @since	16th September 2016
	 * @param	string		$sourceName	
	 * @return	boolean
	 */
	public function extractFileContent($sourceName)
	{
		$this->ok = false;
		$this->content = null;
		if (! empty($_FILES[$sourceName]['tmp_name'])) {
			$this->content = file_get_contents($_FILES[$sourceName]['tmp_name']);
			
			unlink($_FILES[$sourceName]['tmp_name']);
			
			$this->ok = true ;
			return $this->getContent();
		}
		return $this->ok ;
	}

	/**
	 * get Content
	 *
     * @version	16th September 2016
	 * @since	16th September 2016
	 * @param	string		$sourceName	
	 * @return	boolean
	 */
	public function getContent()
	{
		return $this->content;
	}
}

