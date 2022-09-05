<?php
/**
 * Gibbon, Flexible & Open School System
 * Copyright (C) 2010, Ross Parker
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Date: 7/02/2019
 * Time: 16:03
 */

class LoginCest
{
    protected function login(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        // Fill in Login form
        $I->fillField('username', 'testingsupport');
        $I->fillField('password', '84BNQAQfNyKa');
        $I->click('Login');
    }

    /**
     * loginTest
     * @param AcceptanceTester $I
     * @before login
     */
    public function loginTest(AcceptanceTester $I)
    {
        $I->wantTo('login to Gibbon');

        // Logged In
        $I->see('Logout', 'a');
        $I->see('Preferences', 'a');
        $I->see('Support TestUser');
        $lastTimestamp = $I->grabFromDatabase('gibbonPerson', 'lastTimestamp', ['username' => 'testingsupport']);
        $I->seeInDatabase('gibbonLog', ['title' => 'Login - Success', 'serialisedArray LIKE' => '%testingsupport%', 'timestamp >=' => $lastTimestamp]);
    }

    /**
     * logout
     * @param AcceptanceTester $I
     * @before login
     */
    public function logout(AcceptanceTester $I)
    {
        $I->wantTo('logout of Gibbon');

        // Logged In
        $I->see('Support TestUser');

        // Logged back out
        $I->click('Logout', 'a');
        $I->seeElement('#username');
        $I->seeElement('#password');
        $I->dontSee('Logout', 'a');
    }

    /**
     * badPassword
     * @param AcceptanceTester $I
     */
    public function badPassword(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->wantTo('Bad Password Test');
        $I->fillField('username', 'testingsupport');
        $I->fillField('password', '84BfNyKa');
        $I->click('Login');

        // Logged Failed
        $I->seeElement('#username');
        $I->seeElement('#password');
        $I->seeElement('.error');
        $I->see('Incorrect username and password.', '.error');
        $I->dontSee('Logout', 'a');

        $lastFailTimestamp = $I->grabFromDatabase('gibbonPerson', 'lastFailTimestamp', ['username' => 'testingsupport']);
        $lastTimestamp = $I->grabFromDatabase('gibbonPerson', 'lastTimestamp', ['username' => 'testingsupport']);
        $failCount = $I->grabFromDatabase('gibbonPerson', 'failCount', ['username' => 'testingsupport']);

        $I->assertGreaterOrEquals($lastTimestamp, $lastFailTimestamp, 'Failure recorded against user.');
        $I->assertGreaterOrEquals(1, intval($failCount), 'Failure Count incremented.');

        $I->seeInDatabase('gibbonLog', ['title' => 'Login - Failed', 'serialisedArray LIKE' => '%testingsupport%', 'timestamp >=' => $lastTimestamp]);
    }

    /**
     * badUser
     * @param AcceptanceTester $I
     */
    public function badUser(AcceptanceTester $I)
    {
        $username = substr(uniqid('testing_'), 0, 20);
        $I->amOnPage('/');
        $I->wantTo('Bad Username Test');
        $I->fillField('username', $username);
        $I->fillField('password', '5gF$hef6rh7ert45');
        $I->click('Login');

        // Logged Failed
        $I->seeElement('#username');
        $I->seeElement('#password');
        $I->seeElement('.error');
        $I->see('Incorrect username and password.', '.error');
        $I->dontSee('Logout', 'a');
        $I->seeInDatabase('gibbonLog',  ['title' => 'Login - Failed', 'serialisedArray like' => '%'.$username.'%']);
    }

    /**
     * loginAsAdmin
     * @param AcceptanceTester $I
     */
    public function loginAsAdmin(AcceptanceTester $I)
    {
        $I->wantTo('login to Gibbon as an admin');
        $I->loginAsAdmin();

        // Logged In
        $I->see('Staff Dashboard', 'h2');
        $I->see('System Admin', 'a');

    }

    /**
     * loginAsParent
     * @param AcceptanceTester $I
     */
    public function loginAsParent(AcceptanceTester $I)
    {
        $I->wantTo('login to Gibbon as a parent');
        $I->loginAsParent();

        // Logged In
        $I->see('Logout', 'a');

    }

    /**
     * loginAsStudent
     * @param AcceptanceTester $I
     */
    public function loginAsStudent(AcceptanceTester $I)
    {
        $I->wantTo('login to Gibbon as a student');
        $I->loginAsStudent();

        // Logged In
        $I->see('Student Dashboard', 'h2');
    }

    /**
     * loginAsStudent
     * @param AcceptanceTester $I
     */
    public function loginAsSupport(AcceptanceTester $I)
    {
        $I->wantTo('login to Gibbon as support staff');
        $I->loginAsSupport();

        // Logged In
        $I->see('Logout', 'a');
    }

    /**
     * loginAsTeacher
     * @param AcceptanceTester $I
     */
    public function loginAsTeacher(AcceptanceTester $I)
    {
        $I->wantTo('login to Gibbon as a teacher');
        $I->loginAsTeacher();

        // Logged In
        $I->see('Staff Dashboard', 'h2');
    }
}
