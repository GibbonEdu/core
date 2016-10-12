<?php
use Gibbon\trans ;
use Gibbon\helper ;

$this->h2($el->header);
if ($this->getSecurity()->isActionAccessible('/modules/User Admin/user_manage.php')) 
    $this->linkTop(array_merge($el->links, array('edit' => '/modules/User Admin/user_manage_edit.php&gibbonPersonID='.$el->personID)));
$this->startWell();
$this->h3('General Information'); ?>

<table class='smallIntBorder' cellspacing='0' style='width: 100%'>
    <tr>
        <td style='width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Surname'); ?></span><br/>
            <?php echo $el->student->getField('surname'); ?>
        </td>
        <td style='width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('First Name'); ?></span><br/>
            <?php echo $el->student->getField('firstName'); ?>
        </td>
        <td style='width: 34%; vertical-align: top'>
        
        </td>
    </tr>
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Preferred Name'); ?></span><br/>
            <?php echo $el->student->formatName(); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Official Name'); ?></span><br/>
            <?php echo $el->student->getField('officialName'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Name In Characters'); ?></span><br/>
            <?php echo $el->student->getField('nameInCharacters'); ?>
        </td>
    </tr>
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Gender'); ?></span><br/>
            <?php echo $el->student->getField('gender'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Date of Birth'); ?></span><br/>
            <?php if (! is_null($el->student->getField('dob')) &&  $el->student->getField('dob') != '0000-00-00') {
                echo helper::dateConvertBack($el->student->getField('dob'));
            } ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Age'); ?></span><br/>
            <?php if (is_null($el->student->getField('dob')) == false and $el->student->getField('dob') != '0000-00-00') {
                echo helper::getAge(dateConvertToTimestamp($el->student->getField('dob')));
            } ?>
        </td>
    </tr>
</table>

<?php 
$this->endWell();
$this->startWell();
$this->h3('Contacts'); ?>

<table class='smallIntBorder' cellspacing='0' style='width: 100%'><!-- ".__LINE__." -->
	<?php $numberCount = 0;
    if (! empty($el->student->getField('phone1')) || ! empty($el->student->getField('phone2')) || ! empty($el->student->getField('phone3')) || ! empty($el->student->getField('phone4'))) { ?>
        <tr>
            <?php for ($i = 1; $i < 5; ++$i) {
                if (! empty($el->student->getField('phone'.$i))) {
                    $numberCount++; ?>
                    <td width: '33%'; style='vertical-align: top'>
                        <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Phone').' '.$numberCount; ?></span><br/> <?php
                        if ($el->student->getField('phone'.$i.'Type') != '') {
                            echo '<em>'.$el->student->getField('phone'.$i.'Type').':</em> ';
                        }
                        if (! empty($el->student->getField('phone'.$i.'CountryCode'))) {
                            echo '+'.$el->student->getField('phone'.$i.'CountryCode'); 
                        }
                        echo helper::formatPhone($el->student->getField('phone'.$i)); ?><br/>
                    </td><?php
                } else { ?>
                    <td width: 33%; style='vertical-align: top'>
            
                    </td><?php
                }
            } ?>
        </tr>
    <?php } ?>
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Email'); ?></span><br/>
            <?php if (! empty($el->student->getField('email'))) { ?>
            	<em><a href='mailto:<?php echo $el->student->getField('email'); ?>'><?php echo $el->student->getField('email'); ?></a></em><?php
            } ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Alternate Email'); ?></span><br/>
            <?php if (! empty($el->student->getField('emailAlternate'))) { ?>
            	<em><a href='mailto:<?php echo $el->student->getField('emailAlternate'); ?>'><?php echo $el->student->getField('emailAlternate'); ?></a></em><?php
            } ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=2>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Website'); ?></span><br/> <?php
            if (! empty($el->student->getField('website'))) { ?>
           		<em><a href='<?php echo $el->student->getField('website'); ?>'><?php echo $el->student->getField('website'); ?></a></em><?php
            } ?>
        </td>
    </tr><?php
    if (! empty($el->student->getField('address1'))) { ?>
        <tr>
            <td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=4>
                <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Address 1'); ?></span><br/><?php
                $address1 = helper::addressFormat($el->student->getField('address1'), $el->student->getField('address1District'), $el->student->getField('address1Country'));
                if ($address1) 
                    echo $address1; ?>
            </td>
        </tr><?php
    }
    if (! empty($el->student->getField('address2'))) { ?>
        <tr>
            <td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>
                <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Address 2'); ?></span><br/><?php
                $address2 = helper::addressFormat($el->student->getField('address2'), $el->student->getField('address2District'), $el->student->getField('address2Country'));
                if ($address2) 
                    echo $address2; ?>
            </td>
        </tr><?php
    } ?>
</table>

<?php 
$this->endWell();
$this->startWell();
$this->h3('School Information'); ?>

<table class='smallIntBorder' cellspacing='0' style='width: 100%'>
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Last School'); ?></span><br/><?php
            echo $el->student->getField('lastSchool'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Start Date'); ?></span><br/><?php
            echo helper::dateConvertBack($el->student->getField('dateStart')); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Class Of'); ?></span><br/><?php
            if (empty($el->student->getField('gibbonSchoolYearIDClassOf'))) { ?>
            	<em><?php echo trans::__('NA'); ?></em><?php
            } else {
				echo $el->student->getClassOf()->getField('name');
            } ?>
        </td>
    </tr>
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Next School'); ?></span><br/><?php
            echo $el->student->getField('nextSchool'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('End Date'); ?></span><br/><?php
            echo helper::dateConvertBack($el->student->getField('dateEnd')); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Departure Reason'); ?></span><br/><?php
            echo $el->student->getField('departureReason'); ?>
        </td>
    </tr><?php
    $dayTypeOptions = $this->config->getSettingByScope('User Admin', 'dayTypeOptions');
    if (! empty($dayTypeOptions)) { ?>
		<tr>
			<td style='width: 33%; padding-top: 15px; vertical-align: top'>
                <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Day Type'); ?></span><br/><?php
                echo $el->student->getField('dayType'); ?>
			</td>
			<td style='width: 33%; padding-top: 15px; vertical-align: top'>
			
			</td>
			<td style='width: 33%; padding-top: 15px; vertical-align: top'>
			
			</td>
		</tr><?php
    } ?>
</table>


<?php 
$this->endWell();
$this->startWell();
$this->h3('Background');  ?>

<table class='smallIntBorder' cellspacing='0' style='width: 100%'>
    <tr>
        <td width: 33%; style='vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Country of Birth'); ?></span><br/><?php
            echo $el->student->getField('countryOfBirth'); ?>
        </td>
        <td style='width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Ethnicity'); ?></span><br/><?php
            echo $el->student->getField('ethnicity'); ?>
        </td>
        <td style='width: 34%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Religion'); ?></span><br/><?php
            echo $el->student->getField('religion'); ?>
        </td>
    </tr>
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Citizenship 1'); ?></span><br/><?php
            echo $el->student->getField('citizenship1');
            if (! empty($el->student->getField('citizenship1Passport'))) { ?>
                <br/><?php
                echo $el->student->getField('citizenship1Passport'); 
            } ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Citizenship 2'); ?></span><br/><?php
            echo $el->student->getField('citizenship2');
            if (! empty($el->student->getField('citizenship2Passport'))) { ?>
                <br/><?php
                echo $el->student->getField('citizenship2Passport'); 
            } ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'><?php
            if ($this->session->isEmpty('country')) { ?>
				<span style='font-size: 115%; font-weight: bold'><?php echo trans::__('National ID Card'); ?></span><br/><?php
            } else { ?>
				<span style='font-size: 115%; font-weight: bold'><?php echo $this->session->get('country').' '.trans::__('ID Card'); ?></span><br/><?php
            }
            echo $el->student->getField('nationalIDCardNumber'); ?>
        </td>
    </tr>
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('First Language'); ?></span><br/><?php
            echo $el->student->getField('languageFirst'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Second Language'); ?></span><br/><?php
            echo $el->student->getField('languageSecond'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Third Language'); ?></span><br/><?php
            echo $el->student->getField('languageThird'); ?>
        </td>
    </tr>
    <tr>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'><?php
            if ($this->session->isEmpty('country')) { ?>
            	<span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Residency/Visa Type'); ?></span><br/><?php
            } else { ?>
            	<span style='font-size: 115%; font-weight: bold'><?php echo $this->session->get('country').' '.trans::__('Residency/Visa Type'); ?></span><br/><?php
            }
            echo $el->student->getField('residencyStatus'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'><?php
            if ($this->session->isEmpty('country')) { ?>
            	<span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Visa Expiry Date'); ?></span><br/><?php
            } else { ?>
            	<span style='font-size: 115%; font-weight: bold'><?php echo $this->session->get('country').' '.trans::__('Visa Expiry Date'); ?></span><br/><?php
            }
            if (! empty($el->student->getField('visaExpiryDate')))
            	echo helper::dateConvertBack($el->student->getField('visaExpiryDate')); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
        
        </td>
    </tr>
</table>

<?php 
$this->endWell();
$this->startWell();
$this->h3('School Data');  ?>

<table class='smallIntBorder' cellspacing='0' style='width: 100%'><!-- ".__LINE__." -->
    <tr>
        <td style='width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Year Group'); ?></span><br/><?php
            $el->student->getYearGroup();
			if ($el->student->validYearGroup) 
                echo $el->student->getYearGroup('name'); ?>
        </td>
        <td style='width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Roll Group'); ?></span><br/><?php
            $el->student->getRollGroup();
			if ($el->student->validRollGroup) {
				if ($this->getSecurity()->isActionAccessible('/modules/Roll Groups/rollGroups_details.php')) { ?>
                	<a href='<?php GIBBON_URL . 'index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID='.$el->student->getRollGroup('gibbonRollGroupID');?>'><?php echo $el->student->getRollGroup('name'); ?></a><?php
                } else
                        echo $el->student->getRollGroup('name'); 
            } ?>
        </td>
        <td style='width: 34%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Tutors'); ?></span><br/><?php
            if (count($el->student->getRollGroup()->getTutors()) > 0) {
				$main = true ;
				foreach($el->student->getRollGroup()->getTutors() as $tutor)
                	if ($this->getSecurity()->isActionAccessible('/modules/Staff/staff_view_details.php')) { ?>
                    	<a href='<?php echo GIBBON_URL; ?>index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID=<?php echo $tutor->getField('gibbonPersonID'); ?>'><?php echo $tutor->formatName(false, true); ?></a><?php
                } else {
                    echo $tutor->formatName();
                }
                if ($main) {
                     echo ' ('.trans::__('Main Tutor').')';
					 $main = false;
                } ?>
                <br/><?php
            } ?>
        </td>
    </tr>
    <tr>
        <td style='padding-top: 15px ; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('House'); ?></span><br/><?php
			echo $el->student->getHouse('name'); ?>
        </td>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Student ID'); ?></span><br/><?php
            echo $el->student->getField('studentID'); ?>
        </td>
        <td style='width: 34%; vertical-align: top'>
        
        </td>
    </tr>
</table>

<?php 
$this->endWell();
$this->startWell();
$this->h3('System Data'); ?>

<table class='smallIntBorder' cellspacing='0' style='width: 100%'><!-- ".__LINE__." -->
    <tr>
        <td width: 33%; style='vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Username'); ?></span><br/><?php
            echo $el->student->getField('username'); ?>
        </td>
        <td style='width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Can Login?'); ?></span><br/><?php
            echo helper::ynExpander($el->student->getField('canLogin')); ?>
        </td>
        <td style='width: 34%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Last IP Address'); ?></span><br/><?php
            echo $el->student->getField('lastIPAddress'); ?>
        </td>
    </tr>
