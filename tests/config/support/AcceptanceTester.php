<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    protected $breadcrumbEnd = '.trailEnd';

   /**
    * Define custom actions here
    */

    public function login($name, $password)
    {
        $I = $this;

        $I->amOnPage('/');
        $I->submitForm('form[name=loginForm]', [
            'username' => $name,
            'password' => $password
        ]);

        $I->see('Logout', 'a');
    }

    public function loginAsAdmin()
    {
        $this->login('testingadmin', '7SSbB9FZN24Q');
    }

    public function loginAsTeacher()
    {
        $this->login('testingteacher', 'm86GVNLH7DbV');
    }

    public function loginAsStudent()
    {
        $this->login('testingstudent', 'WKLm9ELHLJL5');
    }

    public function loginAsParent()
    {
        $this->login('testingparent', 'UVSf5t7epNa7');
    }

    public function loginAsSupport()
    {
        $this->login('testingsupport', '84BNQAQfNyKa');
    }

    public function clickNavigation($text)
    {
        return $this->click($text, '.linkTop a');
    }

    public function seeBreadcrumb($text)
    {
        return $this->see($text, $this->breadcrumbEnd);
    }

    public function grabValueFromURL($param)
    {
        return $this->grabFromCurrentUrl('/'.$param.'=(\d+)/');
    }

    public function grabEditIDFromURL()
    {
        return $this->grabFromCurrentUrl('/editID=(\d+)/');
    }

    public function selectFromDropdown($selector, $n)
    {
        $option = $this->grabTextFrom('select[name='.$selector.'] option:nth-child(' . $n . ')');
        $this->selectOption($selector, $option);
    }
}
