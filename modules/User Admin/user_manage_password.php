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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_password.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/user_manage.php'>".__($guid, 'Manage Users')."</a> > </div><div class='trailEnd'>".__($guid, 'Reset User Password').'</div>';
    echo '</div>';

    $returns = array();
    $returns['error5'] = __($guid, 'Your request failed because your passwords did not match.');
    $returns['error6'] = __($guid, 'Your request failed because your password to not meet the minimum requirements for strength.');
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    //Check if school year specified
    $gibbonPersonID = $_GET['gibbonPersonID'];
    if ($gibbonPersonID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonPersonID' => $gibbonPersonID);
            $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $search = (isset($_GET['search']))? $_GET['search'] : '';
            if (!empty($search)) {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/user_manage.php&search='.$search."'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            $policy = getPasswordPolicy($guid, $connection2);
            if ($policy != false) {
                echo "<div class='warning'>";
                echo $policy;
                echo '</div>';
            }

            $form = Form::create('resetUserPassword', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/user_manage_passwordProcess.php?gibbonPersonID='.$gibbonPersonID.'&search='.$search);

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $row = $form->addRow();
                $row->addLabel('username', __('Username'));
                $row->addTextField('username')->isRequired()->readOnly()->setValue($values['username']);

            $row = $form->addRow();
                $row->addLabel('passwordNew', __('Password'));
                $column = $row->addColumn('passwordNew')->addClass('inline right');
                $column->addButton(__('Generate Password'))->addClass('generatePassword');
                $column->addPassword('passwordNew')->isRequired()->maxLength(30)->addValidationOption('onlyOnSubmit: true');

            $row = $form->addRow();
                $row->addLabel('passwordConfirm', __('Confirm Password'));
                $row->addPassword('passwordConfirm')->isRequired()->maxLength(30)->addValidationOption('onlyOnSubmit: true')->addValidation('Validate.Confirmation', "match: 'passwordNew'");

            $row = $form->addRow();
                $row->addLabel('passwordForceReset', __('Force Reset Password?'))->description(__('User will be prompted on next login.'));
                $row->addYesNo('passwordForceReset')->isRequired();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
            ?>

            <script type="text/javascript">
                var passwordNew=new LiveValidation('passwordNew');
                passwordNew.add(Validate.Presence);
                <?php
                $alpha = getSettingByScope($connection2, 'System', 'passwordPolicyAlpha');
                $numeric = getSettingByScope($connection2, 'System', 'passwordPolicyNumeric');
                $punctuation = getSettingByScope($connection2, 'System', 'passwordPolicyNonAlphaNumeric');
                $minLength = getSettingByScope($connection2, 'System', 'passwordPolicyMinLength');
                if ($alpha == 'Y') {
                    echo 'passwordNew.add( Validate.Format, { pattern: /.*(?=.*[a-z])(?=.*[A-Z]).*/, failureMessage: "'.__($guid, 'Does not meet password policy.').'" } );';
                }
                if ($numeric == 'Y') {
                    echo 'passwordNew.add( Validate.Format, { pattern: /.*[0-9]/, failureMessage: "'.__($guid, 'Does not meet password policy.').'" } );';
                }
                if ($punctuation == 'Y') {
                    echo 'passwordNew.add( Validate.Format, { pattern: /[^a-zA-Z0-9]/, failureMessage: "'.__($guid, 'Does not meet password policy.').'" } );';
                }
                if (is_numeric($minLength)) {
                    echo 'passwordNew.add( Validate.Length, { minimum: '.$minLength.'} );';
                }
                ?>

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
                    alert('<?php echo __($guid, 'Copy this password if required:') ?>' + '\r\n\r\n' + text) ;
                });
            </script>

			<?php

        }
    }
}
?>
