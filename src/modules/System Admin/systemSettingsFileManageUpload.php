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

if ($this->getSecurity()->isActionAccessible('/modules/System Admin/systemSettings.php')) {

	$source = "https://gibbonedu.org/services/config/";
	
	$source = 'http://www.craigrayner.com/gibbon/';

	$name = isset($_GET['fileName']) ? $_GET['fileName'] : '';

	if (in_array($name, array('languages', 'currency', 'country', 'security', 'schoolData')))
	{
		$el = new stdClass();
		if (file_exists(GIBBON_ROOT . 'config/' . $name . ".yml"))
			$x = Yaml::parse(file_get_contents(GIBBON_ROOT . 'config/' . $name . ".yml"));
		$y =  Yaml::parse(downloadUrlToFile($source . $name . ".yml"));
		if ($y['version'] == $_GET['version'])
			$w = file_put_contents(GIBBON_ROOT . 'config/' . $name . ".yml", Yaml::dump($y));
		if ((bool)$w )
			$x = $y ;
		if (empty($x['version']))
			$x['version'] = 'Unknown';
		if (empty($y['version']))
			$y['version'] = '0';
		$el->status = 'Unknown';
		$el->rowNum = 'info';
		if ($x['version'] < $y['version'])
		{
			$el->status = 'Update Required: from '.$x['version']. ' to '.$y['version'].'.';
			$el->rowNum = 'warning';
			$updateRequired = true;
		}
		elseif ($x['version'] == $y['version'])
		{
			$el->status = $this->__('%1$s is the latest version.', array($x['version']));
			$el->rowNum = 'success';
		}
		$el->name = $name;
		$this->session->set('module', 'System Admin');
		$this->render('fileManage.listMember', $el);
		
	}
}


function downloadUrlToFile($url)
{   
    if(is_file($url)) {
        $content = file_get_contents($url); 
    } else {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		$content = curl_exec($curl);
		curl_close($curl);
    }
	return $content;
}