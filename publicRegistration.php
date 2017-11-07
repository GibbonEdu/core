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

@session_start();

$proceed = false;

if (isset($_SESSION[$guid]['username']) == false) {
    $enablePublicRegistration = getSettingByScope($connection2, 'User Admin', 'enablePublicRegistration');
    if ($enablePublicRegistration == 'Y') {
        $proceed = true;
    }
}

if ($proceed == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > </div><div class='trailEnd'>".$_SESSION[$guid]['organisationNameShort'].' '.__($guid, 'Public Registration').'</div>';
    echo '</div>';

    $publicRegistrationMinimumAge = getSettingByScope($connection2, 'User Admin', 'publicRegistrationMinimumAge');

    $returns = array();
    $returns['fail5'] = sprintf(__($guid, 'Your request failed because you do not meet the minimum age for joining this site (%1$s years of age).'), $publicRegistrationMinimumAge);
    $returns['fail7'] = __($guid, 'Your request failed because your password to not meet the minimum requirements for strength.');
    $returns['success1'] = __($guid, 'Your registration was successfully submitted and is now pending approval. Our team will review your registration and be in touch in due course.');
    $returns['success0'] = __($guid, 'Your registration was successfully submitted, and you may now log into the system using your new username and password.');
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    //Get intro
    $intro = getSettingByScope($connection2, 'User Admin', 'publicRegistrationIntro');
    if ($intro != '') {
        echo '<h3>';
        echo __($guid, 'Introduction');
        echo '</h3>';
        echo '<p>';
        echo $intro;
        echo '</p>';
    }

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/passwordResetProcess.php?step=1');

    $form->setClass('smallIntBorder fullWidth');
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $form->addRow()->addHeading(__('Account Details'));

    $row = $form->addRow();
        $row->addLabel('surname', __('Surname'));
        $row->addTextField('surname')->isRequired()->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('firstName', __('First Name'));
        $row->addTextField('firstName')->isRequired()->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('email', __('Email'))->description(__('Must be unique.'));
        $row->addEmail('email')->maxLength(50)->isRequired();

    $row = $form->addRow();
        $row->addLabel('gender', __('Gender'));
        $row->addSelectGender('gender')->isRequired();

    $row = $form->addRow();
        $row->addLabel('dob', __('Date of Birth'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
        $row->addDate('dob')->isRequired();

    $row = $form->addRow();
        $row->addLabel('username', __('Username'))->description(__('Must be unique.'));
        $row->addTextField('username')->maxLength(20)->isRequired();
        $form->addRow()->addContent('<div class="LV_validation_message LV_invalid" id="username_availability_result"></div><br/>');

    $policy = getPasswordPolicy($guid, $connection2);
    if ($policy != false) {
        $form->addRow()->addAlert($policy, 'warning');
    }

    $row = $form->addRow();
        $row->addLabel('passwordNew', __('Password'));
        $column = $row->addColumn('passwordNew')->addClass('inline right');
        $column->addButton(__('Generate Password'))->addClass('generatePassword');
        $password = $column->addPassword('passwordNew')->isRequired()->maxLength(30);

    $alpha = getSettingByScope($connection2, 'System', 'passwordPolicyAlpha');
    $numeric = getSettingByScope($connection2, 'System', 'passwordPolicyNumeric');
    $punctuation = getSettingByScope($connection2, 'System', 'passwordPolicyNonAlphaNumeric');
    $minLength = getSettingByScope($connection2, 'System', 'passwordPolicyMinLength');

    if ($alpha == 'Y') {
        $password->addValidation('Validate.Format', 'pattern: /.*(?=.*[a-z])(?=.*[A-Z]).*/, failureMessage: "'.__('Does not meet password policy.').'"');
    }
    if ($numeric == 'Y') {
        $password->addValidation('Validate.Format', 'pattern: /.*[0-9]/, failureMessage: "'.__('Does not meet password policy.').'"');
    }
    if ($punctuation == 'Y') {
        $password->addValidation('Validate.Format', 'pattern: /[^a-zA-Z0-9]/, failureMessage: "'.__('Does not meet password policy.').'"');
    }
    if (!empty($minLength) && is_numeric($minLength)) {
        $password->addValidation('Validate.Length', 'minimum: '.$minLength.', failureMessage: "'.__('Does not meet password policy.').'"');
    }

    $privacyStatement = getSettingByScope($connection2, 'User Admin', 'publicRegistrationPrivacyStatement');
    if ($privacyStatement != '') {
        $form->addRow()->addHeading(__('Privacy Statement'));
        $form->addRow()->addContent($privacyStatement);
    }

    $agreement = getSettingByScope($connection2, 'User Admin', 'publicRegistrationAgreement');
    if ($agreement != '') {
        $form->addRow()->addHeading(__('Agreement'));
        $form->addRow()->addContent($agreement);

        $row = $form->addRow();
            $row->addLabel('agreement', __('Do you agree to the above?'));
            $row->addCheckbox('agreement')->isRequired()->prepend('Yes');
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    ?>
    <script type="text/javascript">
        $(document).ready(function(){
            $('#username').on('input', function(){
                if ($('#username').val() == '') {
                    $('#username_availability_result').html('');
                    return;
                }
                $('#username_availability_result').html('<?php echo __($guid, "Checking availability...") ?>');
                $.ajax({
                    type : 'POST',
                    data : { username: $('#username').val() },
                    url: "./publicRegistrationCheck.php",
                    success: function(responseText){
                        if(responseText == 0){
                            $('#username_availability_result').html('<?php echo __('Username available'); ?>');
                            $('#username_availability_result').switchClass('LV_invalid', 'LV_valid');
                        }else if(responseText > 0){
                            $('#username_availability_result').html('<?php echo __('Username already taken'); ?>');
                            $('#username_availability_result').switchClass('LV_valid', 'LV_invalid');
                        }
                    }
                });
            });
        });
    </script>
    <script type="text/javascript">
        // Password Generation
        $(".generatePassword").click(function(){
            var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789![]{}()%&*$#^~@|';
            var text = '';
            for(var i=0; i < <?php echo $minLength + 4 ?>; i++) {
                if (i==0) { text += chars.charAt(Math.floor(Math.random() * 26)); }
                else if (i==1) { text += chars.charAt(Math.floor(Math.random() * 26)+26); }
                else if (i==2) { text += chars.charAt(Math.floor(Math.random() * 10)+52); }
                else if (i==3) { text += chars.charAt(Math.floor(Math.random() * 19)+62); }
                else { text += chars.charAt(Math.floor(Math.random() * chars.length)); }
            }
            $('input[name="passwordNew"]').val(text);
            $('input[name="passwordConfirm"]').val(text);
            alert('<?php echo __('Copy this password if required:') ?>' + '\r\n\r\n' + text) ;
        });
    </script>

	<?php
    //Get postscrript
    $postscript = getSettingByScope($connection2, 'User Admin', 'publicRegistrationPostscript');
    if ($postscript != '') {
        echo '<h2>';
        echo __($guid, 'Further Information');
        echo '</h2>';
        echo "<p style='padding-bottom: 15px'>";
        echo $postscript;
        echo '</p>';
    }
}
?>
