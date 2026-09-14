<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\UI\Components;

use Gibbon\Session\Session;
use PHPUnit\Framework\TestCase;

/**
 * @covers SidebarHistory
 */
class SidebarHistoryTest extends TestCase
{
    private $guid = 'testguid';

    protected function setUp(): void
    {
        $_SESSION[$this->guid] = [];
    }

    protected function tearDown(): void
    {
        unset($_SESSION[$this->guid]);
    }

    protected function session(int $pageLoads = 1): Session
    {
        $session = new Session($this->guid);
        $session->set('pageLoads', $pageLoads);
        return $session;
    }

    public function testHomePageDetection()
    {
        $this->assertTrue(SidebarHistory::isHomePage(''));
        $this->assertTrue(SidebarHistory::isHomePage('/'));
        $this->assertTrue(SidebarHistory::isHomePage('index.php'));
        $this->assertFalse(SidebarHistory::isHomePage('/modules/Students/student_view.php'));
    }

    public function testFormPageDetectionSkipsCrudPages()
    {
        $this->assertTrue(SidebarHistory::isFormPage('user_manage_add.php', 'user_manage.php'));
        $this->assertTrue(SidebarHistory::isFormPage('user_manage_edit.php', 'user_manage.php'));
        $this->assertTrue(SidebarHistory::isFormPage('user_manage_delete.php', 'user_manage.php'));
        $this->assertFalse(SidebarHistory::isFormPage('user_manage.php', 'user_manage.php'));
        $this->assertFalse(SidebarHistory::isFormPage('student_view_details.php', 'student_view.php'));
    }

    public function testFormPageDetectionKeepsEntryUrlAsStatic()
    {
        $this->assertFalse(SidebarHistory::isFormPage('markbook_edit.php', 'markbook_edit.php'));
        $this->assertTrue(SidebarHistory::isFormPage('markbook_edit_add.php', 'markbook_edit.php'));
    }

    public function testReturnsHomeAsPreviousFromFirstModulePage()
    {
        $history = new SidebarHistory();
        $session = $this->session();

        $history->updateAndGetPrevious($session, [
            'address' => '',
            'url'     => '/index.php',
            'title'   => 'Home',
            'isHome'  => true,
            'isForm'  => false,
        ]);

        $previous = $history->updateAndGetPrevious($session, [
            'address' => '/modules/Students/student_view.php',
            'url'     => '/index.php?q=/modules/Students/student_view.php',
            'title'   => 'View Student Profiles',
            'isHome'  => false,
            'isForm'  => false,
        ]);

        $this->assertSame('/index.php', $previous['url']);
        $this->assertSame('Home', $previous['title']);
    }

    public function testSkipsFormPagesWhenChoosingPrevious()
    {
        $history = new SidebarHistory();
        $session = $this->session();

        $history->updateAndGetPrevious($session, [
            'address' => '/modules/User Admin/user_manage.php',
            'url'     => '/index.php?q=/modules/User Admin/user_manage.php',
            'title'   => 'Manage Users',
            'isHome'  => false,
            'isForm'  => false,
        ]);

        $fromAdd = $history->updateAndGetPrevious($session, [
            'address' => '/modules/User Admin/user_manage_add.php',
            'url'     => '/index.php?q=/modules/User Admin/user_manage_add.php',
            'title'   => 'Add User',
            'isHome'  => false,
            'isForm'  => true,
        ]);

        $this->assertSame('Manage Users', $fromAdd['title']);

        $fromEdit = $history->updateAndGetPrevious($session, [
            'address' => '/modules/User Admin/user_manage_edit.php',
            'url'     => '/index.php?q=/modules/User Admin/user_manage_edit.php',
            'title'   => 'Edit User',
            'isHome'  => false,
            'isForm'  => true,
        ]);

        $this->assertSame('Manage Users', $fromEdit['title']);
        $this->assertSame('/index.php?q=/modules/User Admin/user_manage.php', $fromEdit['url']);
    }

    public function testDoesNotShowBackLinkOnHome()
    {
        $history = new SidebarHistory();
        $session = $this->session();

        $history->updateAndGetPrevious($session, [
            'address' => '/modules/Students/student_view.php',
            'url'     => '/index.php?q=/modules/Students/student_view.php',
            'title'   => 'View Student Profiles',
            'isHome'  => false,
            'isForm'  => false,
        ]);

        $onHome = $history->updateAndGetPrevious($session, [
            'address' => '',
            'url'     => '/index.php',
            'title'   => 'Home',
            'isHome'  => true,
            'isForm'  => false,
        ]);

        $this->assertNull($onHome);
    }

    public function testClearsHistoryOnFirstPageLoad()
    {
        $history = new SidebarHistory();
        $session = $this->session(0);
        $session->set(SidebarHistory::SESSION_KEY, [[
            'address' => '/old',
            'url'     => '/old',
            'title'   => 'Old',
        ]]);

        $previous = $history->updateAndGetPrevious($session, [
            'address' => '/modules/Students/student_view.php',
            'url'     => '/index.php?q=/modules/Students/student_view.php',
            'title'   => 'View Student Profiles',
            'isHome'  => false,
            'isForm'  => false,
        ]);

        $this->assertNull($previous);
    }
}
