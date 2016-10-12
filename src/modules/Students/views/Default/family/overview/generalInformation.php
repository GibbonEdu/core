<?php 
use Gibbon\trans ;
$this->startWell(); 
$this->h3('General Information');
?>
<table class='smallIntBorder' cellspacing='0' style='width: 100%'>
    <tr>
        <td style='width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Preferred Name') ?></span><br/>
            <?php echo $el->formatName(); ?>
        </td>
        <td style='width: 34%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Official Name') ?></span><br/>
            <?php echo $el->getField('officialName'); ?>
        </td>
        <td style='width: 34%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Name In Characters') ?></span><br/>
            <?php echo $el->getField('nameInCharacters'); ?>
        </td>
    </tr>
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Year Group') ?></span><br/><?php
            if (! empty($el->getField('gibbonYearGroupID'))) {
                echo trans::__($el->getDetailsOfPerson('YearGroup', 'name'));
                $dayTypeOptions = $this->config->getSettingByScope('User Admin', 'dayTypeOptions');
                if (! empty($dayTypeOptions)) {
                     echo '('.$el->getField('dayType').')';
                }
            } ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
        <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Roll Group') ?></span><br/><?php
            if (! empty($el->getField('gibbonRollGroupID'))) {
                if ($this->getSecurity()->isActionAccessible('/modules/Roll Groups/rollGroups_details.php')) { ?>
                    <a href='<?php echo GIBBON_URL; ?>index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID=<?php echo $el->getField('gibbonRollGroupID') ?>'><?php echo $el->getDetailsOfPerson('RollGroup', 'name') ?></a><?php
                } else {
                    echo $el->getDetailsOfPerson('RollGroup', 'name');
                }
                $primaryTutor = $el->getDetailsOfPerson('RollGroup', 'gibbonPersonIDTutor');
            } ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
        <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Tutors') ?></span><br/><?php
            if (isset($primaryTutor)) {
                foreach ($el->tutors as $tutor) {
                    if ($this->getSecurity()->isActionAccessible('/modules/Staff/staff_view_details.php')) { ?>
                        <a href='<?php echo GIBBON_URL; ?>index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID=<?php echo $el->getField('gibbonPersonID') ?>'><?php echo $tutor->formatName(false, true) ?></a><?php
                    } else {
                        echo $tutor->formatName();
                    }
                    if ($tutor->getField('gibbonPersonID') == $primaryTutor && count($el->tutors) > 1) {
                         echo '('.trans::__('Main Tutor').')' ;
                    } ?>
                    <br/><?php
                }
            }?>
        </td>
    </tr>
    <tr>
    	<td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Username') ?></span><br/>
            <?php echo $el->getField('username'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Age') ?></span><br/><?php
            if (! is_null($el->getField('dob')) && $el->getField('dob') != '0000-00-00') {
                echo Gibbon\helper::getAge(Gibbon\helper::dateConvertToTimestamp($el->getField('dob')));
            } ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('House') ?></span><br/>
            <?php echo $el->getDetailsOfPerson('House', 'name'); ?>
        </td>
    </tr>
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Website') ?></span><br/><?php
            if (! empty($el->getField('website'))) { ?>
                <em><a href='<?php echo $el->getField('website') ?>'><?php echo $el->getField('website') ?></a></em><?php
            } ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Email') ?></span><br/><?php
            if (! empty($el->getField('email'))) { ?>
                <em><a href='mailto:<?php echo $el->getField('email') ?>'><?php echo $el->getField('email') ?></a></em><?php
            } ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
        	<span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('School History') ?></span><br/><?php
			if ($el->getField('dateStart') != '') { ?>
				<u><?php echo Gibbon\trans::__('Start Date') ?></u>: <?php echo Gibbon\helper::dateConvertBack($el->getField('dateStart')) ?></br><?php
			}

			echo '<u>'.$el->getEnrolment()->getField('schoolYear').'</u>: '.$el->getEnrolment()->getField('rollGroup').'<br/>';
			if (! empty($el->getField('dateEnd'))) {
				echo '<u>'.Gibbon\trans::__('End Date').'</u>: '.Gibbon\helper::dateConvertBack($el->getField('dateEnd')).'</br>';
			} ?>
        </td>
    </tr>
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Locker Number') ?></span><br/>
            <?php if (! empty($el->getField('lockerNumber'))) {
                echo $el->getField('lockerNumber');
            }?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Student ID') ?></span><br/>
            <?php if (! empty($el->getField('studentID'))) {
                echo $el->getField('studentID');
            } ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
        
        </td>
    </tr> <?php
    $privacySetting = $this->config->getSettingByScope('User Admin', 'privacy');
    if ($privacySetting == 'Y') { ?>
        <tr>
            <td style='width: 33%; padding-top: 15px; vertical-align: top' colspan='3'>
                <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Privacy') ?></span><br/>
                <?php if ($el->getField('privacy') != '') { ?>
                    <span style='color: #cc0000; background-color: #F6CECB'>
                        <?php echo trans::__('Privacy required:').' '.$el->getField('privacy'); ?>
                    </span> 
                <?php } else { ?>
                    <span style='color: #390; background-color: #D4F6DC;'>
                        <?php echo trans::__('Privacy not required or not set.'); ?>
                    </span>
                <?php } ?>
            </td>
        </tr> <?php
    }
    $studentAgreementOptions = $this->config->getSettingByScope('School Admin', 'studentAgreementOptions');
    if (! empty($studentAgreementOptions)) { ?>
        <tr>
            <td style='width: 33%; padding-top: 15px; vertical-align: top' colspan='3'>
                <span style='font-size: 115%; font-weight: bold'><?php echo Gibbon\trans::__('Student Agreements')?></span><br/>
                <?php echo trans::__('Agreements Signed:').' '.$el->getField('studentAgreements'); ?>
            </td>
        </tr> <?php
    } ?>
</table>
<?php $this->endWell(); ?>
