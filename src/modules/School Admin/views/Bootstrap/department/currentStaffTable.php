<?php if (count($el->staff) < 1) {
    $el->form->addElement('warning', null, "There are no staff allocated to this department!");
}
else { 
	$el->form->addElement('info', null, 'Selected staff will be removed from the department when the page is submitted.');
	foreach($el->staff as $person) { 
		$x = $el->form->addElement('checkbox', 'deleteStaff[]', $person->getField('gibbonDepartmentStaffID'));
		$x->nameDisplay = $person->formatName(true, true);
		$x->description = 'Role: '. $person->getField('role');
	} ?>
<?php
}
