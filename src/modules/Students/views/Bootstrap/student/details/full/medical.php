<?php
use Gibbon\trans ;
use Gibbon\helper ;

$medical = $el->student->getMedical();

if (! $el->student->validMedical) {
	if ($this->getSecurity()->isActionAccessible('/modules/Students/medicalForm_manage_edit.php', null, '')) 
		$this->linkTop(array_merge($el->links, array('add' => array('q'=>'/modules/Students/medicalForm_manage_edit.php', 'gibbonPersonMedicalID' => 'Add', 'gibbonPersonID' => $el->student->getField('gibbonPersonID')))));
	$this->h2($el->header);
	$this->displayMessage('There are no records to display.');
} else {
	if ($this->getSecurity()->isActionAccessible('/modules/Students/medicalForm_manage_edit.php', null, '')) 
		$this->linkTop(array_merge($el->links, array('edit' => array('q'=>'/modulesStudents/medicalForm_manage_edit.php', 'gibbonPersonMedicalID' => $medical->getField('gibbonPersonMedicalID'), 'gibbonPersonID' => $el->student->getField('gibbonPersonID')))));
	$this->h2($el->header);

	//Medical alert!

	//Get medical conditions
	$el->student->getMedicalConditions(); 
	$this->startWell();
	$this->h3('Basic Medical Details');
	$alert = $el->student->getHighestMedicalRisk();
	if (is_array($alert)) { ?>
		<div class='error' style='background-color: #<?php echo $alert['colourBG']; ?>; border-left: 4px solid #<?php echo $alert['colour']; ?>; color: #<?php echo $alert['colour']; ?>'>
		<?php $this->strong(array('This student has one or more %1$s risk medical conditions', array(strtolower($alert['name'])))); ?>
		</div><?php
	}
	?>

	
	<table class='smallIntBorder' cellspacing='0' style='width: 100%'>
        <tr>
            <td style='width: 33%; vertical-align: top'>
                <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Long Term Medication'); ?></span><br/><?php
                if (empty($el->student->getMedical()->getField('longTermMedication'))) {
                    echo '<em>'.trans::__('Unknown'); ?></td>em><?php
                } else {
                    echo $el->student->getMedical()->getField('longTermMedication');
                } ?>
            </td>
            <td style='width: 67%; vertical-align: top' colspan=2>
                <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Details'); ?></span><br/><?php
                echo helper::ynExpander($el->student->getMedical()->getField('longTermMedicationDetails')); ?>
            </td>
        </tr>
        <tr>
            <td style='width: 33%; padding-top: 15px; vertical-align: top'>
                <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Tetanus Last 10 Years?'); ?></span><br/><?php
                if (empty($el->student->getMedical()->getField('tetanusWithin10Years'))) {
                    echo '<em>'.trans::__('Unknown'); ?></em><?php
                } else {
                    echo helper::ynExpander($el->student->getMedical()->getField('tetanusWithin10Years'));
                }?>
            </td>
            <td style='width: 33%; padding-top: 15px; vertical-align: top'>
                <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Blood Type'); ?></span><br/><?php
                echo $el->student->getMedical()->getField('bloodType'); ?>
            </td>
            <td style='width: 33%; padding-top: 15px; vertical-align: top'>
                <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Medical Conditions?'); ?></span><br/><?php
                if (count($el->student->getMedicalConditions()) > 0) {
                    echo trans::__('Yes').'. '.trans::__('Details below.');
                } else {
                    echo trans::__('No');
                } ?>
            </td>
        </tr>
	</table><?php
	$this->endWell();

	foreach($el->student->getMedicalConditions() as $condition) {
		$this->startWell();
		if (is_array($condition->alert)) { 
			$this->h4(array('%1$s', array(trans::__($condition->getField('name'))." <span style='color: #".$condition->alert['colour']."'>(".trans::__($condition->alert['name']).' '.trans::__('Risk').')</span>'))); }?>
		<table class='smallIntBorder' cellspacing='0' style='width: 100%'><!-- ".__LINE__." -->
            <tr>
                <td style='width: 50%; vertical-align: top'>
                    <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Triggers'); ?></span><br/><?php
                    echo $condition->getField('triggers');?>
                </td>
                <td style='width: 50%; vertical-align: top' colspan=2>
                    <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Reaction'); ?></span><br/><?php
                    echo $condition->getField('reaction'); ?>
                </td>
            </tr>
            <tr>
                <td style='width: 33%; padding-top: 15px; vertical-align: top'>
                    <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Response'); ?></span><br/><?php
                    echo $condition->getField('response'); ?>
                </td>
                <td style='width: 33%; padding-top: 15px; vertical-align: top'>
                    <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Medication'); ?></span><br/><?php
                    echo $condition->getField('medication'); ?>
                </td>
            </tr>
            <tr>
                <td style='width: 33%; padding-top: 15px; vertical-align: top'>
                    <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Last Episode Date'); ?></span><br/><?php
                    if (! is_null($el->student->getField('dob')) && $el->student->getField('dob') != '0000-00-00')
                        echo helper::dateConvertBack($condition->getField('lastEpisode')); ?>
                </td>
                <td style='width: 33%; padding-top: 15px; vertical-align: top'>
                    <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Last Episode Treatment'); ?></span><br/><?php
                    echo $condition->getField('lastEpisodeTreatment'); ?>
                </td>
            </tr>
            <tr>
                <td style='width: 33%; padding-top: 15px; vertical-align: top' colspan='2'>
                    <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Comments'); ?></span><br/><?php
                    echo $condition->getField('comment'); ?>
                </td>
            </tr>
		</table><?php
		$this->endWell();
	}
}
