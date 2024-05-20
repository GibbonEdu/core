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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Planner\ResourceGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('View Resources'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/resources_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    echo '<h3>';
    echo __('Filters');
    echo '</h3>';

    //Get current filter values
    $tags = isset($_REQUEST['tag'])? trim($_REQUEST['tag']) : '';
    $tags = preg_replace('/[^a-zA-Z0-9-_, \']/', '', $tags);
    $tagsArray = !empty($tags)? explode(',', $tags) : [];

    $category = (isset($_REQUEST['category']))? trim($_REQUEST['category']) : null;
    $purpose = (isset($_REQUEST['purpose']))? trim($_REQUEST['purpose']) : null;
    $gibbonYearGroupID = (isset($_REQUEST['gibbonYearGroupID']))? trim($_REQUEST['gibbonYearGroupID']) : null;

    //Display filters

    $form = Form::create('resourcesView', $session->get('absoluteURL').'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/resources_view.php');

    $sql = "SELECT tag as value, CONCAT(tag, ' <i>(', count, ')</i>') as name FROM gibbonResourceTag WHERE count>0 ORDER BY tag";
    $row = $form->addRow();
        $row->addLabel('tag', __('Tags'));
        $row->addFinder('tag')->fromQuery($pdo, $sql)->setParameter('hintText', __('Type a tag...'))->selected($tagsArray);

    $settingGateway = $container->get(SettingGateway::class);

    $categories = $settingGateway->getSettingByScope('Resources', 'categories');
    $row = $form->addRow();
        $row->addLabel('category', __('Category'));
        $row->addSelect('category')->fromString($categories)->placeholder()->selected($category);

    $purposesGeneral = $settingGateway->getSettingByScope('Resources', 'purposesGeneral');
    $purposesRestricted = $settingGateway->getSettingByScope('Resources', 'purposesRestricted');
    $row = $form->addRow();
        $row->addLabel('purpose', __('Purpose'));
        $row->addSelect('purpose')->fromString($purposesGeneral)->fromString($purposesRestricted)->placeholder()->selected($purpose);

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->selected($gibbonYearGroupID);

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();


    $resourceGateway = $container->get(ResourceGateway::class);
    // QUERY
    $criteria = $resourceGateway->newQueryCriteria(true)
        ->filterBy('tags', $tags)
        ->filterBy('category', $category)
        ->filterBy('purpose', $purpose)
        ->filterBy('gibbonYearGroupID', $gibbonYearGroupID)
        ->sortBy('timestamp', 'DESC')
        ->fromPOST();
    
    $resources = $resourceGateway->queryResources($criteria);
    // TABLE
    $table = DataTable::createPaginated('resources', $criteria);
    $table->setTitle(__('View'));
        $table->addHeaderAction('add', __('Add'))
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

    echo $table->render($resources);

    //Print sidebar
    $session->set('sidebarExtra', sidebarExtraResources($guid, $connection2));
}
?>
