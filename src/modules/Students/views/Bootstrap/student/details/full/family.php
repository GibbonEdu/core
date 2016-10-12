<?php

$families = $el->student->getFamily();

if (count($families) == 0) {
	$this->displayMessage('There are no records to display.');
} else {
	
	$this->h2($el->header);
	foreach($families as $family){

		if ($this->getSecurity()->isActionAccessible('/modules/User Admin/family_manage.php')) 
			$this->linkTop(array_merge($el->links, array('edit' => array('q'=>'/modules/User Admin/family_manage_edit.php', 'gibbonFamilyID' => $family->getField('gibbonFamilyID')))));

		$this->render('family.details', $family);

		//Get adults
		$adults = $el->student->getFamilyAdults($family);

		foreach($adults as $adult) {
			$adult->relationship = $el->student->getAdultRelationship($adult, array('%1$sRelationship Unknown%2$s', array('<em>', '</em')));
			$this->render('family.adult', $adult);
		}

		//Get and Render siblings
		$this->render('family.siblings', $el->student->getSiblings($family));

	}
}
