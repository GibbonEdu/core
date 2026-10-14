<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
    10|the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
    20|*/

namespace Gibbon\Data;

use Gibbon\Domain\Planner\LessonPlanAddFormBuilder;
use Gibbon\Forms\Form;
use Gibbon\Forms\Input\Checkbox;
use Gibbon\Forms\Input\CustomField;
use Gibbon\Forms\Input\Date;
use Gibbon\Forms\Input\Editor;
use Gibbon\Forms\Input\FileUpload;
use Gibbon\Forms\Input\Input;
use Gibbon\Forms\Input\Number;
use Gibbon\Forms\Input\Time;
use Gibbon\Forms\Input\Toggle;
use Gibbon\Forms\Layout\Column;
use Gibbon\Forms\Layout\Label;
use Gibbon\Forms\Layout\Row;
use League\Container\Container;

/**
 * Walks the live Add Lesson Plan form and turns visible inputs into import fields.
 */
class LessonPlanFormFieldParser
{
    /**
     * Form input names that are UI-only and should not become import columns.
     *
     * @var string[]
     */
    protected static $skipNames = [
        'address',
        'q',
        'csrftoken',
        'nonce',
        'advanced',
        'notify',
        'markbook',
        'guests',
        'role',
        'outcome',
        'courseClassName',
        'gibbonTTDayRowClassID',
        'homeworkDueDateTime',
        'homeworkCrowdAssessControl',
        'homeworkCrowdAssessClassTeacher',
        'homeworkCrowdAssessClassSubmitter',
    ];

    /**
     * Map add-form input names onto gibbonPlannerEntry / import field names.
     *
     * @var string[]
     */
    protected static $aliases = [
        'homeworkDueDate' => 'homeworkDueDateTime',
    ];

    /**
     * Merge fields discovered on the live add-lesson form into a YAML import definition.
     *
     * @param array $fileData
     * @param Container $container
     * @return array
     */
    public static function mergeIntoImportData(array $fileData, Container $container): array
    {
        $form = LessonPlanAddFormBuilder::createForImport($container);
        $liveFields = self::parseForm($form);

        $existing = $fileData['table'] ?? $fileData['fields'] ?? [];
        $merged = [];
        $used = [];

        foreach ($existing as $fieldName => $definition) {
            $hidden = !empty($definition['args']['hidden']);
            $readonly = !empty($definition['args']['readonly']);
            $inLive = isset($liveFields[$fieldName]);
            foreach (self::$aliases as $formName => $importName) {
                if ($importName === $fieldName && isset($liveFields[$formName])) {
                    $inLive = true;
                }
            }

            if (!$inLive && $readonly && empty($definition['args']['linked'] ?? '')) {
                $merged[$fieldName] = $definition;
                $used[$fieldName] = true;
            }
        }

        foreach ($liveFields as $formName => $liveDefinition) {
            $fieldName = self::$aliases[$formName] ?? $formName;

            if (isset($existing[$fieldName])) {
                $merged[$fieldName] = self::overlayDefinition($existing[$fieldName], $liveDefinition);
            } else {
                $merged[$fieldName] = $liveDefinition;
            }
            $used[$fieldName] = true;
        }

        foreach ($existing as $fieldName => $definition) {
            if (empty($used[$fieldName])) {
                $merged[$fieldName] = $definition;
            }
        }

        if (isset($fileData['table'])) {
            $fileData['table'] = $merged;
        } else {
            $fileData['fields'] = $merged;
            if (!empty($fileData['tables']['gibbonPlannerEntry']['fields'])) {
                $fileData['tables']['gibbonPlannerEntry']['fields'] = array_keys($merged);
            }
        }

        return $fileData;
    }

    /**
     * @param Form $form
     * @return array
     */
    public static function parseForm(Form $form): array
    {
        $fields = [];
        foreach ($form->getRows() as $row) {
            self::collectFromRow($row, $fields, [], false);
        }

        return $fields;
    }

