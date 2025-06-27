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
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;
use Gibbon\Http\Url;

/**
 * ApplicationFamilyTable
 *
 * @version v24
 * @since   v24
 */
class ApplicationFamilyTable extends DataTable
{
    protected $view;
    protected $session;
    
    protected $formUploadGateway;
    protected $applicationGateway;

    public function __construct(Session $session, View $view, AdmissionsApplicationGateway $applicationGateway)
    {
        $this->view = $view;
        $this->session = $session;
        $this->applicationGateway = $applicationGateway;
    }

    public function createTable($gibbonAdmissionsApplicationID, $gibbonFamilyID)
    {
        // Load related documents
        $criteria = $this->applicationGateway->newQueryCriteria()->fromPOST();
        $family = $this->applicationGateway->queryFamilyByApplication($criteria, $this->session->get('gibbonSchoolYearID'), $gibbonAdmissionsApplicationID);

        // Create the table
        $table = DataTable::create('applicationFamily')->withData($family);
        
        $table->modifyRows(function ($values, $row) {
            if ($values['status'] == 'Full' || $values['status'] == 'Expected') $row->addClass('success');
            if ($values['status'] == 'Accepted') $row->addClass('success');
            if ($values['status'] == 'Incomplete') $row->addClass('warning');
            if ($values['status'] == 'Left') $row->addClass('error');
            if ($values['status'] == 'Rejected') $row->addClass('error');
            if ($values['status'] == 'Withdrawn') $row->addClass('error');
            return $row;
        });

        if (!empty($gibbonFamilyID)) {
            $table->addHeaderAction('edit', __('Edit Family'))
                ->setURL('/modules/User Admin/family_manage_edit.php')
                ->addParam('gibbonFamilyID', $gibbonFamilyID)
                ->displayLabel();
        }

        $table->addColumn('image_240', __('Photo'))
            ->context('primary')
            ->width('10%')
            ->notSortable()
            ->format(Format::using('userPhoto', ['image_240', 'sm']));

        $table->addColumn('person', __('Person'))
            ->description(__('Status'))
            ->format(function ($values) {
                return !empty($values['gibbonPersonID'])
                    ? Format::nameLinked($values['gibbonPersonID'], '', $values['preferredName'], $values['surname'], $values['roleCategory'], true)
                    : Format::name('', $values['preferredName'], $values['surname'], 'Student', true);

            })
            ->formatDetails(function ($values) {
                return Format::small($values['roleCategory'] == 'Student' ? Format::userStatusInfo($values) : $values['status']);
            });

        $table->addColumn('roleCategory', __('Role'))
            ->description(__('Relationship'))
            ->formatDetails(function ($values) {
                return Format::small($values['relationship']);
            });

        $table->addColumn('email', __('Email'));

        $table->addColumn('details', __('Details'))
            ->format(function ($values) use ($gibbonAdmissionsApplicationID) {
                if ($values['status'] == 'Full' || $values['status'] == 'Expected') {
                    return __('Existing {role}', ['role' => $values['roleCategory']]);
                } elseif ($values['roleCategory'] == 'Parent' && $values['status'] == 'Pending') {
                    return __('New Parent');
                } elseif ($values['roleCategory'] == 'Student' && !empty($values['applicationID'])) {
                    $name = $gibbonAdmissionsApplicationID == $values['applicationID'] 
                        ? Format::tag(__('Current Application'), 'dull') 
                        : __('Application').' #'.$values['applicationID'];
                    $url = Url::fromModuleRoute('Admissions', 'applications_manage_edit')->withQueryParams(['gibbonAdmissionsApplicationID' => $values['applicationID']]);
                    return Format::link($url, $name);
                }
                return '';
            })
            ->formatDetails(function ($values) {
                return Format::small($values['yearGroup']);
            });


        return $table;
    }
}
