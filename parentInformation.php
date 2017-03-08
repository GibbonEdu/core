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
	.cropit-preview.cropit-image-loading .loading {
	  opacity: 1;
	}

	.cropit-preview .loading {
	  opacity: 0;
	  width: 180px;
	  margin:110px auto 0;
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
	.cropit-preview:not(.cropit-image-loading)::before {
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


$translation = array();

if ($gibbon->locale->getLocale() == 'zh_HK') {
	$translation['step1']            	   = "第一步";
	$translation['step1-subheading'] 	   = "確認帳戶";
	$translation['step1-message']          = "於登入前請先提供以下幾項資料以便確認帳號。資料獲確認後，  閣下會收到學校發出之電郵，通知家長登入Gibbon帳戶上載家庭成員照片。";
	$translation['step1-email']            = "家長電郵地址";
	$translation['step1-birthday']         = "子女生日日期";
	$translation['step1-birthday-message'] = "登入之帳號將設定為  貴子女之生日日期。若  閣下有多於一位孩子就讀本校，請以最年長的子女之生日日期為登入帳號。";
	$translation['step2']                  = "第二步";
	$translation['step2-subheading']       = "上載家庭成員照片";
	$translation['step2-message']          = "請為需要申請家長證之家庭成員及家務助理上傳照片（照片規格為「證件照」），家長證只供有相片上載之人士申請。當前沒有照片可供上傳者，可於稍後再行上傳。請注意：有上傳照片之家庭成員可快速辦理家長證。";
	$translation['step2-dates']            = "家長證將於2017年3月底前開始辦理。";
	$translation['step2-photo-heading']    = "照片規格：";
	$translation['step2-photo-message']    = "上載之照片最好為證件照大小、人像清晰、背景簡單。照片上傳後可自行縮放大小，以符合要求之尺寸。若上傳之照片人像不清晰，將不能辦理家長證。";
	$translation['step2-add-heading']      = "附加照片";
	$translation['step2-add-message']      = "家長可為會前來學校接送子女之家庭成員或家務助理申請家長證，家長證僅發放予有相片存檔的人士。而學校已有學生照片存檔，家長無須上傳  貴子女之照片。";
	$translation['step2-add-person']      = "申請者照片";
	$translation['step2-photo']            = "照片";
	$translation['step2-name']             = "姓名（身份證明文件姓名）";
	$translation['step2-relationship']     = "關係";
	$translation['step2-helper']           = "助理";
	$translation['step2-driver']           = "司機";
	$translation['step2-family']           = "家人";
	$translation['step2-other']            = "其他";
	$translation['step2-final-message']    = "提交帳號資料後，<b style='color:#c0292d;'>會即時收到學校發出之電郵</b>。若數分鐘後仍未收到有關電郵，請查看  閣下電子電箱內之「垃圾郵件」。";
	$translation['required-message']       = "* 必需填寫";
	$translation['submit']                 = "提交";
	$translation['confirm-success']        = "成功確認帳戶，請繼續下一步。完成資料輸入後請按「提交」按鍵";
	$translation['confirm-error']          = '若資料提交出現錯誤，由於  閣下所填寫之電郵地址與學校紀錄之電郵地址資料不符，無法登入。請重新輸入，再有問題歡迎聯絡學校提供協助 %1$s';
} else {
	$translation['step1']                  = "Step 1";
	$translation['step1-subheading']       = "Account Confirmation";
	$translation['step1-message']          = "Before you can login we'd like to request a few details to confirm your account. After submitting this form you'll be prompted to upload family member photos, after which you'll receive an email with account details to login to Gibbon for the first time.";
	$translation['step1-email']            = "Your Email Address";
	$translation['step1-birthday']         = "Child's Birthdate";
	$translation['step1-birthday-message'] = "Please confirm your account by entering the birthdate of your child at TIS. If there is more than one child in your family, please enter the birthdate of the oldest child currently enrolled at TIS.";
	$translation['step2']                  = "Step 2";
	$translation['step2-subheading']       = "Upload Family Member Photos";
	$translation['step2-message']          = "Please take the time now to upload a passport-sized photo for family members and helpers who will need an ID card. Photo ID cards can only be provided for those individuals with valid photos on file: if you do not have a photo available now you will have the opportunity to upload it later. Please note, however, that your IDs will be processed faster if the photos are included here.";
	$translation['step2-dates']            = "Processing and issuing of Photo IDs will begin mid to late March 2017.";
	$translation['step2-photo-heading']    = "Photo Instructions:";
	$translation['step2-photo-message']    = "For the best results your photos should be passport-sized, good quality and on a plain background. You can move, zoom and rotate your photos after uploading to ensure they fit the available frame. ID cards may not issued if the photo provided is not clear and easily recognizable.";
	$translation['step2-add-heading']      = "Additional Photos";
	$translation['step2-add-message']      = "You may optionally upload photos for helpers, drivers and extended family members who may be on campus for student dropoff or pickup. ID cards will only be provided for additional people if there is a valid photo on file. This does not include current students at TIS, a photo for them is already on file.";
	$translation['step2-add-person']      = "Additional Person";
	$translation['step2-photo']            = "Photo";
	$translation['step2-name']             = "Name (Legal name)";
	$translation['step2-relationship']     = "Relationship";
	$translation['step2-helper']           = "Helper";
	$translation['step2-driver']           = "Driver";
	$translation['step2-family']           = "Family";
	$translation['step2-other']            = "Other";
	$translation['step2-final-message']    = "After pressing submit <b style='color:#c0292d;'>your login details will be emailed to you</b>. If you do not receive an email within a few minutes please check your spam folder as some emails may end up there. ";
	$translation['required-message']       = "* denotes a required field";
	$translation['submit']                 = "Submit";
	$translation['confirm-success']        = "Account confirmation successful, please continue. Be sure to click Submit when you've completed this form.";
	$translation['confirm-error']          = 'Your request failed because the email address supplied does not match the one in our records. Please try again, and if the problem persists contact support at %1$s';
}

$returns = array();
$returns['error0'] = __($guid, 'Email address not set.');
$returns['error3'] = __($guid, 'Failed to send update email.');
$returns['error4'] = __($guid, 'Your request failed due to non-matching passwords.');
$returns['error5'] = __($guid, 'Your request failed due to incorrect or non-existent or non-unique email address.');
$returns['error6'] = __($guid, 'Your request failed because your password to not meet the minimum requirements for strength.');
$returns['error7'] = __($guid, 'Your request failed because your new password is the same as your current password.');
$returns['error8'] = __($guid, 'Your request failed because the birthdate supplied does not match the one in our records, or your family data could not be located. Please try again, and if the problem persists contact support at <a mailto="'.$_SESSION[$guid]['organisationDBAEmail'].'">'.$_SESSION[$guid]['organisationDBAEmail'].'</a>');
$returns['error9'] = sprintf($translation['confirm-error'], '<a mailto="'.$_SESSION[$guid]['organisationDBAEmail'].'">'.$_SESSION[$guid]['organisationDBAEmail'].'</a>');
$returns['success0'] = $translation['confirm-success'];
if (isset($_GET['return'])) {
    returnProcess($guid, $_GET['return'], null, $returns);
    if (stripos($_GET['return'], 'success') !== false) return;
}



if ($step == 1) { ?>

	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'] ?>/parentInformationProcess.php?step=1">
		<table class='smallIntBorder' cellspacing='0' <?php if (isset($_GET['sidebar']) && $_GET['sidebar'] == 'false') echo 'style="max-width:750px;margin: 0 auto;"'; ?>>
			<tr class='break'>
    			<td colspan=2>
    				<h3>
    					<?php echo $translation['step1']; ?> &nbsp;<small><?php echo $translation['step1-subheading']; ?></small>
    				</h3>
    				<p>
						<?php echo $translation['step1-message']; ?>
					</p>
    			</td>
    		</tr>
			<tr>
				<td style="width:160px;">
					<b><?php echo $translation['step1-email']; ?></b>
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
				<br/><p>
					<?php echo $translation['step1-birthday-message']; ?>
					</td>
				</p>
			</tr>
			<tr>
				<td>
					<b><?php echo $translation['step1-birthday']; ?></b>
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
					<input type="submit" value="<?php echo $translation['submit']; ?>"  style='font-size: 16px;padding:6px 10px;height:auto;'>
				</td>
			</tr>
		</table>
	</form>
	<?php
}
else {
	//Get URL parameters
	$proceed = false;
	$message = 'This request is invalid: either the form has already been submitted or session has expired. Please go back to <a href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=parentInformation.php">Step 1</a>';

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
	    	$message = 'The request could not proceed. Either your account is not currently active in our system, or your family data could not be located. Please try again, and if the problem persists contact support at <a mailto="'.$_SESSION[$guid]['organisationDBAEmail'].'">'.$_SESSION[$guid]['organisationDBAEmail'].'</a>';
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
	    				<p>
	    					<b style='color:#c0292d;'><?php echo $translation['step2-dates']; ?></b>
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
									$('#'+photoName).cropit({ <?php echo (!empty($photoURL))? 'imageState: { src:"'.$photoURL.'"},' : ''; ?> width: 180, height: 240, exportZoom: 2, smallImage: 'allow', initialZoom: 'min' , minZoom: 'fit', maxZoom: 3, onImageError: function() { alert('There was an error processing this image, it may not be a recognized file type. Please upload a PNG, JPG, or GIF.'); }});
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
								$('#'+photoName).cropit({ <?php echo (!empty($photoURL))? 'imageState: { src:"'.$photoURL.'"},' : ''; ?> width: 180, height: 240, exportZoom: 2, smallImage: 'allow', initialZoom: 'min' , minZoom: 'fit', maxZoom: 3, onImageError: function() { alert('There was an error processing this image, it may not be a recognized file type. Please upload a PNG, JPG, or GIF.'); }
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

				<?php if (empty($_SESSION[$guid]['username'])) : ?>
					<tr class="break">
						<td colspan="3"><br/>
						<p>
						<?php echo $translation['step2-final-message']; ?>
						</p>
						</td>
					</tr>
				<?php endif; ?>
	    		<tr>
	    			<td colspan=2>
	    				<span class="emphasis small"><?php echo $translation['required-message']; ?></span>
	    			</td>
	    			<td class="right">
	    				<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
	    				<input type="submit" value="<?php echo $translation['submit']; ?>"  onclick='if(confirm("<?php echo 'Are you ready to complete your form? Click cancel if you wish to go back and make changes.'; ?>")) document.forms[0].submit(); else return false;' style='font-size: 16px;padding:6px 10px;height:auto;'>
	    			</td>
	    		</tr>
	    	</table><br/>
	    </form>
		<?php
	}
}
?>