    /**
     * @param Row $row
     * @param array $fields
     */
    protected static function collectFromRow(Row $row, array &$fields, array $inheritedLabels = [], bool $conditional = false)
    {
        $labelByFor = $inheritedLabels;
        $conditional = $conditional || self::isConditionalRow($row);

        foreach ($row->getElements() as $element) {
            if ($element instanceof Label) {
                $for = $element->getAttribute('for');
                if (!empty($for)) {
                    $labelByFor[$for] = $element;
                }
            }
        }

        foreach ($row->getElements() as $element) {
            if ($element instanceof Column) {
                self::collectFromRow($element, $fields, $labelByFor, $conditional);
                continue;
            }

            if ($element instanceof Label) {
                continue;
            }

            if (!$element instanceof Input) {
                continue;
            }

            $name = self::resolveName($element, $labelByFor);
            $name = str_replace(['[]', 'CustomEditor'], '', $name);

            if (empty($name) || isset($fields[$name]) || in_array($name, self::$skipNames, true)) {
                continue;
            }

            if (method_exists($element, 'getDisabled') && $element->getDisabled()) {
                continue;
            }

            $label = $labelByFor[$name] ?? $labelByFor[$name.'CustomEditor'] ?? null;
            $fields[$name] = self::definitionFromInput($element, $label, $conditional, $labelByFor);
        }
    }

    /**
     * @param object $element
     * @return string
     */
    protected static function resolveName($element, array $labelByFor = []): string
    {
        if (method_exists($element, 'getName')) {
            $name = (string) $element->getName();
            if (!empty($name) && $name !== 'label') {
                return $name;
            }
        }

        if ($element instanceof CustomField && !empty($labelByFor)) {
            return (string) array_key_first($labelByFor);
        }

        return (string) $element->getID();
    }

    /**
     * @param Row $row
     * @return bool
     */
    protected static function isConditionalRow(Row $row): bool
    {
        $class = $row->getClass() ?? '';
        return (bool) preg_match('/\b(homework|homeworkSubmission|homeworkCrowdAssess|advanced)\b/', $class);
    }

    /**
     * @param Input $element
     * @param Label|null $label
     * @param bool $conditional
     * @param array $labelByFor
     * @return array
     */
    protected static function definitionFromInput($element, $label, bool $conditional, array $labelByFor = []): array
    {
        $fieldName = self::resolveName($element, $labelByFor);
        $fieldName = str_replace(['[]', 'CustomEditor'], '', $fieldName);

        $name = '';
        $desc = '';
        if ($label instanceof Label) {
            $name = trim(strip_tags((string) $label->getLabelText()));
            $desc = trim(strip_tags((string) $label->getDescription()));
        }

        if ($name === '' && $element instanceof Checkbox) {
            $name = self::checkboxLabel($element);
        }

        if ($name === '' || strcasecmp($name, __('Field')) === 0) {
            $name = self::humanizeFieldName($fieldName);
        }

        $required = false;
        if (!$conditional && method_exists($element, 'getRequired')) {
            $required = (bool) $element->getRequired();
        }

        $filter = self::filterFromElement($element);
        $args = [
            'filter' => $filter,
            'custom' => true,
        ];

        if ($required) {
            $args['required'] = true;
        }

        if ($filter === 'yesno' && $element instanceof CustomField) {
            $options = self::customFieldOptions($element);
            if (count($options) === 1) {
                $args['checkboxOnValue'] = $options[0];
            }
        }

        if (preg_match('/^custom(\d+)$/', $fieldName, $match)) {
            $args['readonly'] = true;
            $args['serialize'] = 'fields';
            $args['customField'] = $match[1];
        } elseif ($fieldName === 'videoLink') {
            $args['readonly'] = true;
            $args['serialize'] = 'fields';
            $args['customField'] = 'videoLink';
        }

        return [
            'name' => $name,
            'desc' => $desc,
            'args' => $args,
        ];
    }

    /**
     * @param Checkbox $element
     * @return string
     */
    protected static function checkboxLabel(Checkbox $element): string
    {
        try {
            $reflection = new \ReflectionProperty($element, 'description');
            $reflection->setAccessible(true);
            $description = $reflection->getValue($element);
            if (is_string($description) && $description !== '') {
                return trim(strip_tags($description));
            }
        } catch (\ReflectionException $e) {
        }

        return '';
    }

