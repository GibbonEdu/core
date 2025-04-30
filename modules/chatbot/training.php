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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\FormFactory;

require_once __DIR__ . '/../../gibbon.php';
require_once __DIR__ . '/moduleFunctions.php';

// Check access
$page->breadcrumbs->add(__('Training Data'));

// Check access rights
if (!isActionAccessible($guid, $connection2, '/modules/ChatBot/training.php', 'Manage Training')) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
    return;
}

// Get action with fallback
$action = $_POST['action'] ?? '';

// Get search parameters
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

// Initialize the page
$page->breadcrumbs->add(__('Training Data'));

// Get database connection
try {
    $connection2 = $container->get('db')->getConnection();
    $pdo = $connection2;
} catch (Exception $e) {
    $page->addError('Database connection error: ' . $e->getMessage());
    return;
}

// Handle training data submission
if (!empty($action)) {
    if ($action == 'add' && !empty($_POST['question']) && !empty($_POST['answer'])) {
        try {
            $data = [
                'question' => $_POST['question'],
                'answer' => $_POST['answer'],
                'approved' => 1
            ];
            
            $sql = "INSERT INTO gibbonChatBotTraining (question, answer, approved, created_at) VALUES (:question, :answer, :approved, NOW())";
            $stmt = $connection2->prepare($sql);
            $stmt->execute($data);
            
            $page->addSuccess(__('Training data added successfully.'));
        } catch (Exception $e) {
            $page->addError(__('Failed to add training data: ') . $e->getMessage());
        }
    } elseif ($action == 'delete' && !empty($_POST['id'])) {
        try {
            $data = ['id' => $_POST['id']];
            $sql = "DELETE FROM gibbonChatBotTraining WHERE gibbonChatBotTrainingID = :id";
            $stmt = $connection2->prepare($sql);
            $stmt->execute($data);
            
            $page->addSuccess(__('Training data deleted successfully.'));
        } catch (Exception $e) {
            $page->addError(__('Failed to delete training data: ') . $e->getMessage());
        }
    }
}

// Get existing training data with search
try {
    $params = [];
    $where = [];
    
    if (!empty($search)) {
        $where[] = "(question LIKE :search OR answer LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }
    
    $sql = "SELECT * FROM gibbonChatBotTraining";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $connection2->prepare($sql);
    $stmt->execute($params);
    $trainingData = $stmt->fetchAll();
} catch (Exception $e) {
    $page->addError('Failed to fetch training data: ' . $e->getMessage());
    $trainingData = array();
}

// Get unique categories for filter
try {
    $stmt = $connection2->query("SELECT DISTINCT category FROM gibbonChatBotTraining WHERE category IS NOT NULL ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categories = array();
}

// Add search form
$form = Form::create('searchTraining', $session->get('absoluteURL') . '/index.php', 'get');
$form->setTitle(__('Search Training Data'));

// Add hidden fields to preserve the URL structure
$form->addHiddenValue('q', '/modules/ChatBot/training.php');

$row = $form->addRow();
$row->addLabel('search', __('Search'));
$row->addTextField('search')
    ->setValue($search)
    ->placeholder(__('Search questions or answers...'));

$row = $form->addRow();
$row->addLabel('category', __('Filter by Category'));
$row->addSelect('category')
    ->fromArray(array_combine($categories, $categories))
    ->selected($categoryFilter)
    ->placeholder(__('All Categories'));

$row = $form->addRow();
$row->addSearchSubmit($session, __('Search'));

echo $form->getOutput();

// Add file upload form
$form = Form::create('uploadTraining', $session->get('absoluteURL') . '/modules/ChatBot/upload.php', 'post');
$form->setTitle(__('Upload Training Data'));
$form->addHiddenValue('address', $session->get('address'));

// Set form attributes directly
$form->setAttribute('enctype', 'multipart/form-data');

$row = $form->addRow();
$row->addLabel('trainingFile', __('Training File'));
$row->addFileUpload('trainingFile')
    ->accepts('.txt,.csv,.json,.pdf')
    ->required();

$row = $form->addRow();
$row->addSubmit(__('Upload'));

echo $form->getOutput();

// Add training data form
$form = Form::create('addTraining', $session->get('absoluteURL') . '/index.php?q=/modules/ChatBot/training.php', 'post');
$form->setTitle(__('Add Training Data'));
$form->addHiddenValue('action', 'add');
$form->addHiddenValue('address', $session->get('address'));

$row = $form->addRow();
$row->addLabel('question', __('Question'));
$row->addTextArea('question')->required()->setRows(3);

$row = $form->addRow();
$row->addLabel('answer', __('Answer'));
$row->addTextArea('answer')->required()->setRows(5);

$row = $form->addRow();
$row->addSubmit(__('Add'));

echo $form->getOutput();

// Display existing training data
$table = DataTable::create('trainingData');
$table->setTitle(__('Existing Training Data'));

if (!empty($search)) {
    $table->setDescription(__('Search term: ') . $search);
}
if (!empty($categoryFilter)) {
    $table->addHeaderAction('clearFilter', __('Clear Filter'))
        ->setURL('/index.php')
        ->addParam('q', '/modules/ChatBot/training.php')
        ->setIcon('refresh')
        ->displayLabel();
}

$table->addColumn('question', __('Question'));
$table->addColumn('answer', __('Answer'));
$table->addColumn('created_at', __('Date Created'))
    ->format(function($row) {
        return Format::date($row['created_at']);
    });

// Add actions column
$table->addActionColumn()
    ->addParam('id')
    ->format(function ($row, $actions) {
        $actions->addAction('delete', __('Delete'))
            ->setURL('/modules/ChatBot/training.php')
            ->addParam('action', 'delete')
            ->addParam('id', $row['gibbonChatBotTrainingID'])
            ->setIcon('garbage')
            ->isDirect()
            ->addConfirmation(__('Are you sure you want to delete this training data?'));
    });

echo $table->render($trainingData);
?> 