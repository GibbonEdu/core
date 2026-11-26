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

declare(strict_types=1);

namespace Gibbon\Filesystem;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Gibbon\Filesystem\Filesystem
 */
class FilesystemTest extends TestCase
{
    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturePath = sys_get_temp_dir().'/FilesystemTest-'.bin2hex(random_bytes(8));
        mkdir($this->fixturePath, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeFixtureDirectory($this->fixturePath);
        parent::tearDown();
    }

    /**
     * @dataProvider versionFileProvider
     */
    public function testReadsTheVersionFromAVersionFileWithoutRunningIt(?string $contents, ?string $expected): void
    {
        $path = $this->fixturePath.'/version.php';
        if ($contents !== null) {
            file_put_contents($path, $contents);
        }

        $this->assertSame($expected, (new Filesystem())->readVersion($path));
    }

    public static function versionFileProvider(): array
    {
        return [
            'single quotes' => ["<?php\n\$version = '30.0.03';\n", '30.0.03'],
            'double quotes' => ["<?php\n\$version = \"30.0.03\";\n", '30.0.03'],
            'extra spacing' => ["<?php\n\$version   =   '30.0.03'  ;\n", '30.0.03'],
            'a comment first' => ["<?php\n/** Sets version. */\n\$version = '31.0.00';\n\$other = 'x';\n", '31.0.00'],
            'not run as code' => ["<?php\ndie('executed');\n\$version = '30.0.03';\n", '30.0.03'],
            'no version' => ["<?php\n\$other = '30.0.03';\n", null],
            'empty version' => ["<?php\n\$version = '';\n", null],
            'missing file' => [null, null],
        ];
    }

    public function testFreeSpaceIsReportedForADirectoryAndNullWhenItCannotBeRead(): void
    {
        $filesystem = new Filesystem();

        $this->assertGreaterThan(0, $filesystem->getFreeSpace($this->fixturePath));
        $this->assertNull($filesystem->getFreeSpace($this->fixturePath.'/missing'));
    }

    public function testContainmentAllowsDescendantsAndCanAllowRootItself(): void
    {
        $validator = new Filesystem();

        $this->assertTrue($validator->isInside($this->fixturePath.'/modules/update.php', $this->fixturePath));
        $this->assertTrue($validator->isInside($this->fixturePath, $this->fixturePath, true));
        $this->assertFalse($validator->isInside($this->fixturePath, $this->fixturePath));
        $this->assertFalse($validator->isInside($this->fixturePath.'-outside/file.php', $this->fixturePath));
    }

    /**
     * @dataProvider safeRelativePathProvider
     */
    public function testRecognizesSafeRelativePaths(string $path): void
    {
        $this->assertTrue((new Filesystem())->isSafeRelativePath($path));
    }

    public static function safeRelativePathProvider(): array
    {
        return [
            'file in root' => ['version.php'],
            'nested path' => ['modules/System Admin/update.php'],
            'current directory prefix' => ['./modules/update.php'],
            'repeated separators' => ['modules//Example/update.php'],
            'windows separators' => ['modules\\Example\\update.php'],
        ];
    }

    /**
     * @dataProvider unsafeRelativePathProvider
     */
    public function testRejectsUnsafeRelativePaths(string $path): void
    {
        $this->assertFalse((new Filesystem())->isSafeRelativePath($path));
    }

    public static function unsafeRelativePathProvider(): array
    {
        return [
            'empty' => [''],
            'parent traversal' => ['modules/../config.php'],
            'parent traversal with windows separators' => ['modules\\..\\config.php'],
            'unix absolute path' => ['/etc/passwd'],
            'windows rooted path' => ['\\Windows\\update.php'],
            'windows absolute path' => ['C:/update.php'],
            'windows drive-relative path' => ['C:update.php'],
            'windows UNC path' => ['\\\\server\\share\\update.php'],
            'null byte' => ["modules/update.php\0.txt"],
            'protected config' => ['config.php'],
            'backup directory' => ['backups/fileUpdate/package.zip'],
            'git directory' => ['.git/config'],
        ];
    }

    public function testPermissionProbeUsesOnlyCallerSuppliedPathAndOperations(): void
    {
        $validator = new Filesystem();
        $probePath = $this->fixturePath.'/caller/path';

        $this->assertTrue($validator->checkPermissions(
            [$probePath],
            ['read', 'write', 'create', 'rename', 'delete']
        ));
        $this->assertDirectoryExists($probePath);
        $this->assertSame([], glob($probePath.'/.gibbon-filesystem-probe-*') ?: []);
        $this->assertTrue($validator->probeDirectory($probePath, ['read']));
        $this->assertFalse($validator->probeDirectory($probePath, ['unknown-operation']));
    }

    public function testPermissionCheckCanReportWithoutCreatingMissingDirectories(): void
    {
        $filesystem = new Filesystem();
        $missing = $this->fixturePath.'/not/yet/created';

        $this->assertTrue($filesystem->checkPermissions([$missing], Filesystem::DEFAULT_OPERATIONS, false));

        $this->assertDirectoryDoesNotExist($this->fixturePath.'/not');
        $this->assertSame([], glob($this->fixturePath.'/.gibbon-filesystem-probe-*') ?: [], 'The probe is cleaned up.');
    }

