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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Planner\ResourceGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('Manage Resources'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/resources_manage.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    $search = $_GET['search'] ?? null;

    // FORM
    $form = Form::create('resourcesManage', $session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__('Search'));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/resources_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Resource name.'));
        $row->addTextField('search')->setValue($search);

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Search'));

    echo $form->getOutput();

    $resourceGateway = $container->get(ResourceGateway::class);

    // QUERY
    $criteria = $resourceGateway->newQueryCriteria(true)
        ->searchBy($resourceGateway->getSearchableColumns(), $search)
        ->sortBy('timestamp', 'DESC')
        ->fromPOST();

    $gibbonPersonID = $highestAction == 'Manage Resources_all' ? null : $session->get('gibbonPersonID');
    $resources = $resourceGateway->queryResources($criteria, $gibbonPersonID);

    // TABLE
    $table = DataTable::createPaginated('resources', $criteria);
    $table->setTitle(__('View'));
    $table->addHeaderAction('add', __('Add'))
        ->addParam('search', $search)
        ->setURL('/modules/' .$session->get('module') . '/resources_manage_add.php')
        ->displayLabel();

    $table->addColumn('name', __('Name'))
        ->description(__('Contributor'))
        ->format(function ($resource) use ($guid) {
            return getResourceLink($guid, $resource['gibbonResourceID'], $resource['type'], $resource['name'], $resource['content'])
                . Format::small(Format::name($resource['title'], $resource['preferredName'], $resource['surname'], 'Staff'));
        });

    $table->addColumn('type', __('Type'));

    $table->addColumn('category', __('Category'))
        ->description(__('Purpose'))
        ->format(function ($resource) {
            return $resource['category'] . '<br/>'. Format::small(__($resource['purpose']));
        });

    $table->addColumn('tags', __('Tags'))
        ->format(function ($resource) {
            $output = '';
            $tags = explode(',', $resource['tags']);
            natcasesort($tags);
            foreach ($tags as $tag) {
                $output .= trim($tag).', ';
            }
            return substr($output, 0, -2);
        });

    $table->addColumn('yearGroupList', __('Year Groups'))
        ->format(function ($resource) {
            return $resource['yearGroups'] >= $resource['totalYearGroups']
            ? __('All Years')
            : $resource['yearGroupList'];
        });

    $actions = $table->addActionColumn()
        ->addParam('gibbonResourceID')
        ->addParam('search', $search)
        ->format(function ($resource, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Planner/resources_manage_edit.php');
        });

    echo $table->render($resources);
}
