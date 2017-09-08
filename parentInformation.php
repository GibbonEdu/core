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

use Gibbon\Forms\Form;

echo "<div class='trail'>";
echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > </div><div class='trailEnd'>".__($guid, 'Parent Information').'</div>';
echo '</div>';

$translation = include $_SESSION[$guid]['absolutePath'].'/modules/ID Cards/translations.php';

// Module JS
echo '<script type="text/javascript">';
echo @file_get_contents($_SESSION[$guid]['absolutePath'].'/modules/ID Cards/js/module.js');
echo '</script>';

// Module CSS
echo '<style>';
echo @file_get_contents($_SESSION[$guid]['absolutePath'].'/modules/ID Cards/css/module.css');
echo '.cropit-preview:not(.cropit-image-loading)::before { content: "'.$translation['step2-photo-button'].'";}';
echo '</style>';


$step = 1;
if (isset($_GET['step']) && $_GET['step'] == 2 ) {
	$step = 2;
}

$email = (isset($_GET['email']))? $_GET['email'] : '';

// Skip to step 2 for logged in users
if (!empty($_SESSION[$guid]['username'])) {
	$step = 2;
	$email = $_SESSION[$guid]['email'];
}


$returns = array();
$returns['error0'] = __($guid, 'Email address not set.');
$returns['error8'] = sprintf($translation['confirm-error1'], '<a mailto="'.$_SESSION[$guid]['organisationDBAEmail'].'">'.$_SESSION[$guid]['organisationDBAEmail'].'</a>');
$returns['error9'] = sprintf($translation['confirm-error2'], '<a mailto="'.$_SESSION[$guid]['organisationDBAEmail'].'">'.$_SESSION[$guid]['organisationDBAEmail'].'</a>');
$returns['success0'] = $translation['confirm-success'];
if (isset($_GET['return'])) {
    returnProcess($guid, $_GET['return'], null, $returns);
    if (stripos($_GET['return'], 'success') !== false) return;
}


