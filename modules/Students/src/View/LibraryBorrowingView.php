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

namespace Gibbon\Module\Students\View;

use Gibbon\View\Page;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Library\LibraryReportGateway;
use Gibbon\Forms\Form;

/**
 *
 * @version v29
 * @since   v29
 */
class LibraryBorrowingView
{
    protected $session;
    protected $db;
    protected $settingGateway;
    protected $libraryReportGateway;

    protected $gibbonSchoolYearID;
    protected $gibbonPersonID;
    protected $lendingAction;
    protected $canLend;
    protected $canEdit;

    public function __construct(Session $session, Connection $db, SettingGateway $settingGateway, LibraryReportGateway $libraryReportGateway)
    {
        $this->session = $session;
        $this->db = $db;
        $this->settingGateway = $settingGateway;
        $this->libraryReportGateway = $libraryReportGateway;
    }

    public function setStudent($gibbonPersonID)
    {
        $this->gibbonSchoolYearID = $this->session->get('gibbonSchoolYearID');
        $this->gibbonPersonID = $gibbonPersonID;

        return $this; 
    }

    public function compose(Page $page)
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();

        $this->canLend = isActionAccessible($guid, $connection2, '/modules/Library/library_lending.php');
        $this->canEdit = isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog.php');
        $this->lendingAction = $_REQUEST['lendingAction'] ?? '';
        
        // Forms
        $signOutForm = $this->createLendingForm('SignOut');
        $returnForm = $this->createLendingForm('Return');
        $reserveForm = $this->createLendingForm('Reserve');
        $signOutOtherForm = $this->createLendingForm('SignOutOther');

        // Table: On Loan
        $criteria = $this->libraryReportGateway->newQueryCriteria()
            ->sortBy('timestampOut', 'DESC')
            ->filterBy('gibbonPersonID', $this->gibbonPersonID)
            ->filterBy('status', 'On Loan')
            ->filterBy('type', 'Print Publication')
            ->fromPOST('SignOut');
        $items = $this->libraryReportGateway->queryStudentReportData($criteria);
        $onLoanTable = $this->createLendingTable('SignOut', $criteria, $items);

        // Table: Returned
        $criteria = $this->libraryReportGateway->newQueryCriteria(true)
            ->sortBy('timestampOut', 'DESC')
            ->filterBy('gibbonPersonID', $this->gibbonPersonID)
            ->filterBy('status', 'Returned')
            ->filterBy('type', 'Print Publication')
            ->fromPOST('Return');
        $items = $this->libraryReportGateway->queryStudentReportData($criteria);
        $returnedTable = $this->createLendingTable('Return', $criteria, $items);

        // Table: Reserved
        $criteria = $this->libraryReportGateway->newQueryCriteria(true)
            ->sortBy('timestampOut', 'DESC')
            ->filterBy('gibbonPersonID', $this->gibbonPersonID)
            ->filterBy('status', 'Reserved')
            ->filterBy('type', 'Print Publication')
            ->fromPOST('Reserve');
        $items = $this->libraryReportGateway->queryStudentReportData($criteria);
        $reservedTable = $this->createLendingTable('Reserve', $criteria, $items);

        // Table: Other Items
        $criteria = $this->libraryReportGateway->newQueryCriteria(true)
            ->sortBy('timestampOut', 'DESC')
            ->filterBy('gibbonPersonID', $this->gibbonPersonID)
            ->filterBy('notType', 'Print Publication')
            ->fromPOST('SignOutOther');
        $items = $this->libraryReportGateway->queryStudentReportData($criteria);
        $otherTable = $this->createLendingTable('SignOutOther', $criteria, $items);

