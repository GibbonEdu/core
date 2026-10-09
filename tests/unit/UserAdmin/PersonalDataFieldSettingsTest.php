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

use Gibbon\Data\ImportType;
use Gibbon\Data\PasswordPolicy;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\PersonalDataFieldSettings;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Gibbon\Forms\PersonalDataFieldSettings
 */
class PersonalDataFieldSettingsTest extends TestCase
{
    protected function createService(?array $settings): PersonalDataFieldSettings
    {
        $settingGateway = $this->createMock(SettingGateway::class);
        $settingGateway->method('getSettingByScope')
            ->with('User Admin', 'personalDataUpdaterRequiredFields')
            ->willReturn($settings === null ? null : serialize($settings));

        return new PersonalDataFieldSettings($settingGateway);
    }

    /** Apply the same setting value to every role category. */
    protected function forAllRoles(array $fields): array
    {
        return [
            'Staff' => $fields,
            'Student' => $fields,
            'Parent' => $fields,
            'Other' => $fields,
        ];
    }

    public function testNormalizePhoneRelatedFieldNames()
    {
        $service = $this->createService([]);

        $this->assertSame('phone1', $service->normalizeFieldName('phone1'));
        $this->assertSame('phone1', $service->normalizeFieldName('phone1Type'));
        $this->assertSame('phone2', $service->normalizeFieldName('phone2CountryCode'));
        $this->assertSame('ethnicity', $service->normalizeFieldName('ethnicity'));
    }

    public function testMissingSettingsKeepFieldsVisibleWithLegacyRequiredNames()
    {
        $service = $this->createService(null);

        $this->assertSame([], $service->getSettings('Student'));
        $this->assertTrue($service->isVisible('ethnicity', 'Student'));
        $this->assertFalse($service->isHiddenFromExport('ethnicity'));
        $this->assertTrue($service->isRequired('surname', 'Student'));
        $this->assertTrue($service->isRequired('preferredName', 'Student'));
        $this->assertTrue($service->isRequired('title', 'Student'));
        $this->assertTrue($service->isRequired('firstName', 'Student'));
        $this->assertTrue($service->isRequired('officialName', 'Student'));
    }

    public function testLegacyFlatYnSettingsAreConverted()
    {
        $service = $this->createService([
            'surname' => 'Y',
            'ethnicity' => 'N',
            'religion' => 'Y',
        ]);

        $this->assertTrue($service->isRequired('surname', 'Student'));
        $this->assertFalse($service->isRequired('ethnicity', 'Student'));
        $this->assertTrue($service->isRequired('religion', 'Staff'));
        $this->assertTrue($service->isVisible('ethnicity', 'Student'));
    }

    public function testHiddenAndRequiredMergePriority()
    {
        $service = $this->createService([
            'Staff' => ['countryOfBirth' => 'hidden', 'religion' => 'hidden'],
            'Student' => ['countryOfBirth' => 'required', 'religion' => ''],
            'Parent' => ['countryOfBirth' => '', 'religion' => 'hidden'],
            'Other' => ['countryOfBirth' => '', 'religion' => 'hidden'],
        ]);

        $settings = $service->getSettings(['Staff', 'Student']);
        $this->assertSame('required', $settings['countryOfBirth']);
        $this->assertSame('', $settings['religion']);
        $this->assertTrue($service->isVisible('countryOfBirth', ['Staff', 'Student']));
        $this->assertTrue($service->isVisible('religion', ['Staff', 'Student']));
    }

    public function testHiddenAppliesPerRoleAndToExport()
    {
        $service = $this->createService([
            'Staff' => ['ethnicity' => '', 'address1' => ''],
            'Student' => ['ethnicity' => 'hidden', 'address1' => 'hidden'],
            'Parent' => ['ethnicity' => '', 'address1' => 'required'],
            'Other' => ['ethnicity' => '', 'address1' => ''],
        ]);

        $this->assertFalse($service->isVisible('ethnicity', 'Student'));
        $this->assertTrue($service->isVisible('ethnicity', 'Staff'));
        $this->assertTrue($service->isHiddenFromExport('ethnicity'));
        $this->assertTrue($service->isHiddenFromExport('address1'));
        $this->assertFalse($service->isHiddenFromExport('email'));
        $this->assertSame(['ethnicity', 'address1'], $service->getHiddenFields('Student'));
        $this->assertTrue($service->anyVisible(['email', 'ethnicity'], 'Student'));
        $this->assertFalse($service->anyVisible(['ethnicity', 'address1'], 'Student'));
    }

    public function testPhoneCompanionsFollowPhoneVisibility()
    {
        $service = $this->createService($this->forAllRoles(['phone1' => 'hidden']));

        $this->assertFalse($service->isVisible('phone1Type', 'Staff'));
        $this->assertTrue($service->isHiddenFromExport('phone1CountryCode'));
        $this->assertSame(['phone1', 'phone1Type', 'phone1CountryCode'], $service->getHiddenFields('Staff'));
    }

