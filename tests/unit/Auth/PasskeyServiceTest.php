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

namespace Gibbon\Auth\Passkey;

use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\UserGateway;
use PHPUnit\Framework\TestCase;

if (!function_exists(__NAMESPACE__.'\\__')) {
    function __($value)
    {
        return $value;
    }
}

/**
 * @covers \Gibbon\Auth\Passkey\PasskeyService
 */
class PasskeyServiceTest extends TestCase
{
    public function testAllowedOriginUsesTheConfiguredSchemeHostAndPort()
    {
        $this->assertSame(
            'https://school.example.com',
            $this->getAllowedOrigin('https://school.example.com/gibbon')
        );
        $this->assertSame(
            'https://school.example.com:8443',
            $this->getAllowedOrigin('https://school.example.com:8443/gibbon')
        );
        $this->assertSame(
            'http://localhost:8080',
            $this->getAllowedOrigin('http://localhost:8080')
        );
    }

    public function testAllowedOriginRejectsInsecureNonLocalOrigins()
    {
        $this->expectException(\RuntimeException::class);
        $this->getAllowedOrigin('http://school.example.com');
    }

    private function getAllowedOrigin(string $absoluteURL): string
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->with('absoluteURL')->willReturn($absoluteURL);

        $service = new PasskeyService(
            $this->createMock(PasskeyRepository::class),
            $session,
            $this->createMock(UserGateway::class),
            $this->createMock(SettingGateway::class)
        );

        $method = new \ReflectionMethod($service, 'getAllowedOrigin');

        return $method->invoke($service);
    }
}
