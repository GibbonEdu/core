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
        $I->submitForm('form[id=loginForm]', [
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

    public function seeSuccessMessage($text = 'Your request was completed successfully.')
    {
        return $this->see($text, '.success');
    }

    public function seeErrorMessage($text = '')
    {
        return $this->see($text, '.error');
    }

    public function seeWarningMessage($text = '')
    {
        return $this->see($text, '.warning');
    }

    public function grabValueFromURL($param)
    {
        return $this->grabFromCurrentUrl('/'.$param.'=([^=&\s]+)/');
    }

    public function grabEditIDFromURL()
    {
        return $this->grabFromCurrentUrl('/editID=(\d+)/');
    }

    public function selectFromDropdown($selector, $n)
    {
        $n = intval($n);

        if ($n < 0) {
            $option = $this->grabTextFrom('select[name='.$selector.'] option:nth-last-of-type('.abs($n).')');
        } else {
            $option = $this->grabTextFrom('select[name='.$selector.'] option:nth-of-type('.$n.')');
        }

        $this->selectOption($selector, $option);
    }

    public function amOnModulePage($module, $page, $params = null)
    {
        if (mb_stripos($page, '.php') === false) {
            $page .= '.php';
        }

        $url = sprintf('/index.php?q=/modules/%1$s/%2$s', $module, $page);

        if (!empty($params)) {
            $url .= '&'.http_build_query($params);
        }

        return $this->amOnPage($url);
    }
}
