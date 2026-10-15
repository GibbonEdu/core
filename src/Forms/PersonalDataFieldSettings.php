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

namespace Gibbon\Forms;

use Gibbon\Domain\System\SettingGateway;

/**
 * Personal Data Field Settings
 *
 * @version v31
 * @since   v31
 */
class PersonalDataFieldSettings
{
    public const CANNOT_DISABLE = ['firstName', 'officialName'];

    protected const LEGACY_REQUIRED = ['surname', 'preferredName', 'officialName', 'title', 'firstName'];
    protected const ROLE_CATEGORIES = ['Staff', 'Student', 'Parent', 'Other'];
    protected const MERGE_PRIORITY = ['required', '', 'readonly', 'hidden', 'disabled'];

    protected SettingGateway $settingGateway;

    protected bool $rawSettingsLoaded = false;
    protected ?array $rawSettings = null;
    protected array $resolved = [];
    protected ?array $hiddenFromExport = null;

    public function __construct(SettingGateway $settingGateway)
    {
        $this->settingGateway = $settingGateway;
    }

    /**
     * Merged field settings for the given role categories.
     *
     * @param string|array $roleCategories
     * @return array
     */
    public function getSettings(string|array $roleCategories = ['Other']): array
    {
        $roleCategories = (array) $roleCategories;
        $cacheKey = implode(',', $roleCategories);

        if (isset($this->resolved[$cacheKey])) {
            return $this->resolved[$cacheKey];
        }

        $raw = $this->loadRawSettings();
        if ($raw === null) {
            $this->resolved[$cacheKey] = [];

            return [];
        }

        $byField = [];
        foreach ($roleCategories as $roleCategory) {
            foreach (($raw[$roleCategory] ?? []) as $name => $value) {
                $byField[$name][] = $value;
            }
        }

        $fields = [];
        foreach ($byField as $name => $values) {
            $fields[$name] = $this->normalizeSetting($name, $this->mergeValues($values));
        }

        $this->resolved[$cacheKey] = $fields;

        return $fields;
    }

    /**
     * Field is shown for these roles. In User Admin, only Disabled is hidden.
     *
     * @param string|array $roleCategories
     * @param bool $admin
     * @return bool
     */
    public function isVisible(string $fieldName, string|array $roleCategories = ['Other'], bool $admin = false): bool
    {
        $setting = $this->settingFor($fieldName, $roleCategories);

        if ($setting === 'disabled') {
            return false;
        }

        return $admin || $setting !== 'hidden';
    }

    /**
     * @param string|array $roleCategories
     * @return bool
     */
    public function isRequired(string $fieldName, string|array $roleCategories = ['Other']): bool
    {
        return $this->settingFor($fieldName, $roleCategories) === 'required';
    }

    /**
     * @param string|array $roleCategories
     * @return bool
     */
    public function isReadOnly(string $fieldName, string|array $roleCategories = ['Other']): bool
    {
        return $this->settingFor($fieldName, $roleCategories) === 'readonly';
    }

    /**
     * True if any role has this field Hidden or Disabled.
     *
     * @param string $fieldName
     * @return bool
     */
    public function isHiddenFromExport(string $fieldName): bool
    {
        $fieldName = $this->normalizeFieldName($fieldName);
        if ($fieldName === '') {
            return false;
        }

        if ($this->hiddenFromExport === null) {
            $this->hiddenFromExport = [];
            $raw = $this->loadRawSettings();
            if (is_array($raw)) {
                foreach (self::ROLE_CATEGORIES as $roleCategory) {
                    foreach (($raw[$roleCategory] ?? []) as $name => $value) {
                        if (!is_string($value)) {
                            continue;
                        }
                        $value = $this->normalizeSetting($name, $value);
                        if ($value === 'hidden' || $value === 'disabled') {
                            $this->hiddenFromExport[$name] = true;
                        }
                    }
                }
            }
        }

        return isset($this->hiddenFromExport[$fieldName]);
    }