</table>

<?php 
$this->endWell();
$this->startWell();
$this->h3('Miscellaneous'); ?>


<table class='smallIntBorder' cellspacing='0' style='width: 100%'><!-- ".__LINE__." -->
    <tr>
        <td style='width: 33%; vertical-align: top'>
        <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Transport'); ?></span><br/><?php
        echo $el->student->getField('transport');
        if (! empty($el->student->getField('transportNotes'))) 
			echo '<br/>'.$el->student->getField('transportNotes'); ?>
        </td>
        <td style='width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Vehicle Registration'); ?></span><br/><?php 
            echo $el->student->getField('vehicleRegistration'); ?>
        </td>
        <td style='width: 33%; vertical-align: top'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Locker Number'); ?></span><br/><?php
            echo $el->student->getField('lockerNumber'); ?>
        </td>
    </tr><?php
    
    $privacySetting = $this->config->getSettingByScope('User Admin', 'privacy');
    if ($privacySetting == 'Y') { ?>
        <tr>
            <td style='width: 33%; padding-top: 15px; vertical-align: top' colspan='3'>
                <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Image Privacy'); ?></span><br/><?php
                if (! empty($el->student->getField('privacy'))) { ?>
                    <span style='color: #cc0000; background-color: #F6CECB'><?php
                    echo trans::__('Privacy required:').' '.$el->student->getField('privacy'); ?>
                    </span><?php
                } else { ?>
                    <span style='color: #390; background-color: #D4F6DC;'><?php
                    echo trans::__('Privacy not required or not set.'); ?>
                    </span><?php
                } ?>
            </td>
        </tr><?php
    }
    $studentAgreementOptions = $this->config->getSettingByScope('School Admin', 'studentAgreementOptions');
    if (! empty($studentAgreementOptions)) { ?>
        <tr>
            <td style='width: 33%; padding-top: 15px; vertical-align: top' colspan='3'>
            <span style='font-size: 115%; font-weight: bold'><?php echo trans::__('Student Agreements'); ?></span><br/><?php
            echo trans::__('Agreements Signed:').' '.$el->student->getField('studentAgreements'); ?>
            </td>
        </tr><?php
    } ?>
