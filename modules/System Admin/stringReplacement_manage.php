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

use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\System\StringGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/stringReplacement_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage String Replacements'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $search = isset($_GET['search'])? $_GET['search'] : '';

    $stringGateway = $container->get(StringGateway::class);

    // CRITERIA
    $criteria = $stringGateway->newQueryCriteria(true)
        ->searchBy($stringGateway->getSearchableColumns(), $search)
        ->sortBy('priority', 'DESC')
        ->fromPOST();

    echo '<h2>';
    echo __('Search');
    echo '</h2>';
    
    $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');
    
    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/stringReplacement_manage.php');
    
    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Original string, replacement string.'));
        $row->addTextField('search')->setValue($criteria->getSearchText());
    
    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));
    
    echo $form->getOutput();

    echo '<h2>';
    echo __('View');
    echo '</h2>';

    $strings = $stringGateway->queryStrings($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('stringReplacementManage', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/System Admin/stringReplacement_manage_add.php')
        ->displayLabel();

    // COLUMNS
    $table->addColumn('original', __('Original String'));
    $table->addColumn('replacement', __('Replacement String'));
    $table->addColumn('mode', __('Mode'))->translatable();
    $table->addColumn('caseSensitive', __('Case Sensitive'))->format(Format::using('yesNo', 'caseSensitive'));
    $table->addColumn('priority', __('Priority'));

    $table->addActionColumn()
        ->addParam('gibbonStringID')
        ->format(function ($row, $actions) use ($guid) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/System Admin/stringReplacement_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/System Admin/stringReplacement_manage_delete.php');
        });

    echo $table->render($strings);
}