        // Table: Owned
        $criteria = $this->libraryReportGateway->newQueryCriteria(true)
            ->sortBy('timestampOut', 'DESC')
            ->filterBy('ownershipType', 'Individual')
            ->filterBy('gibbonPersonIDOwnership', $this->gibbonPersonID)
            ->fromPOST('Owned');
        $items = $this->libraryReportGateway->queryStudentReportData($criteria);
        $ownedTable = $this->createLendingTable('Owned', $criteria, $items);

        $tabs = [];

        $tabs['On Loan'] = [
            'label'   => __('On Loan'),
            'content' => $this->canLend ? $signOutForm->getOutput().$onLoanTable->getOutput() : $onLoanTable->getOutput(),
            'icon'    => 'book-open',
            'action'  => 'SignOut',
        ];

        $tabs['Returned'] = [
            'label'   => __('Returned'),
            'content' => $this->canLend ? $returnForm->getOutput().$returnedTable->getOutput() : $returnedTable->getOutput(),
            'icon'    => 'book-open',
            'action'  => 'Return',
        ];
        
        $tabs['Reserved'] = [
            'label'   => __('Reserved'),
            'content' => $this->canLend ? $reserveForm->getOutput().$reservedTable->getOutput() : $reservedTable->getOutput(),
            'icon'    => 'bookmark',
            'action'  => 'Reserve',
        ];

        $tabs['Other Items'] = [
            'label'   => __('Other Items'),
            'content' => $this->canLend ? $signOutOtherForm->getOutput().$otherTable->getOutput() : $otherTable->getOutput(),
            'icon'    => 'squares',
            'action'  => 'SignOutOther',
        ];

        $tabs['Owned'] = [
            'label'   => __('Owned'),
            'content' => $ownedTable->getOutput(),
            'icon'    => 'user',
            'action'  => '',
        ];

        $selectedTab = !empty($this->lendingAction)? array_search($this->lendingAction, array_column($tabs, 'action')) + 1 : 1;