</table>

<?php 
$this->endWell();

//Custom Fields
$fields = unserialize($el->student->getField('fields'));
$resultFields = $el->student->getCustomFields(true);
if (count($resultFields) > 0) {

$this->startWell();
$this->h3('Custom Fields'); ?>

    <table class='smallIntBorder' cellspacing='0' style='width: 100%'><?php
    $count = 0;
    $columns = 3;
    
    foreach($resultFields as $field) {
        if ($count % $columns == 0) { ?>
            <tr><?php
        } ?>
        <td style='width: 33%; padding-top: 15px; vertical-align: top'>
        <span style='font-size: 115%; font-weight: bold'><?php echo trans::__($rowFields['name']); ?></span><br/><?php 
        if (isset($fields[$field->getField('gibbonPersonFieldID')])) {
            if ($field->getField('type') == 'date') {
                echo helper::dateConvertBack($fields[$field->getField('gibbonPersonFieldID')]);
            } elseif ($field->getField('type') == 'url') { ?>
                <a target='_blank' href='<?php echo $fields[$field->getField('gibbonPersonFieldID')]; ?>'><?php echo $fields[$field->getField('gibbonPersonFieldID')]; ?></a><?php
            } else {
                echo $fields[$field->getField('gibbonPersonFieldID')];
            }
        } ?>
        </td> <?php
    
        if ($count % $columns == ($columns - 1)) { ?>
            </tr><?php
        }
       	$count++;
    }
    
    if ($count % $columns != 0) {
        for ($i = 0; $i < $columns - ($count % $columns); ++$i) { ?>
            <td style='width: 33%; padding-top: 15px; vertical-align: top'></td><?php
        } ?>
        </tr><?php
    } ?>
    
    </table><?php
	$this->endWell();
} 
