<?php
use Gibbon\trans ;

if ($this->getSecurity()->isActionAccessible('/modules/User Admin/user_manage.php')) 
	$this->linkTop(array_merge($el->links, array('edit' => array('q'=>'/modules/User Admin/user_manage_edit.php', 'gibbonFamilyID' => $el->student->getField('gibbonPersonID')))));

$this->h2($el->header);
$this->displayMessage('In an emergency, please try and contact the adult family members listed below first. If these cannot be reached, then try the emergency contacts below.', 'info');

$families = $el->student->getFamily();

if (count($families) == 0) {
	$this->displayMessage('There are no records to display.');
} else {
	
	$this->h3('Adult Family Members');
	foreach($families as $family){
		//Get adults
		$adults = $el->student->getFamilyAdults($family);

		foreach($adults as $adult) {
			$adult->relationship = $el->student->getAdultRelationship($adult, array('%1$sRelationship Unknown%2$s', array('<em>', '</em')));
			$this->render('family.adult', $adult);
		}
	}
}

$this->startWell();
$this->h3('Emergency Contacts');
?>
<table class='smallIntBorder' cellspacing='0' style='width: 100%'>
    <tr>
        <td style='width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo  trans::__('Contact 1'); ?></span><br/><?php
            echo $el->student->getField('emergency1Name');
            if (! empty($el->student->getField('emergency1Relationship'))) 
                 echo '('.$el->student->getField('emergency1Relationship').')'; ?>
        </td>
        <td style='width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo  trans::__('Number 1'); ?></span><br/><?php
            echo $el->student->getField('emergency1Number1'); ?>
        </td>
        <td style=width: 34%; 'vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo  trans::__('Number 2'); ?></span><br/><?php
            if (! empty($el->student->getField('website'))) 
                echo $el->student->getField('emergency1Number2'); ?>
        </td>
    </tr>
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo  trans::__('Contact 2'); ?></span><br/><?php
            echo $el->student->getField('emergency2Name');
            if (! empty($el->student->getField('emergency2Relationship'))) 
                 echo '('.$el->student->getField('emergency2Relationship').')'; ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo  trans::__('Number 1'); ?></span><br/><?php
            echo $el->student->getField('emergency2Number1'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
			<span style='font-size: 115%; font-weight: bold'><?php echo  trans::__('Number 2'); ?></span><br/><?php
			if ($el->student->getField('website') != '') 
				echo $el->student->getField('emergency2Number2'); ?>
        </td>
    </tr>
</table> 
<?php
$this->endWell();
