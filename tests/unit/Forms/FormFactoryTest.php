<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\Forms;

use PHPUnit\Framework\TestCase;
use Gibbon\Forms\FormFactoryInterface;

/**
 * @covers FormFactory
 */
class FormFactoryTest extends TestCase
{
    public function testCanBeCreatedStatically()
    {
        $this->assertInstanceOf(
            FormFactory::class,
            FormFactory::create()
        );
    }
}
