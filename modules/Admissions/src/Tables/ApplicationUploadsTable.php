<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

    public function createTable($gibbonAdmissionsApplicationID)
    {
        // Load related documents
        $criteria = $this->formUploadGateway->newQueryCriteria()->fromPOST();
        $uploads = $this->formUploadGateway->queryAllDocumentsByContext($criteria, 'gibbonAdmissionsApplication', $gibbonAdmissionsApplicationID);

        // Create the table
        $table = DataTable::createPaginated('applicationDocuments', $criteria)->withData($uploads);
        $table->addColumn('status', __('Status'))->width('6%')->format(function($values)  {
            $fileExists = file_exists($this->session->get('absolutePath').'/'.$values['path']);
            return $this->view->fetchFromTemplate('ui/icons.twig.html', [
                'icon' => $fileExists ? 'check' : 'cross',
                'iconClass' => 'w-6 h-6 fill-current mr-3 -my-2',
            ]);
            return Format::link($this->session->get('absoluteURL').'/'.$values['path'], $values['name'], ['target' => '_blank']);
        });
        $table->addColumn('name', __('Document'))->format(function($values)  {
            return Format::link($this->session->get('absoluteURL').'/'.$values['path'], $values['name'], ['target' => '_blank']);
        });
        $table->addColumn('type', __('Type'));
        $table->addColumn('timestamp', __('When'))->format(Format::using('relativeTime', 'timestamp'));

        $table->addActionColumn()
            ->format(function ($values, $actions) {
                if (!empty($values['path'])) {
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
