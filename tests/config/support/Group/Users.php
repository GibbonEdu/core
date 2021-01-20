<?php
namespace Group;

use \Codeception\Event\TestEvent;
/**
 * Group class is Codeception Extension which is allowed to handle to all internal events.
 * This class itself can be used to listen events for test execution of one particular group.
 * It may be especially useful to create fixtures data, prepare server, etc.
 *
 * INSTALLATION:
 *
 * To use this group extension, include it to "extensions" option of global Codeception config.
 */

class Users extends \Codeception\Platform\Group
{
    public static $group = 'users';

    public function _before(TestEvent $e)
    {
    }

    public function _after(TestEvent $e)
    {
    }
}
