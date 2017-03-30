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

@session_start();

use Gibbon\Forms\Form;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_family_photos.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Update Family Photos').'</div>';
        echo '</div>';

        echo '<h2>';
        echo __($guid, 'Choose Family');
        echo '</h2>';

        $gibbonFamilyID = (isset($_GET['gibbonFamilyID']))? $_GET['gibbonFamilyID'] : '';

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

        $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/data_family_photos.php');

        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonFamily.gibbonFamilyID as value, CONCAT(gibbonFamily.name, ' (', GROUP_CONCAT(DISTINCT CONCAT(adult.preferredName, ' ', adult.surname) SEPARATOR ', '), ')') as name FROM gibbonFamily
                JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                JOIN gibbonPerson AS adult ON (gibbonFamilyAdult.gibbonPersonID=adult.gibbonPersonID)
                JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                JOIN gibbonPerson AS student ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID)
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID)
                WHERE student.status='Full'
                AND adult.status='Full'
                AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY gibbonFamily.gibbonFamilyID
                HAVING (count(DISTINCT gibbonFamilyAdult.gibbonPersonID) > 0 AND count(DISTINCT gibbonStudentEnrolment.gibbonPersonID) > 0)
                ORDER BY name";

        $row = $form->addRow();
            $row->addLabel('gibbonFamilyID', __('Family'));
            $row->addSelect('gibbonFamilyID')->fromQuery($pdo, $sql, $data)->isRequired()->placeholder('')->selected($gibbonFamilyID);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();


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
        content: "<?php echo $translation['step2-photo-button']; ?>";
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

        $translation = array();
        $translation['jan']                    = "January";
        $translation['feb']                    = "February";
        $translation['mar']                    = "March";
        $translation['apr']                    = "April";
        $translation['may']                    = "May";
        $translation['jun']                    = "June";
        $translation['jul']                    = "July";
        $translation['aug']                    = "August";
        $translation['sep']                    = "September";
        $translation['oct']                    = "October";
        $translation['nov']                    = "November";
        $translation['dec']                    = "December";
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
        $translation['step2-add-person']       = "Additional Person";
        $translation['step2-photo']            = "Photo";
        $translation['step2-photo-button']     = "Upload a Photo";
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
        $translation['confirm-error1']         = 'Your request failed because the birthdate supplied does not match the one in our records. Please try again, and if the problem persists contact support at %1$s';
        $translation['confirm-error2']         = 'Your request failed because the email address supplied does not match the one in our records. Please try again, and if the problem persists contact support at %1$s';
        $translation['step1-invalid']          = "The request could not proceed. Either the form has already been submitted or session has expired. Please go back to Step 1.";
        $translation['step1-noaccount']        = 'The request could not proceed. Either your account is not currently active in our system, or your family data could not be located. Please try again, and if the problem persists contact support at %1$s';
        $translation['step2-error-photo']      = 'There was an error processing this image, it may not be a recognized file type. Please upload a PNG, JPG, or GIF.';
        $translation['step2-success-new']      = "Account confirmation was successful: you may now log in. Please check your email for login details. If you do not receive an email within a few minutes please check your spam folder as some emails may end up there.";
        $translation['step2-success-exist']    = "Photo upload successful. Your account has already been confirmed: you may now log in with your existing account details.";


        if (!empty($gibbonFamilyID)) {
            echo '<h2>';
            echo __($guid, 'Update Data');
            echo '</h2>';

            try {
                $data = array('gibbonFamilyID' => $gibbonFamilyID);
                $sql = "SELECT familyPerson.gibbonPersonID, familyPerson.username, familyPerson.surname, familyPerson.firstName, familyPerson.officialName, familyPerson.image_240 FROM  gibbonFamilyAdult AS familyAdult JOIN gibbonPerson AS familyPerson ON (familyAdult.gibbonPersonID=familyPerson.gibbonPersonID) WHERE familyAdult.gibbonFamilyID=:gibbonFamilyID ORDER BY familyAdult.gibbonPersonID DESC, familyAdult.contactPriority";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            //Show form
            echo "<form id='photoupload' name='photoupload' method='post' action='".$_SESSION[$guid]['absoluteURL']."/modules/Data Updater/data_family_photosProcess.php?gibbonFamilyID={$gibbonFamilyID}' enctype='multipart/form-data'>";
            ?>
            <table class='smallIntBorder' cellspacing='0' <?php if (isset($_GET['sidebar']) && $_GET['sidebar'] == 'false') echo 'style="max-width:750px;margin: 0 auto;"'; ?>>
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
                    </td>
                </tr>

                <?php
                    for ($i = 0; $i < 3;$i++) :

                        try {
                            $data = array( 'sequenceNumber' => $i, 'gibbonFamilyID' => $gibbonFamilyID);
                            $sql = "SELECT name, relationship, image_240 FROM gibbonFamilyAdditionalPerson WHERE gibbonFamilyID=:gibbonFamilyID AND sequenceNumber=:sequenceNumber";
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
                        <input type="submit" value="<?php echo $translation['submit']; ?>" style='font-size: 16px;padding:6px 10px;height:auto;'>
                    </td>
                </tr>
            </table><br/>
        </form>
        <?php
        }
    }
}
?>
