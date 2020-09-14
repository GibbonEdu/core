<?php
/**
 *  Gibbon, Flexible & Open School System
 * Copyright (C) 2010, Ross Parker
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

class PreferencesCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->loginAsAdmin();

        $I->amOnPage('/index.php?q=preferences.php');
    }

    // tests
    public function updatePreferences(AcceptanceTester $I)
    {
        $I->wantTo('update my preferences');

        // Grab Original Settings --------------------------------------

        $originalFormValues = $I->grabAllFormValues('#preferences');
        $I->seeInFormFields('#preferences', $originalFormValues);

        // Make Changes ------------------------------------------------

        $newFormValues = array(
            'calendarFeedPersonal' => 'testing@testing.test',
            'personalBackground' => 'http://testing.test/personalBackground',
        );

        $I->selectOption('gibbonThemeIDPersonal', '0013');
        $I->selectOption('gibboni18nIDPersonal', '0001');
        $I->selectOption('receiveNotificationEmails', 'N');

        $I->submitForm('#preferences', $newFormValues, 'Submit');


        // Verify Results ----------------------------------------------

        $I->see('Your request was completed successfully.', '.success');
        $I->seeInFormFields('#preferences', $newFormValues);

        // Restore Original Settings -----------------------------------

        $I->submitForm('#preferences', $originalFormValues, 'Submit');
        $I->see('Your request was completed successfully.', '.success');
        $I->seeInFormFields('#preferences', $originalFormValues);
    }

    public function updatePassword(AcceptanceTester $I)
    {
        $I->wantTo('reset my password');

        // Change Password
        $I->fillField('password', '7SSbB9FZN24Q');
        $I->fillField('passwordNew', 'UnFZLTtJ9!');
        $I->fillField('passwordConfirm', 'UnFZLTtJ9!');
        $I->click('Submit');

        $I->seeSuccessMessage();

        // Logout
        $I->click('Logout', 'a');
        $I->see('Login');

        // Try new password
        $I->fillField('username', 'testingadmin');
        $I->fillField('password', 'UnFZLTtJ9!');
        $I->click('Login');

        // Logged In
        $I->see('Staff Dashboard', 'h2');
        $I->see('System Admin', 'a');

        // Restore original password
        $I->updateFromDatabase('gibbonPerson', array(
            'passwordStrong' => '015261d879c7fc2789d19b9193d189364baac34a98561fa205cd5f37b313cdb0',
            'passwordStrongSalt' => '/aBcEHKLnNpPrsStTUyz47',
            'failCount' => '0',
        ), array('username' => 'testingadmin'));    }
}
