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

namespace Gibbon\Module\Admissions\Tables;

use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Forms\FormUploadGateway;

/**
 * ApplicationUploadsTable
 *
 * @version v24
 * @since   v24
 */
class ApplicationUploadsTable extends DataTable
{
    protected $view;
    protected $session;
    protected $formUploadGateway;

    public function __construct(Session $session, View $view, FormUploadGateway $formUploadGateway)
    {
        $this->view = $view;
        $this->session = $session;
        $this->formUploadGateway = $formUploadGateway;

    }

    public function createTable($gibbonFormID, $gibbonAdmissionsApplicationID)
    {
        // Load related documents
        $criteria = $this->formUploadGateway->newQueryCriteria()->fromPOST();
        $uploads = $this->formUploadGateway->queryAllDocumentsByContext($criteria, $gibbonFormID, 'gibbonAdmissionsApplication', $gibbonAdmissionsApplicationID);

        // Create the table
        $table = DataTable::createPaginated('applicationDocuments', $criteria)->withData($uploads);
        $table->addColumn('status', __('Status'))
            ->width('5%')
            ->addClass('h-12')
            ->format(function($values)  {
                $filePath = $this->session->get('absolutePath').'/'.$values['path'];
                $icon = !empty($values['path']) && (!is_file($filePath) || filesize($filePath) == 0) ? 'cross' : 'check';
                $iconRequired = $values['required'] == 'Y' ? 'cross' : 'question';
                return $this->view->fetchFromTemplate('ui/icons.twig.html', [
                    'icon' => empty($values['path']) ? $iconRequired : $icon,
                    'iconClass' => 'w-6 h-6 text-gray-500 fill-current ml-2 -my-2'
                ]);
            });
        $table->addColumn('name', __('Document'))
            ->format(function($values)  {
                $output = !empty($values['path'])
                    ? Format::link($this->session->get('absoluteURL').'/'.$values['path'], __($values['name']), ['target' => '_blank'])
                    : $values['name'];

                $filePath = $this->session->get('absolutePath').'/'.$values['path'];
                if (!empty($values['path']) && (!is_file($filePath) || filesize($filePath) == 0)) {
                    $output .= Format::tag(__('Error'), 'error ml-2', __('This file is missing or empty. It may have failed to upload or is no longer on the server.'));
                }
                return $output;
            });
        $table->addColumn('target', __('Person'))->translatable();
        $table->addColumn('type', __('Type'))
            ->translatable()
            ->format(function ($values) {
                return empty($values['type']) || $values['type'] == 'Unknown'
                    ? Format::tag($values['type'], 'warning')
                    : $values['type'];
            });
        $table->addColumn('timestamp', __('When'))
            ->format(function ($values) {
                return !empty($values['path'])
                    ? Format::relativeTime($values['timestamp'])
                    : Format::small(__('N/A'));
            });

        $table->addActionColumn()
            ->format(function ($values, $actions) {
                if (!empty($values['path'])) {
                    // if ($values['confirmable'] == 'Y' && $values['confirmed'] != 'Y') {
                        // $actions->addAction('confirm', __('Confirm'))
                        //     ->setURL('/modules/Admissions/applications_manage_editConfirmProcess.php')
                        //     ->setIcon('iconTick');
                    // }

                    $actions->addAction('view', __('View'))
                        ->setExternalURL($this->session->get('absoluteURL').'/'.$values['path'])
                        ->directLink();

                    $actions->addAction('export', __('Download'))
                        ->setExternalURL($this->session->get('absoluteURL').'/'.$values['path'], null, true)
                        ->directLink();
                }
            });

        return $table;
    }
}
