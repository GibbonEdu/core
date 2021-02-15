<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
*/

namespace Gibbon;

use PHPUnit\Framework\TestCase;
use Gibbon\Contracts\Database\Connection;

/**
 * @covers FileUploader
 */
class FileUploaderTest extends TestCase
{
    private $mockPDO;
    private $mockSession;

    private $fileUploader;

    public function setUp(): void
    {
        // Mock the database results for a gibbonFileExtensions query
        $mockResults = $this->createMock(\PDOStatement::class);
        $mockResults->method('rowCount')
                    ->willReturn(3);
        $mockResults->method('fetchAll')
                    ->willReturn(array(0 => 'foo', 1 => 'bar', 2 => 'baz'));

        // Create a stub for the Gibbon\Contracts\Database\Connection class using mock results
        $this->mockPDO = $this->createMock(Connection::class);
        $this->mockPDO->method('executeQuery')
                      ->willReturn($mockResults);

        // Create a stub for the Gibbon\session class
        $this->mockSession = $this->createMock(session::class);
        $this->mockSession->method('get')
                          ->willReturn(__DIR__);

        $this->fileUploader = new FileUploader($this->mockPDO, $this->mockSession);
    }

    public function testCanValidateFileExtension()
    {
        $this->fileUploader->setFileExtensions(array('foo','bar','baz'));

        $this->assertTrue($this->fileUploader->isFileTypeValid('somefile.bar'));
    }

    public function testCanValidateFileExtensionCaseInsensitive()
    {
        $this->fileUploader->setFileExtensions(array('foo','bar','baz'));

        $this->assertTrue($this->fileUploader->isFileTypeValid('somefile.FOO'));
    }

    public function testCanInvalidateFileExtension()
    {
        $this->fileUploader->setFileExtensions(array('foo','bar','baz'));

        $this->assertFalse($this->fileUploader->isFileTypeValid('somefile.php'));
    }

    public function testWillNotValidateEmptyFileExtension()
    {
        $this->fileUploader->setFileExtensions(array('foo','bar','baz'));

        $this->assertFalse($this->fileUploader->isFileTypeValid('somefile'));
    }

    public function testWillNotValidateInlineFileExtension()
    {
        $this->fileUploader->setFileExtensions(array('foo','bar','baz'));

        $this->assertFalse($this->fileUploader->isFileTypeValid('somefile.foo.php'));
    }

    public function testWillNotValidateSuspiciousFileExtension()
    {
        $this->fileUploader->setFileExtensions(array('foo','bar','baz'));

        $this->assertFalse($this->fileUploader->isFileTypeValid('somefile.foo\'; ?>.php'));
    }

    public function testCanSetFileExtensionsFromArray()
    {
        $extensions = array('foo','bar','baz');
        $this->fileUploader->setFileExtensions($extensions);

        $this->assertEquals($this->fileUploader->getFileExtensions(), $extensions);
    }

    public function testCannotSetFileExtensionsFromNonArray()
    {
        $this->assertFalse($this->fileUploader->setFileExtensions('NotValidArray'));
    }

    public function testCanGetFileExtensionsAsCSV()
    {
        $extensions = array('foo','bar','baz');
        $this->fileUploader->setFileExtensions($extensions);

        $this->assertEquals($this->fileUploader->getFileExtensionsCSV(), "'.foo','.bar','.baz'");
    }

    public function testCanGetFileExtensionsFromDatabase()
    {
        $extensions = array('foo','bar','baz');
        $this->assertEquals($this->fileUploader->getFileExtensions(), $extensions);
    }

    public function testCanGetRandomizedFilename()
    {
        $filename = 'somefile.foo';
        $randomizedName = $this->fileUploader->getRandomizedFilename($filename, __DIR__);

        $this->assertNotEquals($filename, $randomizedName);
    }

    public function testCanDisableRandomizedFilename()
    {
        $this->fileUploader->setFileSuffixType(FileUploader::FILE_SUFFIX_NONE);

        $filename = 'somefile.foo';
        $randomizedName = $this->fileUploader->getRandomizedFilename($filename, __DIR__);

        $this->assertEquals($filename, $randomizedName);
    }

    public function testCanGetUploadsFolderBySpecificDate()
    {
        $timestamp = mktime(0, 0, 0, 4, 1, 2006);

        $returnedFolder = $this->fileUploader->getUploadsFolderByDate($timestamp);
        $expectedFolder = 'uploads/2006/04';

        $this->assertEquals($returnedFolder, $expectedFolder);
    }

    public function testCannotUploadMissingFilename()
    {
        $filename = '';
        $sourcePath = __FILE__;

        $this->assertFalse($this->fileUploader->upload($filename, $sourcePath));
    }

    public function testCannotUploadMissingSourcePath()
    {
        $filename = 'somefile.foo';
        $sourcePath = '';

        $this->assertFalse($this->fileUploader->upload($filename, $sourcePath));
    }

    public function testCannotSetIllegalExtensionsFromArray()
    {
        $this->fileUploader->setFileExtensions(array('foo','php','bar','js','baz','py'));

        $this->assertFalse($this->fileUploader->isFileTypeValid('somefile.php'));
    }

    public function testCannotSetIllegalExtensionsFromDatabase()
    {
        $mockResults = $this->createMock(\PDOStatement::class);
        $mockResults->method('rowCount')->willReturn(6);
        $mockResults->method('fetchAll')->willReturn(array('foo','php','bar','js','baz','py'));

        $mockPDO = $this->createMock(Connection::class);
        $mockPDO->method('executeQuery')
                ->willReturn($mockResults);

        $fileUploaderMock = new FileUploader($mockPDO, $this->mockSession);

        $this->assertEquals($fileUploaderMock->getFileExtensions(), array('foo','bar','baz'));
    }
}
