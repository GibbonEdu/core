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
use Symfony\Component\Yaml\Yaml ;
use stdClass ;

if (! $this instanceof view) die();

$standard = function($name) {
	return GIBBON_ROOT . 'config/' .$name . '.yml';
};
$local = function($name) {
	return GIBBON_ROOT . 'config/local/' .$name . '.yml';
};

if ($this->getSecurity()->isActionAccessible()) {

	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'System File Management';
	$trail->render($this);

	$this->render('default.flash');

	$fileCheckList = array('languages', 'currency', 'country', 'security', 'schoolData'); 
	sort($fileCheckList);
	$source = "https://gibbonedu.org/services/config/";

	$this->h3('File System Management');
	
	
	$this->render('fileManage.listStart');
	
	$updateRequired = false;
	foreach ($fileCheckList as $name)
	{
		$el = new stdClass();
		$el->name = $name;
		$this->render('fileManage.script', $el);
		
	}
	$this->render('fileManage.listEnd') ;

	$this->h3('Local Setting Variance');
	$this->paragraph('Report on any differences found between the standard configuration supplied by Gibbon and your local settings.  Local settings can be different from the standard configuration, due to local variations.');
	$this->displayMessage('You can download and upload local copies of your settings files, so that you can edit them.  When uploading, the file is chaecked to ensure that the correct YAML formatting is applied, and that all of the required values exist in the file. Changing values can significantly alter the way your Gibbon Site works.  Please ensure you get a copy of the original downloaded version before editing.', 'info');
	foreach($fileCheckList as $name)
	{
		$form = $this->getForm(null, array('q' => '/modules/System Admin/systemSettingsFileManageLocalProcess.php'), true, $name.'Form', true);
		$form->addElement('h4', null, $name);
		$ok = true ;
		$merge = false;
		if (! file_exists($standard($name)))
		{
			$form->addElement('error', null, 'The standard configuration file is not available!'); 	
			$ok = false;
		}
		if (! file_exists($local($name)))
		{
			if (file_exists($standard($name)))
			{
				file_put_contents($local($name), file_get_contents($standard($name)));
			}
			if (! file_exists($local($name)))
			{
				$form->addElement('error', null, 'The local configuration file is not available!'); 	
				$ok = false;
			}
			else
			{
				$form->addElement('info', null, 'The local configuration file was successfully created.'); 	
				$ok = false;
			}
		}
			
		if (file_exists($standard($name)) && file_exists($local($name)))
		{
			$s = file_get_contents($standard($name));
			$l = file_get_contents($local($name));
			if (md5($s) !== md5($l))
			{
				$s = Yaml::parse($s);
				$l = Yaml::parse($l);
				foreach($s as $key=>$value)
				{
					if (! isset($l[$key]))
					{
						$form->addElement('warning', null, array('The key "%1$s" is not available in your local configurations, and has been merged to your local configuration.', array($key)));
						$merge = true;
						$l[$key] = $value;
					} 
					elseif ($l[$key] != $value)
					{
						$form->addElement('warning', null, array('The key "%1$s" has a different value in your local configurations.', array($key)));
						$ok = false;
					}
					elseif ($l[$key] == $value)
					{
						unset($s[$key]);
					}
				}
				if (count($s) == 0 || (count($s) == 1 && isset($s['version'])))
					file_put_contents($local($name), file_get_contents($standard($name)));
				if ($merge) 
					file_put_contents($local($name), Yaml::dump($l));
			}

			$el = $form->addElement('button', 'Restore', 'Restore');
			$el->nameDisplay = 'Restore';
			$el->setButtonColour('blue');
			$el->onClickSubmit();
			$el->description = array('Restore the local %1$s configuration to Gibbon standard.', array($name));
			
			$el = $form->addElement('button', 'Download', 'Download');
			$el->nameDisplay = 'Download';
			$el->setButtonColour('green');
			$el->additional = ' onClick=\'window.open("'.$this->convertGetArraytoURL(array('q'=>'/modules/System Admin/systemSettingsFileManageLocalProcess.php', 'divert'=>true, 'Download' => 'Download', 'fileName' => $name)).'", "_self");\'';
			$el->description = array('Download the current local version of your %1$s file for editing.', array($name));
			
			$form->addElement('hidden', 'fileName', $name);
			$el = $form->addElement('file', 'file', 'Upload');	
			$el->onChangeSubmit();
			$el->nameDisplay = array('Upload %1$s', array($name));
			$el->description = array('Upload, test and save a new version of the %1$s local settings file.', array($name));
			$el->setFile('Must be a valid YAML File', 'yml');

		}
		if ($ok)
			$form->addElement('success', null, array('No problems where identified in the %1$s configuration file', array($name)));

		$form->render();
	}

}
