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

use Gibbon\core\view ;
use Gibbon\core\trans ;
use Gibbon\Record\theme ;

if (! $this instanceof view) die();

$mf = new Functions\functions($this);

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage Themes';
	$trail->render($this);
	
	$this->h2('Manage Theme');
	$this->render('default.flash');
	//Get themes from database, and store in an array
	$themeObj = new theme($this);
	$themesSQL = $themeObj->findAll("SELECT * FROM gibbonTheme ORDER BY name", array(), '_', 'name');
	foreach ($themesSQL as $q=>$w)
		$themesSQL[$q]->setField('status', 'orphaned');

	//Get list of modules in /modules directory
	$themesFS = glob(GIBBON_ROOT .'src/themes/*' , GLOB_ONLYDIR);
	
	$this->displayMessage($this->__('To install a theme, upload the theme folder to %1$s on your server and then refresh this page. After refresh, the theme should appear in the list below: use the install button in the Actions column to set it up.', array("<strong><u>" . GIBBON_ROOT . "src/themes/</u></strong>")), 'info');
	
	if (count($themesFS)<1) {
		$this->displayMessage("There are no records to display.") ;
	}
	else {
		
		$form = $this->getForm(null, array('q'=>'/modules/System Admin/theme_manageProcess.php'), true );
				
		$el = new \stdClass();
		$el->action = true ;
		$form->addElement('raw', '', $this->renderReturn('theme.listStart', $el));
						
		foreach ($themesFS AS $themesFS) {
			$themeName = substr($themesFS, strlen(GIBBON_ROOT . 'src/themes/')) ;
			if (isset($themesSQL[$themeName])) 
				$el = $themesSQL[$themeName];
			else {
				$el = new theme($this);
				$el->defaultRecord();
			}
			$el->setField('status', "present") ;
			
			$el->themeName = $themeName ;
			$el->installed = true;
			$el->action = true;
			$el->themeVersion = $mf->getThemeVersion($themeName) ;
			if (! isset($themesSQL[$themeName])) {
				$el->installed = false;
				$el->rowNum="info" ;
			}
			$form->addElement('raw', '', $this->renderReturn('theme.listMember', $el));
		}
		$form->addElement('raw', '', $this->renderReturn('theme.listEnd', $form));
		$form->render();
		
	}
	
	//Find and display orphaned themes
	$orphans = false ;
	foreach($themesSQL AS $themeSQL) {
		if ($themeSQL->getField('status')=="orphaned") {
			$orphans = true ;
		}
	}

	if ($orphans) {
		$this->render('theme.orphanStart');
		foreach($themesSQL AS $themeSQL) {
			if ($themeSQL->getField('status')=="orphaned") {
				$el = new \stdClass();
				$el->themeName = $themeSQL->getField("name") ;
				$el->themeID = $themeSQL->getField("gibbonThemeID") ;
				$this->render('theme.orphanMember', $el);
			}
		}
		$this->render('theme.orphanEnd');
	}
}