    public function testSettingsAreCachedPerRequest()
    {
        $settingGateway = $this->createMock(SettingGateway::class);
        $settingGateway->expects($this->once())
            ->method('getSettingByScope')
            ->willReturn(serialize([
                'Student' => ['dob' => 'hidden'],
                'Staff' => ['dob' => ''],
                'Parent' => ['dob' => ''],
                'Other' => ['dob' => ''],
            ]));

        $service = new PersonalDataFieldSettings($settingGateway);

        $this->assertFalse($service->isVisible('dob', 'Student'));
        $this->assertFalse($service->isVisible('dob', 'Student'));
    }

    public function testSurnameCanBeOptionalWhileCoreNamesStayRequired()
    {
        $service = $this->createService($this->forAllRoles([
            'surname' => '',
            'preferredName' => 'required',
            'firstName' => 'hidden',
            'officialName' => 'readonly',
        ]));

        $this->assertFalse($service->isRequired('surname', 'Student'));
        $this->assertTrue($service->isRequired('preferredName', 'Student'));
        $this->assertTrue($service->isRequired('firstName', 'Student'));
        $this->assertTrue($service->isRequired('officialName', 'Student'));
        $this->assertTrue($service->isVisible('firstName', 'Student'));
        $this->assertTrue($service->isVisible('officialName', 'Student'));
        $this->assertFalse($service->isReadonly('officialName', 'Student'));
        $this->assertFalse($service->isHiddenFromExport('firstName'));
        $this->assertTrue($service->isAlwaysRequired('firstName'));
        $this->assertTrue($service->isAlwaysRequired('officialName'));
        $this->assertFalse($service->isAlwaysRequired('surname'));
        $this->assertSame('required', $service->getSettings('Student')['firstName']);
        $this->assertSame('required', $service->getSettings('Student')['officialName']);
    }

    public function testReadonlySetting()
    {
        $service = $this->createService($this->forAllRoles(['preferredName' => 'readonly']));

        $this->assertTrue($service->isReadonly('preferredName', 'Student'));
        $this->assertFalse($service->isRequired('preferredName', 'Student'));
    }

    public function testImportTypeHonoursHiddenFromExport()
    {
        $service = $this->createService($this->forAllRoles([
            'ethnicity' => 'hidden',
            'email' => '',
            'firstName' => 'hidden',
        ]));

        $importType = new ImportType(
            [
                'details' => ['type' => 'test', 'table' => 'gibbonPerson', 'name' => 'Test'],
                'primaryKey' => 'gibbonPersonID',
                'table' => [
                    'ethnicity' => ['name' => 'Ethnicity', 'desc' => '', 'args' => ['filter' => 'string']],
                    'email' => ['name' => 'Email', 'desc' => '', 'args' => ['filter' => 'string']],
                    'firstName' => ['name' => 'First Name', 'desc' => '', 'args' => ['filter' => 'string']],
                    'linkedField' => ['name' => 'Linked', 'desc' => '', 'args' => ['filter' => 'string', 'linked' => true]],
                    'yamlHidden' => ['name' => 'YAML Hidden', 'desc' => '', 'args' => ['filter' => 'string', 'hidden' => true]],
                ],
            ],
            PasswordPolicy::createNilPolicy(),
            null,
            false,
            $service
        );

        $this->assertTrue($importType->isFieldHidden('ethnicity'));
        $this->assertFalse($importType->isFieldHidden('email'));
        // Always-required names are never hidden from import/export
        $this->assertFalse($importType->isFieldHidden('firstName'));
        // ImportType's own linked/hidden args still apply
        $this->assertTrue($importType->isFieldHidden('linkedField'));
        $this->assertTrue($importType->isFieldHidden('yamlHidden'));
    }

    public function testExportHidesOnlyWhenAnyRoleMarksHidden()
    {
        $service = $this->createService([
            'Staff' => ['dob' => '', 'vehicleRegistration' => 'hidden'],
            'Student' => ['dob' => 'hidden', 'vehicleRegistration' => ''],
            'Parent' => ['dob' => '', 'vehicleRegistration' => ''],
            'Other' => ['dob' => '', 'vehicleRegistration' => ''],
        ]);

        $this->assertTrue($service->isHiddenFromExport('dob'));
        $this->assertTrue($service->isHiddenFromExport('vehicleRegistration'));
        $this->assertFalse($service->isHiddenFromExport('preferredName'));
        $this->assertFalse($service->isHiddenFromExport(''));
        $this->assertFalse($service->isHiddenFromExport('phone1Type')); // phone1 not hidden
    }
}