    /**
     * @param string $fieldName
     * @return string
     */
    protected static function humanizeFieldName(string $fieldName): string
    {
        $fieldName = preg_replace('/^custom\d+$/', '', $fieldName);
        $spaced = preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $fieldName);
        $spaced = str_replace('_', ' ', (string) $spaced);

        return trim(ucwords($spaced));
    }

    /**
     * @param object $element
     * @return string
     */
    protected static function filterFromElement($element): string
    {
        $customType = '';
        $customField = $element instanceof CustomField ? $element : null;
        if ($element instanceof CustomField) {
            $customType = strtolower(self::customFieldType($element));
            $inner = self::unwrapCustomField($element);
            if ($inner instanceof Input) {
                $element = $inner;
            }
        }

        switch ($customType) {
            case 'date':
                return 'date';
            case 'time':
                return 'time';
            case 'number':
                return 'numeric';
            case 'editor':
            case 'code':
            case 'text':
                return 'html';
            case 'yesno':
            case 'checkbox':
                return 'yesno';
            case 'checkboxes':
                if ($customField instanceof CustomField && count(self::customFieldOptions($customField)) <= 1) {
                    return 'yesno';
                }
                return 'csv';
            case 'radio':
            case 'select':
                return 'csv';
            case 'url':
                return 'url';
            case 'file':
            case 'image':
                return 'string';
        }

        if ($element instanceof Date) {
            return 'date';
        }
        if ($element instanceof Time) {
            return 'time';
        }
        if ($element instanceof Number) {
            return 'numeric';
        }
        if ($element instanceof Editor) {
            return 'html';
        }
        if ($element instanceof Toggle) {
            return 'yesno';
        }
        if ($element instanceof Checkbox) {
            return 'yesno';
        }
        if ($element instanceof FileUpload) {
            return 'string';
        }

        $htmlType = method_exists($element, 'getAttribute') ? strtolower((string) $element->getAttribute('type')) : '';
        if ($htmlType === 'checkbox') {
            return 'yesno';
        }
        if ($htmlType === 'url') {
            return 'url';
        }

        return 'string';
    }

    /**
     * @param CustomField $element
     * @return string[]
     */
    protected static function customFieldOptions(CustomField $element): array
    {
        try {
            $reflection = new \ReflectionProperty($element, 'fields');
            $reflection->setAccessible(true);
            $fields = $reflection->getValue($element);
            $options = $fields['options'] ?? '';
        } catch (\ReflectionException $e) {
            return [];
        }

        if (is_array($options)) {
            return array_values(array_filter(array_map('trim', $options), 'strlen'));
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $options)), 'strlen'));
    }

    /**
     * @param CustomField $element
     * @return string
     */
    protected static function customFieldType(CustomField $element): string
    {
        try {
            $reflection = new \ReflectionProperty($element, 'type');
            $reflection->setAccessible(true);
            return (string) $reflection->getValue($element);
        } catch (\ReflectionException $e) {
            return '';
        }
    }

    /**
     * @param CustomField $element
     * @return Input|null
     */
    protected static function unwrapCustomField(CustomField $element)
    {
        try {
            $reflection = new \ReflectionProperty($element, 'customField');
            $reflection->setAccessible(true);
            $inner = $reflection->getValue($element);
            return $inner instanceof Input ? $inner : null;
        } catch (\ReflectionException $e) {
            return null;
        }
    }

    /**
     * @param array $base
     * @param array $live
     * @return array
     */
    protected static function overlayDefinition(array $base, array $live): array
    {
        $liveName = trim((string) ($live['name'] ?? ''));
        if ($liveName !== '' && strcasecmp($liveName, __('Field')) !== 0) {
            $base['name'] = $liveName;
        }
        if (!empty($live['desc'])) {
            $base['desc'] = $live['desc'];
        }

        $liveArgs = $live['args'] ?? [];
        $baseArgs = $base['args'] ?? [];

        if (isset($liveArgs['required']) && empty($baseArgs['required'])) {
            $baseArgs['required'] = $liveArgs['required'];
        }

        $base['args'] = $baseArgs;

        return $base;
    }
}
