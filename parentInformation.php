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

echo "<div class='trail'>";
echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > </div><div class='trailEnd'>".__($guid, 'Parent Information').'</div>';
echo '</div>';

?>
<script type="text/javascript">
function dataURItoBlob(dataURI) {
    // convert base64/URLEncoded data component to raw binary data held in a string
    var byteString;
    if (dataURI.split(',')[0].indexOf('base64') >= 0)
        byteString = atob(dataURI.split(',')[1]);
    else
        byteString = unescape(dataURI.split(',')[1]);

    // separate out the mime component
    var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];

    // write the bytes of the string to a typed array
    var ia = new Uint8Array(byteString.length);
    for (var i = 0; i < byteString.length; i++) {
        ia[i] = byteString.charCodeAt(i);
    }

    return new Blob([ia], {type:mimeString});
}
</script>
<style>
	/* Show load indicator when image is being loaded */
	.cropit-preview.cropit-image-loading .spinner {
	  opacity: 1;
	}

	/* Show move cursor when image has been loaded */
	.cropit-preview.cropit-image-loaded .cropit-preview-image-container {
	  cursor: move;
	  background: #ffffff;
	}

	/* Show pointer cursor before image has been loaded */
	.cropit-preview:not(.cropit-image-loaded) {
		cursor:pointer;
	}

	/* Show upload photo text */
	.cropit-preview::before {
		content: "Upload a Photo";
		border: 2px dashed #dddddd;
		border-radius: 8px;
		padding: 20px;
		width:90px;
		position: relative;
		top: 50%;
		left: calc(50% - ((90px + 40px - 4px)/2));
	}

	.cropit-photo-disabled {
		width: 180px;
		height: 240px;
		border: 2px solid #bbbbbb;
		background-size: cover;
	}

</style>

<?php

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
$returns['error3'] = __($guid, 'Failed to send update email.');
$returns['error4'] = __($guid, 'Your request failed due to non-matching passwords.');
$returns['error5'] = __($guid, 'Your request failed due to incorrect or non-existent or non-unique email address.');
$returns['error6'] = __($guid, 'Your request failed because your password to not meet the minimum requirements for strength.');
$returns['error7'] = __($guid, 'Your request failed because your new password is the same as your current password.');
$returns['error8'] = __($guid, 'Your request failed because the birthdate supplied does not match the one in our records, or your family data could not be located. Please try again, and if the problem persists contact support at <a mailto="'.$_SESSION[$guid]['organisationDBAEmail'].'">'.$_SESSION[$guid]['organisationDBAEmail'].'</a>');
$returns['error9'] = __($guid, 'Your request failed because the email address supplied does not match the one in our records. Please try again, and if the problem persists contact support at <a mailto="'.$_SESSION[$guid]['organisationDBAEmail'].'">'.$_SESSION[$guid]['organisationDBAEmail'].'</a>');
$returns['success0'] = __($guid, 'Account confirmation successfully initiated, please check your email. If you do not receive an email within a few minutes please check your spam folder as some emails may end up there.');
if (isset($_GET['return'])) {
    returnProcess($guid, $_GET['return'], null, $returns);
    if (stripos($_GET['return'], 'success') !== false) return;
}	

