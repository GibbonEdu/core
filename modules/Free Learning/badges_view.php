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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Module\FreeLearning\Domain\BadgeGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/badges_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs
         ->add(__m('View Badges'));

    if (isModuleAccessible($guid, $connection2, '/modules/Badges/badges_manage.php') == false) {
        //Acess denied
        echo "<div class='error'>";
        echo __m('This functionality requires the Badges module to be installed, active and available.');
        echo '</div>';
    } else {
        $badgeGateway = $container->get(BadgeGateway::class);

        $criteria = $badgeGateway->newQueryCriteria(true)
            ->fromPOST();

        $badges = $badgeGateway->selectBadges();

        $table = DataTable::createPaginated('badges', $criteria);

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
        $table->addColumn('description', __('Description'));

        echo $table->render($badges);
    }
}
?>
