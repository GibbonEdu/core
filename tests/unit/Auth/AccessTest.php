<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

namespace Gibbon\Auth\Access;

use Gibbon\Contracts\Database\Result;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\ActionGateway;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Gibbon\Auth\Access\Access
 */
class AccessTest extends TestCase
{
    private $session;
    private $actionGateway;
    private $result;
    private $access;

    public function setUp(): void
    {
        $this->session = $this->createMock(Session::class);
        $this->actionGateway = $this->createMock(ActionGateway::class);
        $this->result = $this->createMock(Result::class);
        $this->access = new Access($this->session, $this->actionGateway);
    }

    public function testCacheIsInitiallyEmpty()
    {
        $this->assertSame(0, $this->access->getCacheCount());
    }

    public function testReturnsUncachedEmptyActionsWhenUsernameIsMissing()
    {
        $this->session->expects($this->exactly(2))
            ->method('has')
            ->with('username')
            ->willReturn(false);
        $this->session->expects($this->never())
            ->method('get');
        $this->actionGateway->expects($this->never())
            ->method('selectModuleActionsByRole');

        $first = $this->access->get('Timetable Admin', 'courseEnrolment_manage');
        $second = $this->access->get('Timetable Admin', 'courseEnrolment_manage');

        $this->assertFalse($first->allows());
        $this->assertNotSame($first, $second);
        $this->assertSame(0, $this->access->getCacheCount());
    }

    public function testDeniesAccessWhenCurrentRoleIsMissing()
    {
        $this->session->expects($this->once())
            ->method('has')
            ->with('username')
            ->willReturn(true);
        $this->session->expects($this->once())
            ->method('get')
            ->with('gibbonRoleIDCurrent')
            ->willReturn(null);
        $this->actionGateway->expects($this->never())
            ->method('selectModuleActionsByRole');

        $this->assertTrue($this->access->denies('Timetable Admin', 'courseEnrolment_manage'));
        $this->assertSame(0, $this->access->getCacheCount());
    }

    public function testLoadsAllowedActionsAndCachesThemByRoute()
    {
        $this->session->expects($this->once())
            ->method('has')
            ->with('username')
            ->willReturn(true);
        $this->session->expects($this->exactly(2))
            ->method('get')
            ->with('gibbonRoleIDCurrent')
            ->willReturn('001');
        $this->actionGateway->expects($this->once())
            ->method('selectModuleActionsByRole')
            ->with('001', 'Timetable Admin', 'courseEnrolment_manage')
            ->willReturn($this->result);
        $this->result->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                ['actionName' => 'Manage Enrolment'],
                ['actionName' => 'View Enrolment'],
            ]);

        $action = $this->access->get('Timetable Admin', 'courseEnrolment_manage');

        $this->assertTrue($action->highest('Manage Enrolment'));
        $this->assertFalse($action->highest('View Enrolment'));
        $this->assertSame($action, $this->access->get('Timetable Admin', 'courseEnrolment_manage'));
        $this->assertTrue($this->access->allows('Timetable Admin', 'courseEnrolment_manage'));
        $this->assertTrue($this->access->allows('Timetable Admin', 'courseEnrolment_manage', 'View Enrolment'));
        $this->assertFalse($this->access->allows('Timetable Admin', 'courseEnrolment_manage', 'Delete Enrolment'));
        $this->assertFalse($this->access->denies('Timetable Admin', 'courseEnrolment_manage', 'Manage Enrolment'));
        $this->assertTrue($this->access->denies('Timetable Admin', 'courseEnrolment_manage', 'Delete Enrolment'));
        $this->assertSame('Manage Enrolment', $this->access->getHighestAction('Timetable Admin', 'courseEnrolment_manage'));
        $this->assertSame(1, $this->access->getCacheCount());
    }

    public function testCachesEmptyResultsForAuthenticatedSessions()
    {
        $this->session->expects($this->once())
            ->method('has')
            ->with('username')
            ->willReturn(true);
        $this->session->expects($this->exactly(2))
            ->method('get')
            ->with('gibbonRoleIDCurrent')
            ->willReturn('001');
        $this->actionGateway->expects($this->once())
            ->method('selectModuleActionsByRole')
            ->with('001', 'Timetable Admin', 'courseEnrolment_manage')
            ->willReturn($this->result);
        $this->result->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        $action = $this->access->get('Timetable Admin', 'courseEnrolment_manage');

        $this->assertFalse($action->allows());
        $this->assertFalse($action->allows('View Enrolment'));
        $this->assertFalse($action->highest('View Enrolment'));
        $this->assertFalse($this->access->allows('Timetable Admin', 'courseEnrolment_manage'));
        $this->assertTrue($this->access->denies('Timetable Admin', 'courseEnrolment_manage'));
        $this->assertNull($this->access->getHighestAction('Timetable Admin', 'courseEnrolment_manage'));
        $this->assertSame(1, $this->access->getCacheCount());
    }
}
