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
use Gibbon\core\pagination ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage String Replacements';
	$trail->render($this);
		
	$this->render('default.flash');

    $search = '';
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }

    $this->h2('Search');
	
	$form = $this->getForm(null, array('q'=>'/modules/System Admin/stringReplacement_manage.php'), false);
	$form->setStyle('noIntBorder');
	
	$form->method = 'get';
	$form->table->style = 'width: 100%;';
	
	$el = $form->addElement('text', 'search', $search);
	$el->nameDisplay = 'Search for';
	$el->description = 'Original string, replacement string.';
	$el->setMaxLength(20);

	$el = $form->addElement('buttons', null);
	$el->addButton('clear', 'Clear Search', 'resetBtn');
	$el->addButton(null, 'Search', 'submitBtn');
	
	$el = $form->addElement('hidden', 'q', '/modules/System Admin/stringReplacement_manage.php');
	
	$form->render('noIntBorder', false);

	$this->linkTop(array('Add' => array('q' => '/modules/System Admin/stringReplacement_manage_edit.php', 'search' => $search, 'gibbonStringID' => 'Add')));

    $this->h3('View');

    //Set pagination variable
	$data = array();
	$where = '';
	$order = array('priority' => 'DESC', 'original' => 'ASC');
	if (! empty($search)) {
		$data = array('search1' => "%".$search."%", 'search2' => "%".$search."%");
		$where = "(`original` LIKE :search1 OR `replacement` LIKE :search2)";
	}


	$pagin = new pagination($this, $where, $data, $order, new \Gibbon\Record\stringReplacement($this));

    if ($pagin->get('total') < 1) {
        $this->displayMessage('There are no records to display.');
    } else {
        $pagin->printPagination('top', "&search=".$search);
		$el = new \stdClass();
		$el->action = true ;
		$this->render('stringReplacement.listStart', $el);

        foreach($pagin->get('results') as $row) {
           	$row->action = true ;
           	$row->search = $search ;
			$this->render('stringReplacement.listMember', $row);
        }
		$this->render('stringReplacement.listEnd', $el);
	
		$pagin->printPagination('bottom', "&search=".$search);
	}
}
