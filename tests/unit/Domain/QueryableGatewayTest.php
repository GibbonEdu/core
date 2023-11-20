<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

    public function setUp(): void
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
