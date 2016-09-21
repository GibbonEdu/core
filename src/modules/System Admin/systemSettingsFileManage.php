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
use Gibbon\core\module ;
use Gibbon\core\trans ;
use Symfony\Component\Yaml\Yaml ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {

	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'System File Management';
	$trail->render($this);

	$this->render('default.flash');

	$fileCheckList = array('languages', 'currency', 'country', 'security'); 
	sort($fileCheckList);
	$source = "https://gibbonedu.org/services/config/";

	if (isset($this->post))
		foreach($fileCheckList as $name)
			if (isset($this->post['update-'.$name]))
				file_put_contents( GIBBON_CONFIG . $name . ".yml", file_get_contents($source . $name . ".yml") ); 
				
		$this->h3('File System Managemement');
		
		$form = $this->getForm(null, array('q'=>'/modules/System Admin/systemSettingsFileManage.php'), false);
		
		$form->addElement('raw', '', $this->renderReturn('fileManage.listStart'));
		
		$updateRequired = false;
		
		foreach ($fileCheckList as $name)
		{
			$params = new \stdClass();
			if (file_exists(GIBBON_ROOT . "config/" . $name . ".yml"))
				$x = Yaml::parse( file_get_contents(GIBBON_CONFIG . $name . ".yml") );
			if (@fopen($source . $name . ".yml", 'r'))  // Remote server Test
				$y = Yaml::parse( file_get_contents($source . $name . ".yml") );
			if (empty($x['version']))
				$x['version'] = 'Unknown';
			if (empty($y['version']))
				$y['version'] = '0';
			$params->status = 'Unknown';
			$params->rowNum = 'info';
			if ($x['version'] < $y['version'])
			{
				$params->status = 'Update Required: from '.$x['version']. ' to '.$y['version'].'.';
				$params->rowNum = 'warning';
				$updateRequired = true;
			}
			elseif ($x['version'] == $y['version'])
			{
				$params->status = $this->__('%1$s is the latest version.', array($x['version']));
				$params->rowNum = 'success';
			}
			$params->name = $name;
			$form->addElement('raw', '', $this->renderReturn('fileManage.listMember', $params));
		}
		$x = new \stdClass();
		$x->updateRequired = $updateRequired;
		$form->addElement('raw', '', $this->renderReturn('fileManage.listEnd', $x)) ;

		$form->render();
}