if ($step == 1) {
    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/parentInformationProcess.php?step=1');
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->setAutocomplete('on');

    if (isset($_GET['sidebar']) && $_GET['sidebar'] == 'false') {
        $form->addClass('fixedWidth');
    }

    $form->addRow()->addHeading($translation['step1'].' &nbsp;<small>'.$translation['step1-subheading'].'</small>')
        ->append('<p>'.$translation['step1-message'].'</p>');

    $row = $form->addRow();
        $row->addLabel('email', $translation['step1-email']);
        $row->addEmail('email')->isRequired()->addValidationOption('onlyOnBlur: true')->setClass('longWidth');

    $form->addRow()->addContent($translation['step1-birthday-message'])->wrap('<br/><p>', '</p>');

    $months = array('01' => $translation['jan'], '02' => $translation['feb'],'03' => $translation['mar'],'04' => $translation['apr'],'05' => $translation['may'],'06' => $translation['jun'],'07' => $translation['jul'],'08' => $translation['aug'],'09' => $translation['sep'],'10' => $translation['oct'],'11' => $translation['nov'],'12' => $translation['dec']);

    $row = $form->addRow();
        $row->addLabel('birthdate', $translation['step1-birthday']);
        $inline = $row->addColumn()->setClass('inline-input longWidth');
        $inline->addNumber('birthday')->isRequired()->minimum(1)->maximum(31)->setClass('dateWidth')->maxLength(2)->addValidationOption('onlyOnBlur: true');
        $inline->addSelect('birthmonth')->fromArray($months)->setClass('dateWidth');
        $inline->addSelect('birthyear')->fromArray(range(date('Y'), date('Y')-20) )->setClass('dateWidth');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit()->addClass('large-button');

    echo $form->getOutput();

} else {
	//Get URL parameters
	$proceed = false;
	$message = $translation['step1-invalid'];

	if (!empty($_SESSION[$guid]['username'])) {
		// Logged in users
		$gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
		$gibbonPersonResetID='';
		$key='';
		$input = $_SESSION[$guid]['email'];

		try {
			$data = array('gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT student.gibbonPersonID FROM gibbonFamilyAdult JOIN gibbonFamilyChild ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) JOIN gibbonPerson AS student ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID) WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID AND student.status='Full' && gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current')";
            $result = $connection2->prepare($sql);
            $result->execute($data);
	    } catch (PDOException $e) {}

	    if ($result->rowCount() >= 1) {
	    	$proceed = true;
	    } else {
	    	$message = sprintf($translation['step1-noaccount'], '<a mailto="'.$_SESSION[$guid]['organisationDBAEmail'].'">'.$_SESSION[$guid]['organisationDBAEmail'].'</a>');
	    }

	} else {
		$input = (isset($_GET['input']))? $_GET['input'] : null;
		$key = (isset($_GET['key']))? $_GET['key'] : null;
		$gibbonPersonResetID = (isset($_GET['gibbonPersonResetID']))? $_GET['gibbonPersonResetID'] : null;

		if (!empty($input) && !empty($key) && !empty($gibbonPersonResetID)) {
			//Verify authenticity of this request and check it is fresh (within 48 hours)
			try {
		        $data = array('key' => $key, 'gibbonPersonResetID' => $gibbonPersonResetID);
		        $sql = "SELECT gibbonPersonID FROM gibbonPersonReset WHERE `key`=:key AND gibbonPersonResetID=:gibbonPersonResetID AND (timestamp > DATE_SUB(now(), INTERVAL 2 DAY))";
		        $result = $connection2->prepare($sql);
		        $result->execute($data);
		    } catch (PDOException $e) {}

		    if ($result->rowCount() == 1) {
		    	$gibbonPersonID = $result->fetchColumn(0);
		    	$proceed = true;
		    }
		}
	}

	if ($proceed == false) {
		echo "<div class='error'>";
		echo __($guid, $message);
		echo '</div>';
	} else {

		if (empty($_SESSION[$guid]['username'])) {
			echo "<div class='success'>";
			echo $translation['confirm-success'];
			echo '</div>';
		}

		try {
	        $data = array('gibbonPersonID' => $gibbonPersonID);
	        $sql = "SELECT familyPerson.gibbonPersonID, familyPerson.username, familyPerson.surname, familyPerson.firstName, familyPerson.officialName, familyPerson.image_240 FROM gibbonFamilyAdult AS parent JOIN gibbonFamilyAdult AS familyAdult ON (parent.gibbonFamilyID=familyAdult.gibbonFamilyID) JOIN gibbonPerson AS familyPerson ON (familyAdult.gibbonPersonID=familyPerson.gibbonPersonID) WHERE parent.gibbonPersonID=:gibbonPersonID ORDER BY familyAdult.gibbonPersonID=:gibbonPersonID DESC, familyAdult.contactPriority";
	        $result = $connection2->prepare($sql);
	        $result->execute($data);
	    } catch (PDOException $e) {
	        echo "<div class='error'>".$e->getMessage().'</div>';
	    }

		//Show form
		echo "<form id='photoupload' name='photoupload' method='post' action='".$_SESSION[$guid]['absoluteURL']."/parentInformationProcess.php?input=$input&step=2&gibbonPersonResetID=$gibbonPersonResetID&key=$key' enctype='multipart/form-data'>";
			?>
			<table class='smallIntBorder' cellspacing='0' <?php if (isset($_GET['sidebar']) && $_GET['sidebar'] == 'false') echo 'style="max-width:750px;margin: 0 auto;"'; ?>>
				<tr class='break'>
	    			<td colspan=3>
	    				<?php if (empty($_SESSION[$guid]['username'])) : ?>
		    				<h3>
		    					<?php echo $translation['step2']; ?> &nbsp;<small><?php echo $translation['step2-subheading']; ?></small>
		    				</h3>
		    			<?php endif; ?>
		    			<img src="http://gibbon.tis.edu.mo/uploads/passport-graphic.png" style="float:right;margin-left: 20px;">
	    				<p>
	    					<?php echo $translation['step2-message']; ?>
	    				</p>
	    				<h4><?php echo $translation['step2-photo-heading']; ?></h4>
	    				<p>
	    					<?php echo $translation['step2-photo-message']; ?>
	    				</p>

	    			</td>
	    		</tr>
	    		<?php
	    			while ($familyAdult = $result->fetch()) :

	    				$photoURL = (!empty($familyAdult['image_240']) && file_exists($_SESSION[$guid]['absolutePath'].'/'.$familyAdult['image_240']))? $_SESSION[$guid]['absoluteURL'].'/'.$familyAdult['image_240'] : '';
	    		?>
		    		<tr>
		    			<td rowspan=1 style="width:200px;">
							<b><?php echo $familyAdult['officialName']; ?></b><br/>
						</td>

						<?php if ( substr($familyAdult['username'], 0, 4) == '1000' && !empty($familyAdult['image_240'])) : ?>
							<td style="width:210px;">
								<b><?php echo __($guid, 'Photo'); ?></b><br/>
								<span class="emphasis small">Staff photo on file - cannot be changed</span>
							</td>
							<td>
								<div style="width:302px;float:right;">
									<div class="cropit-photo-disabled" style="background-image:url(<?php echo $_SESSION[$guid]['absoluteURL'].'/'.$familyAdult['image_240']; ?>);">&nbsp;</div>
								</div>
							</td>
						<?php else : ?>
							<td style="width:210px;">
								<b><?php echo $translation['step2-photo']; ?></b>
								</td>
								<td>
								<div id="photo<?php echo $familyAdult['username'];?>" class="cropit-photo" style="width:302px;float:right;">
									<div class="cropit-preview" style="border: 2px solid #bbbbbb;">
										<img class="loading" title="Loading" src="<?php echo $_SESSION[$guid]['absoluteURL'];?>/themes/Default/img/loading.gif">
									</div>

									<img title="Zoom In" src="<?php echo $_SESSION[$guid]['absoluteURL'];?>/themes/Default/img/plus.png" style="width:20px;height:20px;">
									<input type="range" class="cropit-image-zoom-input" style="width:140px;"/>
									<img title="Zoom Out" src="<?php echo $_SESSION[$guid]['absoluteURL'];?>/themes/Default/img/minus.png" style="width:20px;height:20px;">

									<img title="Rotate" src="<?php echo $_SESSION[$guid]['absoluteURL'];?>/themes/Default/img/refresh.png" class="rotate-cw-btn" style="width:20px;height:20px;margin-left:20px;">

									<img title="Delete" src="<?php echo $_SESSION[$guid]['absoluteURL'];?>/themes/Default/img/garbage.png" class="delete-btn" style="width:20px;height:20px;margin-left:20px;">

									<input type="file" class="cropit-image-input standardWidth" name="file[<?php echo $familyAdult['username'];?>]" id="file[<?php echo $familyAdult['username'];?>]" accept=".jpg,.gif,.jpeg,.png" />
									<input type="hidden" name="attachment[<?php echo $familyAdult['username'];?>]" id="attachment[<?php echo $familyAdult['username'];?>]" value="" />
								</div>

								<script type="text/javascript">
									var photoName = "<?php echo 'photo'.$familyAdult['username'];?>";
									$('#'+photoName).cropit({ <?php echo (!empty($photoURL))? 'imageState: { src:"'.$photoURL.'"},' : ''; ?> width: 180, height: 240, exportZoom: 2, smallImage: 'allow', initialZoom: 'min' , minZoom: 'fit', maxZoom: 2, onImageError: function() { alert('<?php echo $translation['step2-error-photo']; ?>'); }});
								</script>
							</td>
						<?php endif; ?>

					</tr>
				<?php endwhile; ?>

				<tr class='break'>
	    			<td colspan=3>
	    				<h3>
	    					<?php echo $translation['step2-add-heading']; ?>
	    				</h3>
	    				<p><?php echo $translation['step2-add-message']; ?></p>
	    			</td>
	    		</tr>

				<?php
	    			for ($i = 0; $i < 3;$i++) :

	    				try {
					        $data = array( 'sequenceNumber' => $i, 'gibbonPersonID' => $gibbonPersonID);
					        $sql = "SELECT name, relationship, image_240 FROM gibbonFamilyAdditionalPerson WHERE gibbonFamilyID=(SELECT gibbonFamilyID FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID) AND sequenceNumber=:sequenceNumber";
					        $result = $connection2->prepare($sql);
					        $result->execute($data);
					    } catch (PDOException $e) {}

					    if ($result->rowCount() == 1) {
					    	$additionalPerson = $result->fetch();
					    } else {
					    	$additionalPerson = array('name' => '', 'relationship' => '', 'image_240' => '');
					    }

					    $photoURL = (!empty($additionalPerson['image_240']) && file_exists($_SESSION[$guid]['absolutePath'].'/'.$additionalPerson['image_240']))? $_SESSION[$guid]['absoluteURL'].'/'.$additionalPerson['image_240'] : '';
	    		?>
					<tr>
						<td rowspan=3 style="width:200px;">
							<b><?php echo $translation['step2-add-person'].' '.($i+1); ?></b>
						</td>
						<td style="width:210px;">
							<b><?php echo $translation['step2-photo']; ?></b><br/>
							<span class="emphasis small">
							</span>
						</td>
						<td>
							<div id="additionalPhoto<?php echo $i;?>" class="cropit-photo" style="width:302px;float:right;">
								<div class="cropit-preview" style="border: 2px solid #bbbbbb;">
									<img class="loading" title="Loading" src="<?php echo $_SESSION[$guid]['absoluteURL'];?>/themes/Default/img/loading.gif">
								</div>

								<img title="Zoom In" src="<?php echo $_SESSION[$guid]['absoluteURL'];?>/themes/Default/img/plus.png" style="width:20px;height:20px;">
								<input type="range" class="cropit-image-zoom-input" style="width:140px;"/>
								<img title="Zoom Out" src="<?php echo $_SESSION[$guid]['absoluteURL'];?>/themes/Default/img/minus.png" style="width:20px;height:20px;">

								<img title="Rotate" src="<?php echo $_SESSION[$guid]['absoluteURL'];?>/themes/Default/img/refresh.png" class="rotate-cw-btn" style="width:20px;height:20px;margin-left:20px;">

								<img title="Delete" src="<?php echo $_SESSION[$guid]['absoluteURL'];?>/themes/Default/img/garbage.png" class="delete-btn" style="width:20px;height:20px;margin-left:20px;">

								<input type="file" class="cropit-image-input standardWidth" name="additionalFile<?php echo $i;?>" id="additionalFile<?php echo $i;?>" accept=".jpg,.gif,.jpeg,.png" />
								<input type="hidden" name="attachmentAdditional[<?php echo $i;?>]" id="attachmentAdditional[<?php echo $i;?>]" value="" />
							</div>

							<script type="text/javascript">
								var photoName = "<?php echo 'additionalPhoto'.$i;?>";
								$('#'+photoName).cropit({ <?php echo (!empty($photoURL))? 'imageState: { src:"'.$photoURL.'"},' : ''; ?> width: 180, height: 240, exportZoom: 2, smallImage: 'allow', initialZoom: 'min' , minZoom: 'fit', maxZoom: 2, onImageError: function() { alert('<?php echo $translation['step2-error-photo']; ?>'); }
								});
							</script>
						</td>
					</tr>
					<tr>
						<td style="width:160px;">
							<b><?php echo $translation['step2-name']; ?></b>
						</td>
						<td class="right">
							<input class="standardWidth" name="additionalName[<?php echo $i;?>]" type="text" value="<?php echo $additionalPerson['name'];?>">
						</td>
					</tr>
					<tr>
						<td style="width:160px;">
							<b><?php echo $translation['step2-relationship']; ?></b>
						</td>
						<td class="right">
							<!-- <input class="standardWidth" name="additionalRelationship[<?php echo $i;?>]" type="text" value="<?php echo $additionalPerson['relationship'];?>"> -->
							<select name="additionalRelationship[<?php echo $i;?>]" class="standardWidth">
								<option value="" <?php if ($additionalPerson['relationship'] == '') echo 'selected';?>></option>
								<option value="Helper" <?php if ($additionalPerson['relationship'] == 'Helper') echo 'selected';?>><?php echo $translation['step2-helper']; ?></option>
								<option value="Driver" <?php if ($additionalPerson['relationship'] == 'Driver') echo 'selected';?>><?php echo $translation['step2-driver']; ?></option>
								<option value="Family" <?php if ($additionalPerson['relationship'] == 'Family') echo 'selected';?>><?php echo $translation['step2-family']; ?></option>
								<option value="Other" <?php if ($additionalPerson['relationship'] == 'Other') echo 'selected';?>><?php echo $translation['step2-other']; ?></option>
							</select>
						</td>
					</tr>
				<?php endfor; ?>


				<script type="text/javascript">
					$(document).ready(function(){
						// Handle rotation
						$('.rotate-cw-btn').click(function() {
							$(this).parent().cropit('rotateCW');
						});

						// Handle deletion
						$('.delete-btn').click(function() {
							var parent = $(this).parent();

							$('.cropit-preview', parent).removeClass('cropit-image-loaded');
							$('.cropit-preview-image', parent).attr('src','');

							parent.cropit('destroy');
						});


						// Open file dialog when initially clicked
						$('.cropit-preview').click(function() {
							if ($(this).hasClass('cropit-image-loaded')) return;
							$('.cropit-image-input', $(this).parent()).click();
						});


						$('#photoupload').submit(function(event) {

							event.preventDefault(); //this will prevent the default submit

							// Append the photos as attachment data
							$('input[name^="attachment"]').each( function() {

								var dataURL = $(this).parent('.cropit-photo').cropit('export', {
								  type: 'image/jpeg',
								  quality: 1.0,
								  fillBg: '#fff',
								});

								$(this).attr('value', dataURL);
							});

							// Disable upload of original files
							$('input[type="file"]').each( function() {
								$(this).prop('disabled', true);
							});

							$(this).unbind('submit').submit(); // continue the submit unbind preventDefault
						});
					});
				</script>

                <tr class="break">
                    <td colspan="3"><br/>
				    <?php if (empty($_SESSION[$guid]['username'])) : ?>
						<p>
						<?php echo $translation['step2-final-message']; ?>
						</p>
				    <?php endif; ?>

                    <p>
                    <?php echo $translation['step2-request-message']; ?>
                    </p>
                    </td>
                </tr>
	    		<tr>
	    			<td colspan=2>
	    				<span class="emphasis small"><?php echo $translation['required-message']; ?></span>
	    			</td>
	    			<td class="right">
	    				<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
	    				<input type="submit" value="<?php echo $translation['submit-request']; ?>" style='font-size: 16px;padding:6px 10px;height:auto;'>
	    			</td>
	    		</tr>
	    	</table><br/>
	    </form>
		<?php
	}
}
?>
