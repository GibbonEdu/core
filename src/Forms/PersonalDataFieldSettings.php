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
 * Resolves personal-data field settings from User Admin
 * personalDataUpdaterRequiredFields (per Staff/Student/Parent/Other).
 *
 * Merge priority matches Data Updater: required > blank > readonly > hidden.
 * firstName and officialName are always required.
 *
 * @version v31
 * @since   v31
 */
class PersonalDataFieldSettings
{
    public const ALWAYS_REQUIRED = ['firstName', 'officialName'];

    protected const LEGACY_REQUIRED = ['surname', 'preferredName', 'title'];
    protected const ROLE_CATEGORIES = ['Staff', 'Student', 'Parent', 'Other'];
    protected const MERGE_PRIORITY = ['required', '', 'readonly', 'hidden'];

    protected SettingGateway $settingGateway;

    /** @var array|null|false */
    protected $rawSettings = false;

    /** @var array */
    protected $resolved = [];

    /** @var array|null */
    protected $hiddenFromExport = null;

    public function __construct(SettingGateway $settingGateway)
    {
        $this->settingGateway = $settingGateway;
    }

    /**
     * @param string|string[] $roleCategories
     * @return array fieldName => ''|required|readonly|hidden
     */
    public function getSettings($roleCategories = ['Other']): array
    {
        $roleCategories = (array) $roleCategories;
        $cacheKey = implode(',', $roleCategories);

        if (isset($this->resolved[$cacheKey])) {
            return $this->resolved[$cacheKey];
        }

        $raw = $this->loadRawSettings();
        if ($raw === null) {
            return $this->resolved[$cacheKey] = [];
        }

        $byField = [];
        foreach ($roleCategories as $roleCategory) {
            foreach (($raw[$roleCategory] ?? []) as $name => $value) {
                $byField[$name][] = $value;
            }
        }

        $fields = [];
        foreach ($byField as $name => $values) {
            $fields[$name] = $this->mergeValues($values);
        }

        foreach (self::ALWAYS_REQUIRED as $name) {
            $fields[$name] = 'required';
        }

        return $this->resolved[$cacheKey] = $fields;
    }

    /**
     * @param string|string[] $roleCategories
     */
    public function isVisible(string $fieldName, $roleCategories = ['Other']): bool
    {
        return $this->settingFor($fieldName, $roleCategories) !== 'hidden';
    }

    /**
     * @param string|string[] $roleCategories
     */
    public function isRequired(string $fieldName, $roleCategories = ['Other']): bool
    {
        return $this->settingFor($fieldName, $roleCategories) === 'required';
    }

    /**
     * @param string|string[] $roleCategories
     */
    public function isReadonly(string $fieldName, $roleCategories = ['Other']): bool
    {
        return $this->settingFor($fieldName, $roleCategories) === 'readonly';
    }

    /**
     * True if any role category has this field Hidden (import/export).
     */
    public function isHiddenFromExport(string $fieldName): bool
    {
        $fieldName = $this->normalizeFieldName($fieldName);
        if ($fieldName === '' || in_array($fieldName, self::ALWAYS_REQUIRED, true)) {
            return false;
        }

        if ($this->hiddenFromExport === null) {
            $this->hiddenFromExport = [];
            $raw = $this->loadRawSettings();
            if (is_array($raw)) {
                foreach (self::ROLE_CATEGORIES as $roleCategory) {
                    foreach (($raw[$roleCategory] ?? []) as $name => $value) {
                        if ($value === 'hidden') {
                            $this->hiddenFromExport[$name] = true;
                        }
                    }
                }
            }
        }

        return isset($this->hiddenFromExport[$fieldName]);
    }

    /**
     * Hidden field names for these roles, including phone Type/CountryCode companions.
     *
     * @param string|string[] $roleCategories
     * @return string[]
     */
    public function getHiddenFields($roleCategories = ['Other']): array
    {
        $hidden = [];

        foreach ($this->getSettings($roleCategories) as $field => $setting) {
            if ($setting !== 'hidden') {
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
     * @param string[]        $fieldNames
     * @param string|string[] $roleCategories
     */
    public function anyVisible(array $fieldNames, $roleCategories = ['Other']): bool
    {
        foreach ($fieldNames as $fieldName) {
            if ($this->isVisible($fieldName, $roleCategories)) {
                return true;
            }
        }

        return false;
    }

    public function isAlwaysRequired(string $fieldName): bool
    {
        return in_array($this->normalizeFieldName($fieldName), self::ALWAYS_REQUIRED, true);
    }

    /**
     * Map related POST/column names onto the settings key (e.g. phone1Type -> phone1).
     */
    public function normalizeFieldName(string $fieldName): string
    {
        if (preg_match('/^(phone[1-4])(Type|CountryCode)?$/', $fieldName, $matches)) {
            return $matches[1];
        }

        return $fieldName;
    }

    /**
     * @param string|string[] $roleCategories
     */
    protected function settingFor(string $fieldName, $roleCategories): string
    {
        $fieldName = $this->normalizeFieldName($fieldName);

        if (in_array($fieldName, self::ALWAYS_REQUIRED, true)) {
            return 'required';
        }

        // No modern settings: keep historical required name fields
        if ($this->loadRawSettings() === null) {
            return in_array($fieldName, self::LEGACY_REQUIRED, true) ? 'required' : '';
        }

        return $this->getSettings($roleCategories)[$fieldName] ?? '';
    }

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
     * @return array|null
     */
    protected function loadRawSettings()
    {
        if ($this->rawSettings !== false) {
            return $this->rawSettings;
        }

        $this->rawSettings = null;

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
                // Legacy flat Y/N settings → role-keyed required/blank
                $flat = [];
                foreach ($decoded as $name => $value) {
                    if ($name === 'flag' || is_array($value)) {
                        continue;
                    }
                    $flat[$name] = ($value === 'Y') ? 'required' : '';
                }
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
