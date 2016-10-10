<?php if (count($el->staff) < 1) {
    $this->displayMessage("There are no staff allocated to this department!", 'warning');
}
else { ?>
    <table cellspacing='0' style='width: 100%'>
        <?php $el->form->addElement('info', null, 'Selected staff will be removed from the department when the page is submitted.');
		foreach($el->staff as $person) { 
			$x = $el->form->addElement('checkbox', 'deleteStaff[]', $person->getField('gibbonDepartmentStaffID'));
			$x->nameDisplay = $person->formatName(true, true);
			$x->description = 'Role: '. $person->getField('role');
        } ?>
    </table> <?php
}
?>