    public function testPermissionCheckStillCreatesMissingDirectoriesByDefault(): void
    {
        $missing = $this->fixturePath.'/to/be/created';

        $this->assertTrue((new Filesystem())->checkPermissions([$missing]));

        $this->assertDirectoryExists($missing);
    }

    public function testNonCreatingPermissionCheckFailsWhenTheDirectoryAboveCannotBeUsed(): void
    {
        $filesystem = new FilesystemParentProbe(false);

        $this->assertFalse($filesystem->checkPermissions([$this->fixturePath.'/new/directory'], Filesystem::DEFAULT_OPERATIONS, false));
        $this->assertDirectoryDoesNotExist($this->fixturePath.'/new');
    }

    public function testNonCreatingPermissionCheckFailsWhenAFileIsInTheWay(): void
    {
        file_put_contents($this->fixturePath.'/in-the-way', 'a file, not a directory');

        $this->assertFalse((new Filesystem())->checkPermissions(
            [$this->fixturePath.'/in-the-way/new'],
            Filesystem::DEFAULT_OPERATIONS,
            false
        ));
    }

    public function testNonCreatingPermissionCheckProbesAnExistingDirectoryItself(): void
    {
        $this->assertTrue((new Filesystem())->checkPermissions([$this->fixturePath], Filesystem::DEFAULT_OPERATIONS, false));
        $this->assertFalse((new Filesystem())->checkPermissions([''], Filesystem::DEFAULT_OPERATIONS, false));
    }

    public function testParentCapabilityChecksAndCleansAUsableSiblingProbe(): void
    {
        $root = $this->fixturePath.'/web';
        mkdir($root, 0755, true);
        $filesystem = new Filesystem();

        $this->assertTrue($filesystem->checkParentDirectory($root));
        $this->assertSame(
            dirname(realpath($root)).'/backups/fileUpdate-'.substr(hash('sha256', realpath($root)), 0, 8),
            $filesystem->getSecureDirectory($root, 'backups/fileUpdate')
        );
        $this->assertSame([], glob($this->fixturePath.'/.gibbon-filesystem-probe-*') ?: []);
    }

    public function testInstallsSharingAParentGetTheirOwnPreferredDirectory(): void
    {
        $first = $this->fixturePath.'/site-one';
        $second = $this->fixturePath.'/site-two';
        mkdir($first, 0755, true);
        mkdir($second, 0755, true);
        $filesystem = new Filesystem();

        $firstDirectory = $filesystem->getSecureDirectory($first, 'backups/fileUpdate');
        $secondDirectory = $filesystem->getSecureDirectory($second, 'backups/fileUpdate');

        $this->assertNotSame($firstDirectory, $secondDirectory, 'Two installs must not share a lock, staging or backup folder.');
        $this->assertSame(dirname($firstDirectory), dirname($secondDirectory), 'Both are in the shared parent folder.');
        $this->assertMatchesRegularExpression('#/backups/fileUpdate-[0-9a-f]{8}$#', $firstDirectory);
        $this->assertMatchesRegularExpression('#/backups/fileUpdate-[0-9a-f]{8}$#', $secondDirectory);
    }

    public function testAnInstallAlwaysGetsTheSamePreferredDirectory(): void
    {
        $root = $this->fixturePath.'/site-one';
        mkdir($root, 0755, true);

        $directory = (new Filesystem())->getSecureDirectory($root, 'backups/fileUpdate');

        $this->assertSame($directory, (new Filesystem())->getSecureDirectory($root, 'backups/fileUpdate'));
        $this->assertSame($directory, (new Filesystem())->getSecureDirectory($root.'/', 'backups/fileUpdate'), 'A trailing slash makes no difference.');
        $this->assertSame($directory, (new Filesystem())->getSecureDirectory($root, '/backups/fileUpdate/'));
    }

    public function testPreferredDirectoryFallsBackWhenTheHashedDirectoryIsASymlink(): void
    {
        $root = $this->fixturePath.'/web';
        $outside = $this->fixturePath.'/outside';
        mkdir($root, 0755, true);
        mkdir($outside, 0755, true);
        mkdir($this->fixturePath.'/backups', 0755, true);
        $hashed = (new Filesystem())->getSecureDirectory($root, 'backups/fileUpdate');
        $this->assertTrue(symlink($outside, $hashed));

        $this->assertSame($root.'/backups/fileUpdate', (new Filesystem())->getSecureDirectory($root, 'backups/fileUpdate'));
    }

    public function testParentCapabilityFallsBackForMissingRootRootAndInaccessibleParent(): void
    {
        $filesystem = new Filesystem();
        $this->assertFalse($filesystem->checkParentDirectory($this->fixturePath.'/missing'));
        $this->assertFalse($filesystem->checkParentDirectory('/'));

        $root = $this->fixturePath.'/web';
        mkdir($root, 0755, true);
        $inaccessible = new FilesystemParentProbe(false);
        $this->assertFalse($inaccessible->checkParentDirectory($root));
        $this->assertSame($root.'/backups/fileUpdate', $inaccessible->getSecureDirectory($root, 'backups/fileUpdate'));
    }

