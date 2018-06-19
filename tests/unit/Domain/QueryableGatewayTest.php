<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon\Domain;

use PHPUnit\Framework\TestCase;
use Gibbon\Domain\QueryableGateway;

/**
 * @covers QueryableGateway
 */
class QueryableGatewayTest extends TestCase
{
    private $gateway;

    public function setUp()
    {
        $this->gateway = $this
            ->getMockBuilder(QueryableGateway::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    public function testCanCreateQueryCriteria()
    {
        $criteria = $this->gateway->newQueryCriteria();

        $this->assertInstanceOf(QueryCriteria::class, $criteria);
    }
}