        echo $page->fetchFromTemplate('ui/tabs.twig.html', [
            'selected' => $selectedTab,
            'tabs'     => $tabs,
            'outset'   => false,
            'icons'    => true,
        ]);
    }

    protected function createLendingTable($action, $criteria, $items) : DataTable
    {
        $table = DataTable::createPaginated($action ?? 'Lending', $criteria)->withData($items);

        $table->modifyRows(function ($item, $row) {
            if ($item['status'] == 'On Loan') {
                return $item['pastDue'] == 'Y' ? $row->addClass('error') : $row->addClass('success');
            }
            if ($item['status'] == 'Reserved') $row->addClass('message');
            if ($item['status'] == 'Decommissioned' || $item['status'] == 'Lost') $row->addClass('error');

            return $row;
        });

        if ($this->canLend) {
            $table->addExpandableColumn('details')
                ->format(function ($item) {
                $detailTable = "<table class='w-full'>";
                $fields = json_decode($item['fields'], true) ?? [];
                $typeFields = json_decode($item['typeFields'], true) ?? [];
                foreach ($typeFields as $typeField) {
                    $detailTable .= sprintf('<tr><td><b>%1$s</b></td><td>%2$s</td></tr>', $typeField['name'], $fields[$typeField['name']] ?? '');
                }
                $detailTable .= '</table>';
                return $detailTable;
                });
        }

        $table->addColumn('imageLocation')
            ->width('120px')
            ->format(function ($item) {
            return Format::photo($item['imageLocation'], 75);
            });

        $table->addColumn('name', __('Name'))
            ->description(__('Author/Producer'))
            ->format(function ($item) {
            return Format::bold($item['name']);
            })
            ->formatDetails(function($item) {
                return Format::small($item['producer']);
            });

        $table->addColumn('id', __('ID'))
            ->format(function ($item) {
            return Format::bold($item['id']);
            });

        $table->addColumn('spaceName', __('Location'))
            ->format(function ($item) {
            return Format::bold($item['spaceName']);
            })
            ->formatDetails(function($item) {
                return Format::small($item['locationDetail']);
            });

        $table->addColumn('timestampOut', __('Return Date'))
            ->description(__('Borrow Date'))
            ->format(function ($item) {
                return $item['status'] == 'On Loan' ? Format::date($item['returnExpected']) : Format::date($item['timestampReturn']);
            })
            ->formatDetails(function($item) {
                return Format::small(Format::date($item['timestampOut']));
            });

        $table->addColumn('status', __('Status'))
            ->format(function ($item) {
                return $item['status'];
            })
            ->formatDetails(function($item) {
                return $item['status'] == 'On Loan' && $item['pastDue'] == 'Y' ? Format::tag(__('Overdue'), 'error mt-2') : '';
            });

        if ($this->canLend) {
            $table->addActionColumn()
                ->addParam('gibbonLibraryItemID')
                ->addParam('gibbonLibraryItemEventID')
                ->addParam('gibbonPersonIDStudent', $this->gibbonPersonID)
                ->addParam('lendingAction', $action)
                ->format(function ($item, $actions) {

                    if ($item['status'] == 'On Loan' && !empty($item['gibbonPersonIDStatusResponsible'])) {
                        if (!empty($item['gibbonPersonIDStatusResponsible'])) {
                            $actions->addAction('edit', __('Edit'))
                                ->setURL('/modules/Library/library_lending_item_edit.php');
                        }

                        $actions->addAction('return', __('Return'))
                            ->setIcon('page_left')
                            ->setURL('/modules/Library/library_lending_item_return.php');

                        if (!empty($item['gibbonPersonIDStatusResponsible'])) {
                            $actions->addAction('renew', __('Renew'))
                                ->setIcon('page_right')
                                ->setURL('/modules/Library/library_lending_item_renew.php');
                        }
                    } elseif ($item['status'] == 'Reserved' && !empty($item['gibbonPersonIDStatusResponsible'])) {
                        $actions->addAction('unreserve', __('Unreserve'))
                            ->setIcon('unbookmark')
                            ->setURL('/modules/Library/library_lending_item_return.php');
                    }

                    if ($item['borrowable'] == 'Y' && $item['ownershipType'] <> 'Individual') {
                        $actions->addAction('lending', __('Lending'))
                            ->setURL('/modules/Library/library_lending_item.php')
                            ->setIcon('attendance');
                    } elseif ($this->canEdit) {
                        $actions->addAction('edit', __('Edit'))
                            ->setURL('/modules/Library/library_manage_catalog_edit.php');
                    }
                });
        }

        return $table;
    }

    protected function createLendingForm($action) : Form
    {
        $form = Form::createBlank('lendingQuickScan', $this->session->get('absoluteURL') . '/modules/Library/library_lendingProcess.php');
        $form->addHiddenValue('lendingAction', $action);
        $form->addHiddenValue('gibbonPersonIDStudent', $this->gibbonPersonID);

        $row = $form->addRow()->setClass('flex justify-between items-center');
        $row->addLabel('itemID', __('Quick Lend'));
        $row->addTextField('itemID')
            ->setValue('')
            ->maxLength(50)
            ->setClass('ml-2 flex-1')
            ->placeholder(__('School ID or ISBN'))
            ->groupAlign('left');

        if ($action == 'SignOutOther') {
            $row->addDate('returnExpected')->setValue(date('Y-m-d'))->groupAlign('middle');
        }

        if ($action == 'SignOut' || $action == 'SignOutOther') {
            $row->addSubmit(__('SignOut'))->setIcon('page_right')->setType('quickSubmit')->groupAlign('right');
        } elseif ($action == 'Return') {
            $row->addSubmit(__('Return'))->setIcon('page_left')->setType('quickSubmit')->groupAlign('right');
        } elseif ($action == 'Reserve') {
            $row->addSubmit(__('Reserve'))->setIcon('bookmark')->setType('quickSubmit')->groupAlign('right');
        }

        return $form;
    }
}