    /**
     * Fields to omit when saving. In User Admin, only Disabled is omitted.
     *
     * @param string|array $roleCategories
     * @param bool $admin
     * @return string[]
     */
    public function getHiddenFields(string|array $roleCategories = ['Other'], bool $admin = false): array
    {
        $hidden = [];

        foreach (array_keys($this->getSettings($roleCategories)) as $field) {
            if ($this->isVisible($field, $roleCategories, $admin)) {
                continue;
            }

            $hidden[] = $field;
            if (preg_match('/^phone[1-4]$/', $field)) {
                $hidden[] = $field.'Type';
                $hidden[] = $field.'CountryCode';
            }
        }

        return $hidden;
    }

    /**
     * True if any of the given fields are visible for these roles.
     *
     * @param string[]     $fieldNames
     * @param string|array $roleCategories
     * @return bool
     */
    public function anyVisible(array $fieldNames, string|array $roleCategories = ['Other']): bool
    {
        foreach ($fieldNames as $fieldName) {
            if ($this->isVisible($fieldName, $roleCategories)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $fieldName
     * @return bool
     */
    public function canDisable(string $fieldName): bool
    {
        return !in_array($this->normalizeFieldName($fieldName), self::CANNOT_DISABLE, true);
    }

    /**
     * Map related POST/column names onto the settings key (e.g. phone1Type -> phone1).
     *
     * @param string $fieldName
     * @return string
     */
    public function normalizeFieldName(string $fieldName): string
    {
        if (preg_match('/^(phone[1-4])(Type|CountryCode)?$/', $fieldName, $matches)) {
            return $matches[1];
        }

        return $fieldName;
    }

    /**
     * @param string|array $roleCategories
     * @return string
     */
    protected function settingFor(string $fieldName, string|array $roleCategories): string
    {
        $fieldName = $this->normalizeFieldName($fieldName);

        // No saved settings: keep historical required name fields
        if ($this->loadRawSettings() === null) {
            return in_array($fieldName, self::LEGACY_REQUIRED, true) ? 'required' : '';
        }

        return $this->getSettings($roleCategories)[$fieldName] ?? '';
    }

    /**
     * @param string[] $values
     * @return string
     */
    protected function mergeValues(array $values): string
    {
        foreach (self::MERGE_PRIORITY as $priority) {
            if (in_array($priority, $values, true)) {
                return $priority;
            }
        }

        return '';
    }

    /**
     * firstName and officialName cannot be stored as disabled.
     *
     * @param string $fieldName
     * @param string $value
     * @return string
     */
    protected function normalizeSetting(string $fieldName, string $value): string
    {
        if ($value === 'disabled' && !$this->canDisable($fieldName)) {
            return '';
        }

        return $value;
    }

    /**
     * @return array|null
     */
    protected function loadRawSettings(): ?array
    {
        if ($this->rawSettingsLoaded) {
            return $this->rawSettings;
        }

        $this->rawSettingsLoaded = true;

        try {
            $decoded = @unserialize(
                $this->settingGateway->getSettingByScope('User Admin', 'personalDataUpdaterRequiredFields')
            );
            if (!is_array($decoded)) {
                return $this->rawSettings;
            }

            if (isset($decoded['Staff']) || isset($decoded['Student'])) {
                $this->rawSettings = $decoded;
            } else {
                // Legacy flat Y/N settings become role-keyed required or blank
                $flat = [];
                foreach ($decoded as $name => $value) {
                    if ($name === 'flag' || is_array($value)) {
                        continue;
                    }
                    $flat[$name] = ($value === 'Y') ? 'required' : '';
                }
                $this->rawSettings = [];
                foreach (self::ROLE_CATEGORIES as $roleCategory) {
                    $this->rawSettings[$roleCategory] = $flat;
                }
                if (isset($decoded['flag'])) {
                    $this->rawSettings['flag'] = $decoded['flag'];
                }
            }
        } catch (\Throwable $e) {
            $this->rawSettings = null;
        }

        return $this->rawSettings;
    }
}
