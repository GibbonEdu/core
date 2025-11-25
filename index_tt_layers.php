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


use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\UI\Timetable\TimetableContext;
use Gibbon\UI\Timetable\Timetable;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Form;

// Gibbon system-wide includes
include './gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt.php') == false) {
    // Access denied
    echo Format::alert(__('Your request failed because you do not have access to this action.'), 'error');
} else {

    $userGateway = $container->get(UserGateway::class);
    $layerToggle = $userGateway->getUserPreferenceByScope($session->get('gibbonPersonID'), 'ttLayers');

    // Create timetable context
    $context = $container->get(TimetableContext::class)
        ->set('gibbonSchoolYearID', $session->get('gibbonSchoolYearID'))
        ->set('gibbonPersonID', $session->get('gibbonPersonID'));

    // Build timetable
    
    $timetable = $container->get(Timetable::class)
        ->setDate(date('Y-m-d'))
        ->setContext($context)
        ->addCoreLayers($container); 

    $layers = [];
    foreach ($timetable->getLayers() as $layer) {
        $layers[] = [
            'layerID' => $layer->getID(),
            'name'    => $layer->getName(),
            'color'   => $layer->getColor(),
            'order'   => $layer->getOrder(),
            'type'    => $layer->getType(),
            'toggle'  => $layerToggle[$layer->getID()] ?? 'on',
        ];
    } 
    $layers = array_reverse($layers);
    $structure = $timetable->getStructure();

    // DATA TABLE
    $table = DataTable::create('layers');

    $table->addDraggableColumn('layerID', $session->get('absoluteURL').'/index_tt_layers_ajax.php');

    $table->addColumn('color', __('Colour'))
        ->width('8%')
        ->format(function ($values) use ($structure) {
            $colors = $structure->getColors($values['color'] ?? '');
            return Format::colorSwatch($colors['background'] ?? $values['color']);
        });

    $table->addColumn('name', __('Layers'))
        ->format(function ($values) {
            return $values['toggle'] === '0' ? '<span class="line-through opacity-50">'.__($values['name']).'</span>' : __($values['name']);
        });

    $table->addColumn('status', '')
        ->width('10%')
        ->format(function ($values) {
            if ($values['toggle'] === '0') return Format::tooltip(icon('basic', 'eye-slash', 'size-5 text-gray-500'), __('Hidden'));
            if ($values['type'] == 'calendar') return Format::tooltip(icon('solid', 'calendar', 'size-5 text-gray-500'), __('Calendar'));
            return '';
        });

    echo $table->render($layers);

    // Add a submit button to refresh the timetable after making changes
    $form = Form::createBlank('layers', $session->get('absoluteURL'));
    $form->addSubmit(__('Update'));
    echo $form->getOutput();
}
