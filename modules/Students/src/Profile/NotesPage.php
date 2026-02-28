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

namespace Gibbon\Module\Students\Profile;

use Gibbon\Database\Connection;
use Gibbon\Support\Facades\Access;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Students\StudentNoteGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;

/**
 * NotesPage
 * 
 * Displays student notes with filtering by category.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class NotesPage extends ProfilePage
{
    private Connection $pdo;
    private StudentNoteGateway $noteGateway;
    private SettingGateway $settingGateway;

    public function __construct(
        Session $session,
        Connection $pdo,
        StudentNoteGateway $noteGateway,
        SettingGateway $settingGateway
    ) {
        parent::__construct($session);
        $this->pdo = $pdo;
        $this->noteGateway = $noteGateway;
        $this->settingGateway = $settingGateway;
    }

    /**
     * Check if the current user has permission to view student notes
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        if (!Access::allows('Students', 'View Student Profile_full')) {
            return false;
        }

        $enableStudentNotes = $this->settingGateway->getSettingByScope('Students', 'enableStudentNotes');
        if ($enableStudentNotes != 'Y') {
            return false;
        }

        return Access::allows('Students', 'student_view_details_notes_add');
    }

    /**
     * Generate HTML output for the notes page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('Invalid student ID.'));
        }

        $output = '';

        $output .= '<p>';
        $output .= __('Student Notes provide a way to store information on students which does not fit elsewhere in the system, or which you want to be able to see quickly in one place.').' <b>'.__('Please remember that notes are visible to other users who have access to full student profiles (this should not generally include parents).').'</b>';
        $output .= '</p>';

        $category = $_GET['category'] ?? null;
        $allStudents = $_GET['allStudents'] ?? '';
        $search = $_GET['search'] ?? '';

        // Render category filter if categories exist
        $output .= $this->renderCategoryFilter($category, $allStudents, $search);

        // Fetch notes
        $notes = $this->fetchNotes($category);

        // Render notes table
        $output .= $this->renderNotesTable($notes, $category, $allStudents, $search);

        return $output;
    }

    /**
     * Render category filter form
     * 
     * @param string|null $category Selected category
     * @param string $allStudents All students parameter
     * @param string $search Search parameter
     * @return string HTML for category filter
     */
    protected function renderCategoryFilter(?string $category, string $allStudents, string $search): string
    {
        $dataCategories = [];
        $sqlCategories = "SELECT * FROM gibbonStudentNoteCategory WHERE active='Y' ORDER BY name";
        $resultCategories = $this->pdo->select($sqlCategories, $dataCategories);

        if ($resultCategories->rowCount() == 0) {
            return '';
        }

        $form = Form::create('filter', $this->session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder w-full');

        $form->addHiddenValue('q', '/modules/'.$this->session->get('module').'/student_view_details.php');
        $form->addHiddenValue('gibbonPersonID', $this->gibbonPersonID);
        $form->addHiddenValue('allStudents', $allStudents);
        $form->addHiddenValue('search', $search);
        $form->addHiddenValue('subpage', 'Notes');

        $sql = "SELECT gibbonStudentNoteCategoryID as value, name FROM gibbonStudentNoteCategory WHERE active='Y' ORDER BY name";
        $rowFilter = $form->addRow();
        $rowFilter->addLabel('category', __('Category'));
        $rowFilter->addSelect('category')->fromQuery($this->pdo, $sql)->selected($category)->placeholder();

        $rowFilter = $form->addRow();
        $rowFilter->addSearchSubmit($this->session, __('Clear Filters'), ['gibbonPersonID', 'allStudents', 'search', 'subpage']);

        return $form->getOutput();
    }

    /**
     * Fetch notes from database
     * 
     * @param string|null $category Category filter
     * @return \Gibbon\Domain\QueryableGateway Query result
     */
    protected function fetchNotes(?string $category)
    {
        if ($category == null) {
            $data = ['gibbonPersonID' => $this->gibbonPersonID];
            $sql = 'SELECT gibbonStudentNote.*, gibbonStudentNoteCategory.name AS category, surname, preferredName 
                    FROM gibbonStudentNote 
                    LEFT JOIN gibbonStudentNoteCategory ON (gibbonStudentNote.gibbonStudentNoteCategoryID=gibbonStudentNoteCategory.gibbonStudentNoteCategoryID) 
                    JOIN gibbonPerson ON (gibbonStudentNote.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) 
                    WHERE gibbonStudentNote.gibbonPersonID=:gibbonPersonID 
                    ORDER BY timestamp DESC';
        } else {
            $data = ['gibbonPersonID' => $this->gibbonPersonID, 'gibbonStudentNoteCategoryID' => $category];
            $sql = 'SELECT gibbonStudentNote.*, gibbonStudentNoteCategory.name AS category, surname, preferredName 
                    FROM gibbonStudentNote 
                    LEFT JOIN gibbonStudentNoteCategory ON (gibbonStudentNote.gibbonStudentNoteCategoryID=gibbonStudentNoteCategory.gibbonStudentNoteCategoryID) 
                    JOIN gibbonPerson ON (gibbonStudentNote.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) 
                    WHERE gibbonStudentNote.gibbonPersonID=:gibbonPersonID 
                    AND gibbonStudentNote.gibbonStudentNoteCategoryID=:gibbonStudentNoteCategoryID 
                    ORDER BY timestamp DESC';
        }

        return $this->pdo->select($sql, $data);
    }

    /**
     * Render notes table
     * 
     * @param mixed $notes Notes data
     * @param string|null $category Category filter
     * @param string $allStudents All students parameter
     * @param string $search Search parameter
     * @return string HTML for notes table
     */
    protected function renderNotesTable($notes, ?string $category, string $allStudents, string $search): string
    {
        $table = DataTable::createPaginated('studentNotes', $this->noteGateway->newQueryCriteria(true));

        $table->addExpandableColumn('note');

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Students/student_view_details_notes_add.php')
            ->addParam('gibbonPersonID', $this->gibbonPersonID)
            ->addParam('allStudents', $allStudents)
            ->addParam('search', $search)
            ->addParam('subpage', 'Notes')
            ->addParam('category', $category ?? '')
            ->displayLabel();

        $table->addColumn('date', __('Date'))
            ->description(__('Time'))
            ->format(function ($note) {
                return Format::date($note['timestamp']).'<br/>'.Format::small(Format::time($note['timestamp']));
            });

        $table->addColumn('category', __('Category'))
            ->translatable();

        $table->addColumn('title', __('Title'))
            ->description(__('Overview'))
            ->format(function ($note) {
                $title = !empty($note['title']) ? $note['title'] : __('N/A');
                $overview = substr(strip_tags($note['note']), 0, 60);
                return $title.'<br/><span style="font-size: 75%; font-style: italic">'.$overview.'</span>';
            });

        $table->addColumn('noteTaker', __('Note Taker'))
            ->format(Format::using('name', ['', 'preferredName', 'surname', 'Staff', false, true]));

        // ACTIONS
        $highestAction = Access::getHighestGroupedAction('Students', 'View Student Profile');
        $table->addActionColumn()
            ->addParam('gibbonStudentNoteID')
            ->addParam('gibbonPersonID', $this->gibbonPersonID)
            ->addParam('allStudents', $allStudents)
            ->addParam('search', $search)
            ->addParam('subpage', 'Notes')
            ->addParam('category', $category ?? '')
            ->format(function ($note, $actions) use ($highestAction) {
                if ($note['gibbonPersonIDCreator'] == $this->session->get('gibbonPersonID') || $highestAction == "View Student Profile_fullEditAllNotes") {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Students/student_view_details_notes_edit.php');
                }

                if ($highestAction == "View Student Profile_fullEditAllNotes") {
                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Students/student_view_details_notes_delete.php');
                }
            });

        return $table->render($notes->toDataSet());
    }
}