    public function testPreferredDirectoryFallsBackWhenAParentLocationIsASymlink(): void
    {
        $root = $this->fixturePath.'/web';
        $outside = $this->fixturePath.'/outside';
        mkdir($root, 0755, true);
        mkdir($outside, 0755, true);
        $this->assertTrue(symlink($outside, $this->fixturePath.'/backups'));

        $filesystem = new Filesystem();

        $this->assertSame($root.'/backups/fileUpdate', $filesystem->getSecureDirectory($root, 'backups/fileUpdate'));
    }

    public function testParentCapabilityRejectsASymlinkRoot(): void
    {
        $root = $this->fixturePath.'/web';
        mkdir($root, 0755, true);
        $link = $this->fixturePath.'/web-link';
        $this->assertTrue(symlink($root, $link));

        $this->assertFalse((new Filesystem())->checkParentDirectory($link));
    }

    public function testWipeDirectoryRemovesNestedContentsWithoutFollowingSymlinks(): void
    {
        $directory = $this->fixturePath.'/wipe';
        $outside = $this->fixturePath.'/outside';
        mkdir($directory.'/nested', 0755, true);
        mkdir($outside, 0755, true);
        file_put_contents($directory.'/nested/temporary.txt', 'temporary');
        file_put_contents($outside.'/preserved.txt', 'preserved');
        $this->assertTrue(symlink($outside, $directory.'/linked-outside'));

        $this->assertTrue((new Filesystem())->wipeDirectory($directory));

        $this->assertDirectoryExists($directory);
        $entries = scandir($directory);
        $this->assertSame(['.', '..'], $entries);
        $this->assertFileExists($outside.'/preserved.txt');
    }

    public function testWipeDirectoryHandlesMissingPathsAndRejectsRootSymlinks(): void
    {
        $outside = $this->fixturePath.'/outside';
        mkdir($outside, 0755, true);
        file_put_contents($outside.'/preserved.txt', 'preserved');
        $symlink = $this->fixturePath.'/outside-link';
        $this->assertTrue(symlink($outside, $symlink));

        $filesystem = new Filesystem();
        $this->assertTrue($filesystem->wipeDirectory($this->fixturePath.'/missing'));
        $this->assertFalse($filesystem->wipeDirectory($symlink));
        $this->assertFileExists($outside.'/preserved.txt');
    }

    public function testFileOperationsCreateParentsAndReplaceLinksSafely(): void
    {
        $source = $this->fixturePath.'/source.txt';
        $destination = $this->fixturePath.'/nested/copied.txt';
        $replacement = $this->fixturePath.'/replacement.txt';
        file_put_contents($source, 'source');
        file_put_contents($replacement, 'replacement');

        $filesystem = new Filesystem();
        $this->assertTrue($filesystem->ensureDirectory($this->fixturePath.'/empty/nested'));
        $this->assertTrue($filesystem->copyFile($source, $destination));
        $this->assertSame('source', file_get_contents($destination));
        $this->assertTrue(symlink($source, $this->fixturePath.'/linked.txt'));
        $this->assertTrue($filesystem->replaceFile($replacement, $this->fixturePath.'/linked.txt'));
        $this->assertFalse(is_link($this->fixturePath.'/linked.txt'));
        $this->assertSame('replacement', file_get_contents($this->fixturePath.'/linked.txt'));
    }

    public function testDirectoryCopyReplaceAndDeletionPreserveSafety(): void
    {
        $source = $this->fixturePath.'/source';
        $copied = $this->fixturePath.'/copied';
        $live = $this->fixturePath.'/live';
        mkdir($source.'/nested', 0755, true);
        mkdir($live, 0755, true);
        file_put_contents($source.'/nested/file.txt', 'new');
        file_put_contents($live.'/obsolete.txt', 'old');

        $copiedFiles = [];
        $filesystem = new Filesystem();
        $this->assertTrue($filesystem->copyDirectory($source, $copied, function (string $path) use (&$copiedFiles): void {
            $copiedFiles[] = $path;
        }));
        $this->assertSame([$source.'/nested/file.txt'], $copiedFiles);
        $this->assertSame('new', file_get_contents($copied.'/nested/file.txt'));
        $this->assertTrue($filesystem->replaceDirectory($source, $live));
        $this->assertFileDoesNotExist($live.'/obsolete.txt');
        $this->assertSame('new', file_get_contents($live.'/nested/file.txt'));
        $this->assertTrue($filesystem->deleteIfExists($live.'/nested/file.txt'));
        $this->assertTrue($filesystem->deleteDirectory($live));
        $this->assertDirectoryDoesNotExist($live);
    }

    private function removeFixtureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($path);
    }
}

class FilesystemParentProbe extends Filesystem
{
    public function __construct(private bool $available)
    {
    }

    public function probeDirectory(string $directory, array $operations = self::DEFAULT_OPERATIONS): bool
    {
        return $this->available && parent::probeDirectory($directory, $operations);
    }
}
