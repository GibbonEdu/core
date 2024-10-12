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

        if (stripos($action, 'addProcess') !== false) {
            $icon = '<svg class="'.$iconClass.'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v2.5h-2.5a.75.75 0 0 0 0 1.5h2.5v2.5a.75.75 0 0 0 1.5 0v-2.5h2.5a.75.75 0 0 0 0-1.5h-2.5v-2.5Z" clip-rule="evenodd" />
            </svg>';
            $row = $this->addRow()->addClass('text-sm');
            $row->addContent($icon . __('Adding'))->wrap('<h3 class="text-base font-semibold text-gray-800 mt-0 ">', '</h3>');
            $row->addContent(__('You are creating a new record and it has not been saved yet.').'<br/><br/>'.__('Press Submit to save your data.'))->wrap('<p class="mt-2 mb-0">', '</p>');
        } elseif (stripos($action, 'editProcess') !== false) {
            $icon = '<svg class="'.$iconClass.'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
            <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
            </svg>';
            $row = $this->addRow()->addClass('text-sm');
            $row->addContent($icon . __('Editing'))->wrap('<h3 class="text-base font-semibold text-gray-800 mt-0 ">', '</h3>');
            $row->addContent(__('Press Submit to save your data.'))->wrap('<p class="mt-2 mb-0">', '</p>');
        } elseif (stripos($action, 'duplicate') !== false) {
            $icon = '<svg class="'.$iconClass.'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M7 3.5A1.5 1.5 0 0 1 8.5 2h3.879a1.5 1.5 0 0 1 1.06.44l3.122 3.12A1.5 1.5 0 0 1 17 6.622V12.5a1.5 1.5 0 0 1-1.5 1.5h-1v-3.379a3 3 0 0 0-.879-2.121L10.5 5.379A3 3 0 0 0 8.379 4.5H7v-1Z" />
            <path d="M4.5 6A1.5 1.5 0 0 0 3 7.5v9A1.5 1.5 0 0 0 4.5 18h7a1.5 1.5 0 0 0 1.5-1.5v-5.879a1.5 1.5 0 0 0-.44-1.06L9.44 6.439A1.5 1.5 0 0 0 8.378 6H4.5Z" />
            </svg>';
            $row = $this->addRow()->addClass('text-sm');
            $row->addContent($icon . __('Duplicating'))->wrap('<h3 class="text-base font-semibold text-gray-800 mt-0 ">', '</h3>');
            $row->addContent(__('You are creating a copy of the selected record. The original record will not be changed.'))->wrap('<p class="mt-2 mb-0">', '</p>');
        }

        return $this;
    }

    /**
     * Load bulk values into the meta object.
     * @param   array  &$data
     * @return  self
     */
    public function loadFrom(&$data)
    {
        if (!empty($data['active'])) {
            $row = $this->addRow()->setClass('flex flex-row items-center justify-between');
                $row->addLabel('active', __('Status'));
                $row->addToggle('active')->setActiveInactive()->setClass('justify-start')->setValue($data['active']);
        }

        // $row = $this->addRow()->addContent('This is a layout test');

        if (!empty($data['timestamp']) || !empty($data['timestampModified'])) {
            $timestamp = $data['timestamp'] ?? $data['timestampModified'];
            $row = $this->addRow();
                $row->addLabel('', __('Last Modified'));
                $row->addContent(Format::dateTimeReadable($timestamp));
        }

        return $this;
    }
}
