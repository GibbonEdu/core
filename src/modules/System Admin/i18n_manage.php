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
use Gibbon\core\module ;
use Gibbon\core\trans ;
use Symfony\Component\Yaml\Yaml ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Language Settings';
	$trail->render($this);

    $this->render('default.flash');
	$languages = Yaml::parse( file_get_contents(GIBBON_ROOT . "config/local/languages.yml") );

    $this->displayMessage('Inactive languages are not yet ready for use within the system as they are still under development. They cannot be set to default, nor selected by users.', 'info');

    if (count($languages) < 1) {
        $this->displayError('There are no records to display.');
    } else { 
		$params = new \stdClass();
    	$this->render('i18n.listStart', $params);
        foreach($languages['languages'] as $i18nObj)
			$this->render('i18n.listMember', $i18nObj);
		$this->render('i18n.listEnd');
    }
}