if ($step == 1) { ?>

	<h3>
		Welcome to Gibbon
	</h3>
	<p>
		Before you can login we'd like to request a few details confirm your account. After submitting this form you'll be promted to upload family member photos, after which you'll receive an email with account details to login to Gibbon for the first time.
	</p>

	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/parentInformationProcess.php?step=1">
		<table cellspacing='0' style="width: 100%">
			<tr>
				<td style="width:160px;">
					<b><?php echo __($guid, "Your Email Address");?></b>
				</td>
				<td class="right">
					<input name="email" id="email" type="text" style="width:97.5%" value="<?php echo $email; ?>">
					<script type="text/javascript">
    					var email=new LiveValidation('email');
    					email.add(Validate.Presence);
    				</script>
				</td>
			</tr>
			<tr>
				<td colspan="2">
				<?php echo __($guid, "Please confirm your account by entering the birthdate of your child at TIS. If there is more than one child in your family, please enter the birthdate of the oldest child currently enrolled at TIS.");?>
				</td>
			</tr>
			<tr>
				<td>
					<b><?php echo __($guid, "Child's Birthdate");?></b>
				</td>
				<td class="right">
					
				<table class="blank mini">
					<tr>
						<td>
							<select id="birthyear" name="birthyear">
							<?php
								for ($i = 2016; $i>=(2016-20);$i--) {
									echo '<option value='.$i.'>'.$i;
								}
							?>
							</select>
						</td>
						<td>
							<select id="birthmonth" name="birthmonth">
								<option value="01">January</option>
								<option value="02">February</option>
								<option value="03">March</option>
								<option value="04">April</option>
								<option value="05">May</option>
								<option value="06">June</option>
								<option value="07">July</option>
								<option value="08">August</option>
								<option value="09">September</option>
								<option value="10">October</option>
								<option value="11">November</option>
								<option value="12">December</option>
							</select>
						</td>
						<td>
							<input id="birthday" name="birthday" type="text" style="width:30px" value="" maxlength=2>
						</td>
						<script type="text/javascript">
	    					var birthday=new LiveValidation('birthday');
	    					birthday.add(Validate.Presence);
	    				</script>
					</tr>
				</table>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="right">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
}
else {
	//Get URL parameters
	$proceed = false;


	if (!empty($_SESSION[$guid]['username'])) {
		// Logged in users
		$gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
		$gibbonPersonResetID='';
		$key='';
		$input = $_SESSION[$guid]['email'];
		$proceed = true;
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
		    } catch (PDOException $e) {
		        echo "<div class='error'>".$e->getMessage().'</div>';
		    }

		    if ($result->rowCount() == 1) {
		    	$gibbonPersonID = $result->fetchColumn(0);
		    	$proceed = true;
		    }
		}
	}

	if ($proceed == false) {
		echo "<div class='error'>";
		echo __($guid, 'Your reset request is invalid: you may not proceed.');
		echo '</div>';
	} else {

		if (empty($_SESSION[$guid]['username'])) {
			echo "<div class='success'>";
			echo __($guid, 'Account confirmation successfull, please continue.');
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
			<table class='smallIntBorder fullWidth' cellspacing='0'>
				<tr class='break'>
	    			<td colspan=3>
	    				<h3>
	    					<?php echo __($guid, 'Upload Family Member Photos'); ?>
	    				</h3>
	    				<p>
	    					The addition of the new North Wing affords TIS the opportunity to review and enhance our security on campus. A Parent ID card system will be implemented at the school to ensure the safety of all TIS students and their families.
	    				</p>
	    				<p>
	    					Please take the time now to upload a passport-sized photo for family members and helpers who may need an ID card. Parent ID cards will only be provided for those individuals with valid photos on file: if you do not have a photo available now you will have the opporunity to upload it later. Please note, however, that your Parent IDs will be processed faster if the photos are included here.
	    				</p>
	    				<p>
	    					For the best results your photos should be <u>passport-sized, good quality and on a plain background</u>. You can move, zoom and rotate your photos after uploading to ensure they fit the available frame. ID cards may not issued if the photo provided is not clear and easily recognizable.
	    				</p>
	    				<p>
	    					<b style='color:#c0292d;'>Processing and issuing of Parent IDs will begin mid to late Febriary 2017.</b>
	    				</p>
	    			</td>
	    		</tr>
	    		<?php
	    			while ($familyAdult = $result->fetch()) :
	    		?>
		    		<tr>
		    			<td rowspan=1 style="width:200px;">
							<b><?php echo $familyAdult['officialName']; ?></b><br/>
						</td>
						
						<?php if ( substr($familyAdult['username'], 0, 4) == '1000' && !empty($familyAdult['image_240'])) : ?>
							<td style="width:210px;">
								<b><?php echo __($guid, 'Photo'); ?></b><br/>
								<span class="emphasis small">Staff photos cannot be changed.</span>
							</td>
							<td>
								<div style="width:302px;float:right;">
									<div class="cropit-photo-disabled" style="background-image:url(<?php echo $_SESSION[$guid]['absoluteURL'].'/'.$familyAdult['image_240']; ?>);">&nbsp;</div>
									
								</div>
							</td>
						<?php else : ?>
							<td style="width:210px;">
								<b><?php echo __($guid, 'Photo'); ?></b>
								</td>
								<td>
								<div id="photo<?php echo $familyAdult['username'];?>" class="cropit-photo" style="width:302px;float:right;">
									<div class="cropit-preview" style="border: 2px solid #bbbbbb;">
									</div>

									<img title="Zoom In" src="<?php echo $_SESSION[$guid]['absoluteURL'];?>/themes/Default/img/plus.png" style="width:20px;height:20px;">
									<input type="range" class="cropit-image-zoom-input" style="width:140px;"/>
									<img title="Zoom Out" src="<?php echo $_SESSION[$guid]['absoluteURL'];?>/themes/Default/img/minus.png" style="width:20px;height:20px;">

									<img title="Rotate" src="<?php echo $_SESSION[$guid]['absoluteURL'];?>/themes/Default/img/refresh.png" class="rotate-cw-btn" style="width:20px;height:20px;margin-left:20px;">

									<input type="file" class="cropit-image-input standardWidth" name="file[<?php echo $familyAdult['username'];?>]" id="file[<?php echo $familyAdult['username'];?>]" accept=".jpg,.gif,.jpeg,.png" />
									<input type="hidden" name="attachment[<?php echo $familyAdult['username'];?>]" id="attachment[<?php echo $familyAdult['username'];?>]" value="" />
								</div>

								<script type="text/javascript">
									var photoName = "<?php echo 'photo'.$familyAdult['username'];?>";
									$('#'+photoName).cropit({ imageState: { src: '<?php echo $familyAdult['image_240']; ?>'}, width: 180, height: 240, exportZoom: 2, smallImage: 'allow', initialZoom: 'min' , minZoom: 'fit', maxZoom: 2.0, onImageError: function() { alert('There was an error processing this image, it may not be a recognized file type. Please upload a PNG, JPG, or GIF.'); }});
								</script>
							</td>
						<?php endif; ?>
						
					</tr>
				<?php endwhile; ?>

				<tr class='break'>
	    			<td colspan=3>
	    				<h3>
	    					<?php echo __($guid, 'Additional Photos'); ?>
	    				</h3>
	    				<p>You may optionally upload photos for helpers, drivers and extended family members who may be on campus for student dropoff or pickup. ID cards will only be provided for additional people if there is a valid photo on file.</p>
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
	    		?>
					<tr>
						<td rowspan=3 style="width:200px;">
							<b><?php echo __($guid, 'Additional Person').' '.($i+1); ?></b>
						</td>
						<td style="width:210px;">
							<b><?php echo __($guid, 'Photo'); ?></b><br/>
							<span class="emphasis small">
							</span>
						</td>
						<td>
							<div id="additionalPhoto<?php echo $i;?>" class="cropit-photo" style="width:302px;float:right;">
								<div class="cropit-preview" style="border: 2px solid #bbbbbb;"></div>

								<img title="Zoom In" src="<?php echo $_SESSION[$guid]['absoluteURL'];?>/themes/Default/img/plus.png" style="width:20px;height:20px;">
								<input type="range" class="cropit-image-zoom-input" style="width:140px;"/>
								<img title="Zoom Out" src="<?php echo $_SESSION[$guid]['absoluteURL'];?>/themes/Default/img/minus.png" style="width:20px;height:20px;">

								<img title="Rotate" src="<?php echo $_SESSION[$guid]['absoluteURL'];?>/themes/Default/img/refresh.png" class="rotate-cw-btn" style="width:20px;height:20px;margin-left:20px;">

								<input type="file" class="cropit-image-input standardWidth" name="additionalFile<?php echo $i;?>" id="additionalFile<?php echo $i;?>" accept=".jpg,.gif,.jpeg,.png" />
								<input type="hidden" name="attachmentAdditional[<?php echo $i;?>]" id="attachmentAdditional[<?php echo $i;?>]" value="" />
							</div>

							<script type="text/javascript">
								var photoName = "<?php echo 'additionalPhoto'.$i;?>";
								$('#'+photoName).cropit({ imageState: { src: '<?php echo $additionalPerson['image_240']; ?>'}, width: 180, height: 240, exportZoom: 2, smallImage: 'allow', initialZoom: 'min' , minZoom: 'fit', maxZoom: 2.0, onImageError: function() { alert('There was an error processing this image, it may not be a recognized file type. Please upload a PNG, JPG, or GIF.'); } 
								});
							</script>
						</td>
					</tr>
					<tr>
						<td style="width:160px;">
							<b><?php echo __($guid, "Name");?></b>
						</td>
						<td class="right">
							<input class="standardWidth" name="additionalName[<?php echo $i;?>]" type="text" value="<?php echo $additionalPerson['name'];?>">
						</td>
					</tr>
					<tr>
						<td style="width:160px;">
							<b><?php echo __($guid, "Relationship");?></b>
						</td>
						<td class="right">
							<input class="standardWidth" name="additionalRelationship[<?php echo $i;?>]" type="text" value="<?php echo $additionalPerson['relationship'];?>">
						</td>
					</tr>
				<?php endfor; ?>


				<script type="text/javascript">
					$(document).ready(function(){
						//$imageCropper = $('#image-cropper');
						//$imageCropper.cropit({ width: 240, height: 320 });

						// Handle rotation
						$('.rotate-cw-btn').click(function() {
							$(this).parent().cropit('rotateCW');
						});


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

							$(this).unbind('submit').submit(); // continue the submit unbind preventDefault
						});
					});
				</script>

				<?php if (empty($_SESSION[$guid]['username'])) : ?>
					<tr class="break">
						<td colspan="3"><br/>
						<p>
						After pressing submit <b style='color:#c0292d;'>your login details will be emailed to you</b>. If you do not receive an email within a few minutes please check your spam folder as some emails may end up there.
						</p>
						</td>
					</tr>
				<?php endif; ?>
	    		<tr>
	    			<td colspan=2>
	    				<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
	    			</td>
	    			<td class="right">
	    				<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
	    				<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
	    			</td>
	    		</tr>
	    	</table>
	    </form>
		<?php
	}
}
?>
