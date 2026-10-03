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

namespace Gibbon\Data;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * @covers ImportType
 */
class ImportTypeTest extends TestCase
{
    private function createImportType(array $field): ImportType
    {
        $importType = new ImportType([
            'details' => [
                'type' => 'testImport',
                'name' => 'Test Import',
                'table' => 'gibbonTest',
            ],
            'table' => [
                'custom0017' => $field,
            ],
        ], new PasswordPolicy(false, false, false, 0), null, false);

        $validated = new ReflectionProperty(ImportType::class, 'validated');
        $validated->setAccessible(true);
        $validated->setValue($importType, true);

        return $importType;
    }

    public function testSingleCheckboxUsesYesNoLikeOtherImports()
    {
        $importType = $this->createImportType([
            'name' => 'Single Checkbox',
            'kind' => 'yesno',
            'type' => 'enum',
            'elements' => ['Y', 'N'],
            'args' => ['filter' => 'yesno', 'required' => false, 'checkboxOnValue' => 'Yes'],
        ]);

        $this->assertSame('N', $importType->filterFieldValue('custom0017', ''));
        $this->assertSame('Y', $importType->filterFieldValue('custom0017', 'Yes'));
        $this->assertSame('Y', $importType->filterFieldValue('custom0017', 'Y'));
        $this->assertSame('N', $importType->filterFieldValue('custom0017', 'No'));

        $this->assertSame('N', $importType->validateFieldValue('custom0017', 'N'));
        $this->assertSame('Y', $importType->validateFieldValue('custom0017', 'Y'));

        $this->assertSame('', $importType->storedFieldValue('custom0017', 'N'));
        $this->assertSame('Yes', $importType->storedFieldValue('custom0017', 'Y'));
    }
}
