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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\View\View;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Module\FreeLearning\Domain\BadgeGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/badges_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs
         ->add(__m('Manage Badges'));

    if (isModuleAccessible($guid, $connection2, '/modules/Badges/badges_manage.php') == false) {
        //Acess denied
        echo "<div class='error'>";
        echo __m('This functionality requires the Badges module to be installed, active and available.');
        echo '</div>';
    } else {
        //Acess denied
        echo "<div class='success'>";
        echo __m('The Badges module is installed, active and available, so you can access this functionality.');
        echo '</div>';

        //Set pagination variable
        $page = $_GET['page'] ?? null;
        if ((!is_numeric($page)) or $page < 1) {
            $page = 1;
        }

        $search = $_GET['search'] ?? null;

        // FORM
        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Filter'));

        $form->setClass('noIntBorder w-full');
        $form->addHiddenValue('q', '/modules/Free Learning/badges_manage.php');

        $form->setTitle( __('Search'));

        $row = $form->addRow();
            $row->addLabel('search', __m('Search For'))->description(__m('Name, Category'));
            $row->addTextField('search')->setValue($search);

        $row = $form->addRow();
            $row->addSearchSubmit($session, __('Clear Filters'));

        echo $form->getOutput();


        // TABLE
        $badgeGateway = $container->get(BadgeGateway::class);

        $criteria = $badgeGateway->newQueryCriteria(true)
            ->fromPOST();

        $badges = $badgeGateway->selectBadges(false, $search);

        $table = DataTable::createPaginated('badges', $criteria);

        $table->setTitle( __('View'));

        $table->modifyRows(function ($badge, $row) {
            return $badge['active'] == 'N' ? $row->addClass('error') : $row;
        });

        $table->addHeaderAction('add', __('Add'))
           ->setURL('/modules/Free Learning/badges_manage_add.php')
           ->addParam('search', $search)
           ->displayLabel();

        $table->addColumn('logo', __('Logo'))
            ->format(function ($values) use ($session) {
                if ($values['logo'] == null) {
                    return "<img style='margin: 5px; height: 125px; width: 125px' class='user' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/anonymous_125.jpg'/><br/>";
                } else {
                    return "<img style='margin: 5px; height: 125px; width: 125px' class='user' src='".$values['logo']."'/><br/>";
                }
            });
        $table->addColumn('name', __('Name'));
        $table->addColumn('category', __('Category'));

        $table->addActionColumn()
           ->addParam('freeLearningBadgeID')
           ->addParam('search', $search)
           ->format(function ($row, $actions) {
               $actions->addAction('edit', __('Edit'))
                   ->setURL('/modules/Free Learning/badges_manage_edit.php');
           });

        echo $table->render($badges);
    }
}
?>
