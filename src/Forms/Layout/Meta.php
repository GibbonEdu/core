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

namespace Gibbon\Forms\Layout;


use Gibbon\Forms\FormFactoryInterface;
use Gibbon\Services\Format;

/**
 * Displays a sidebar of meta data for a particular form.
 *
 * @version v28
 * @since   v28
 */
class Meta extends Row
{
    /**
     * Construct a meta column with access to a specific factory.
     * @param  FormFactoryInterface  $factory
     * @param  string                $id
     */
    public function __construct(FormFactoryInterface $factory)
    {
        parent::__construct($factory, 'meta');
    }

    public function addRow($id = '')
    {
        return $this->addColumn($id)->setClass('');
    }

    public function addDefaultContent($action = '')
    {
        $iconClass = 'inline-block align-text-bottom text-gray-500 w-6 h-6 sm:h-5 sm:w-5 mr-2';

        if ($this->checkActionList($action, ['addProcess'])) {
            $row = $this->addRow()->addClass('text-sm');
            $row->addContent(icon('solid', 'add', $iconClass) . __('Adding'))->wrap('<h3 class="text-base font-semibold text-gray-800 mt-0 ">', '</h3>');
            $row->addContent(__('You are creating a new record and it has not been saved yet.').'<br/><br/>'.__('Press Submit to save your data.'))->wrap('<p class="mt-2 mb-0">', '</p>');
        }if ($this->checkActionList($action, ['addMultiProcess', 'addMultipleProcess'])) {
            $row = $this->addRow()->addClass('text-sm');
            $row->addContent(icon('solid', 'add-multi', $iconClass) . __('Adding Multiple'))->wrap('<h3 class="text-base font-semibold text-gray-800 mt-0 ">', '</h3>');
            $row->addContent(__('You are creating multiple new records that share similar data.').'<br/><br/>'.__('Press Submit to save your data.'))->wrap('<p class="mt-2 mb-0">', '</p>');
        } elseif ($this->checkActionList($action, ['editProcess'])) {
            $row = $this->addRow()->addClass('text-sm');
            $row->addContent(icon('solid', 'edit', $iconClass) . __('Editing'))->wrap('<h3 class="text-base font-semibold text-gray-800 mt-0 ">', '</h3>');
            $row->addContent(__('Press Submit to save your data.'))->wrap('<p class="mt-2 mb-0">', '</p>');
        } elseif ($this->checkActionList($action, ['duplicate'])) {
            $row = $this->addRow()->addClass('text-sm');
            $row->addContent(icon('solid', 'copy', $iconClass) . __('Duplicating'))->wrap('<h3 class="text-base font-semibold text-gray-800 mt-0 ">', '</h3>');
            $row->addContent(__('You are creating a copy of the selected record. The original record will not be changed.'))->wrap('<p class="mt-2 mb-0">', '</p>');
        }

        return $this;
    }

    protected function checkActionList($actionString, $validActions)
    {
        foreach ($validActions as $action) {
            if (stripos($actionString, $action) !== false) return true;
        }
        return false;
    }

    /**
     * Load bulk values into the meta object.
     * @param   array  &$data
     * @return  self
     */
    public function loadFrom(&$data)
    {
        // if (!empty($data['active'])) {
        //     $row = $this->addRow()->setClass('flex flex-row items-center justify-between');
        //         $row->addLabel('active', __('Status'));
        //         $row->addToggle('active')->setActiveInactive()->setClass('justify-start')->setValue($data['active']);
        // }

        // $row = $this->addRow()->addContent('This is a layout test');

        if (!empty($data['timestampCreated'])) {
            $row = $this->addRow();
                $row->addLabel('', __('Created'));
                $row->addContent(Format::dateTimeReadable($data['timestampCreated']));
        }

        if (!empty($data['timestamp']) || !empty($data['timestampModified'])) {
            $row = $this->addRow();
                $row->addLabel('', __('Last Modified'));
                $row->addContent(Format::dateTimeReadable($data['timestamp'] ?? $data['timestampModified']));
        }

        return $this;
    }
}
