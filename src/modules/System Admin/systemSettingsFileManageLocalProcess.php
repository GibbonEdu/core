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

namespace Module\System_Admin ;

use Gibbon\core\view;
use Gibbon\core\fileManager;
use Symfony\Component\Yaml\Yaml ;
use Symfony\Component\Yaml\Exception\ParseException ;
use stdClass ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible('/modules/System Admin/systemSettings.php')) {

	$fileName = isset($_POST['fileName']) ? $_POST['fileName'] : '';
	$fileName = isset($_GET['fileName']) ? $_GET['fileName'] : $fileName;

	$url = array('q'=>'/modules/System Admin/systemSettingsFileManage.php');
	if (! in_array($fileName, array('languages', 'currency', 'country', 'security', 'schoolData')))
	{
		$this->insertMessage('return.error.1');
		$this->redirect($url);
	}
	if (isset($_GET['Download']) && $_GET['Download'] === 'Download')
	{
		//Download File
		if (file_exists(GIBBON_ROOT . 'config/local/' . $fileName . '.yml'))
		{
			$content = file_get_contents(GIBBON_ROOT . 'config/local/' . $fileName . '.yml');
			header('Content-type: application/octet-stream');
			header('Content-Disposition: attachment; filename="' . $fileName . '.yml"');
			header("Expires: 0");
			header('Pragma: public');
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private", false);
			header('Content-Length: ' . filesize(GIBBON_ROOT . 'config/local/' . $fileName . '.yml'));
			echo $content;
			die();
		}
		else
		{
			$this->insertMessage(array('The %1$s local configuration file was not available to download.', array($this->__($fileName))));
			$this->redirect($url);
		}
	}
	elseif (! empty($_FILES['file']['name']) && $_FILES['file']['error'] == 0)
	{
		//Upload and test, then save. 
		$fm = new fileManager($this);
		$x = $fm->extractFileContent('file');
		try
		{
			$t = Yaml::parse($x);
		}
		catch ( ParseException $e)
		{
			$this->insertMessage($e->getMessage());
			$this->redirect($url);
		}
		if (empty($t['fileName']) || $t['fileName'] !== $fileName)
		{
			$this->insertMessage(array('The content of the supplied file does not meet the requirements of %1$s configuration file!', array($this->__($fileName))));
			$this->redirect($url);
		}
		if (file_exists(GIBBON_ROOT . 'config/' . $fileName . '.yml'))
		{
			$s = Yaml::parse(file_get_contents(GIBBON_ROOT . 'config/' . $fileName . '.yml'));
			foreach($s as $key=>$value)
				if (! isset($t[$key]))
				{
					$this->insertMessage(array('The content of the supplied file does not meet the requirements of %1$s configuration file! The %2$s key is missing.', array($this->__($fileName), $key)));
					$this->redirect($url);
				}
		}
		file_put_contents(GIBBON_ROOT . 'config/local/' . $fileName . '.yml', $x);
		$this->insertMessage(array('The %1$s local configuration file was successfully replaced.',  array($this->__($fileName))), 'success');
		$this->redirect($url);
	}
	elseif (isset($_POST['Restore']) && $_POST['Restore'] === 'Restore')
	{
		//Copy standard over local.
		file_put_contents(GIBBON_ROOT . 'config/local/' . $fileName . '.yml', file_get_contents(GIBBON_ROOT . 'config/' . $fileName . '.yml'));
		$this->insertMessage(array('The %1$s local configuration file was successfully restored.',  array($this->__($fileName))), 'success');
		$this->redirect($url);
	}
}
