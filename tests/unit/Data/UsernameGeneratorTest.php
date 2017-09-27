<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

namespace Gibbon\Data;

use PHPUnit\Framework\TestCase;

/**
 * @covers UsernameGenerator
 */
class UsernameGeneratorTest extends TestCase
{
    private $usernameGenerator;

    public function setUp()
    {
        // Create a stub for the Gibbon\sqlConnection class
        $mockPDO = $this->createMock(\Gibbon\sqlConnection::class);

        // Defines a predefined list of usernames that will pass/fail uniqueness check
        $uniqueUsernameMap = array(
            array('foo', true),
            array('bar', false),
            array('foobar', true),
            array('barbar', false),
            array('barbar1', true),
            array('foo001', true),
            array('bar0005', false),
            array('bar0010', true),
            array('iñtërnâtiônàlizætiøn', true),
        );

        // Build a mock of the UsernameGenerator to overwrite the isUsernameUnique method
        $mock = $this->getMockBuilder(UsernameGenerator::class)
             ->setMethods(['isUsernameUnique'])
             ->setConstructorArgs(array($mockPDO))
             ->getMock();
        $mock->method('isUsernameUnique')
             ->will($this->returnValueMap($uniqueUsernameMap));

        $this->usernameGenerator = $mock;
    }

    public function testThisMustFail()
    {
        $this->assertTrue(false);
    }

    public function testCanCheckUniqueUsername()
    {
        $this->assertTrue($this->usernameGenerator->isUsernameUnique('foo'));
    }

    public function testCanCheckNonUniqueUsername()
    {
        $this->assertFalse($this->usernameGenerator->isUsernameUnique('bar'));
    }

    public function testCanGenerateUsername()
    {
        $this->usernameGenerator->addToken('one', 'foo');
        $this->usernameGenerator->addToken('two', 'bar');

        $this->assertEquals('foobar', $this->usernameGenerator->generate('[one][two]'));
    }

    public function testCanIncrementNonUniqueUsername()
    {
        $this->usernameGenerator->addToken('one', 'bar');
        $this->usernameGenerator->addToken('two', 'bar');

        $this->assertEquals('barbar1', $this->usernameGenerator->generate('[one][two]'));
    }

    public function testCanGenerateNumericUsername()
    {
        $this->usernameGenerator->addNumericToken('number', 0, 3, 1);

        $this->assertEquals('foo001', $this->usernameGenerator->generate('foo[number]'));
    }

    public function testCanIncrementNumericUsername()
    {
        $this->usernameGenerator->addNumericToken('number', 0, 4, 5);

        $this->assertEquals('bar0010', $this->usernameGenerator->generate('bar[number]'));
    }

    public function testWillRemoveInvalidChars()
    {
        $this->usernameGenerator->addToken('one', 'f-o-o-');
        $this->usernameGenerator->addToken('two', 'b a r');

        $this->assertEquals('foobar', $this->usernameGenerator->generate('[one][two]'));
    }

    public function testWillEnforceLowerCase()
    {
        $this->usernameGenerator->addToken('one', 'FoO');
        $this->usernameGenerator->addToken('two', 'bAr');

        $this->assertEquals('foobar', $this->usernameGenerator->generate('[one][two]'));
    }

    public function testWillLimitTokenLength()
    {
        $this->usernameGenerator->addToken('one', 'fooooooo');
        $this->usernameGenerator->addToken('two', 'barrrrrr');

        $this->assertEquals('foobar', $this->usernameGenerator->generate('[one:3][two:3]'));
    }

    public function testWillHandleMultibyteStrings()
    {
        $this->usernameGenerator->addToken('one', 'iñtërnâtiônàl');
        $this->usernameGenerator->addToken('two', 'izætiøn');

        $this->assertEquals('iñtërnâtiônàlizætiøn', $this->usernameGenerator->generate('[one][two]'));
    }

    public function testWillNotReturnBlankUsername()
    {
        $this->usernameGenerator->addToken('one', ' ');
        $this->usernameGenerator->addToken('two', ' ');

        $this->assertNotEquals('', $this->usernameGenerator->generate('[one][two]'));
    }
}
