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

namespace Gibbon\UI\Timetable;

use Gibbon\Http\Url;
use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Forms\OutputableInterface;
use Gibbon\UI\Timetable\Structure;
use Gibbon\UI\Timetable\TimetableLayerInterface;
use Gibbon\UI\Timetable\Layers\StaffDutyLayer;
use Gibbon\UI\Timetable\Layers\ClassesLayer;
use Gibbon\UI\Timetable\Layers\ActivitiesLayer;
use Gibbon\UI\Timetable\Layers\BookingsLayer;
use Gibbon\UI\Timetable\Layers\CalendarEventsLayer;
use Gibbon\UI\Timetable\Layers\StaffCoverLayer;
use Gibbon\UI\Timetable\Layers\StaffAbsenceLayer;
use Gibbon\UI\Timetable\Layers\SchoolCalendarLayer;
use Gibbon\UI\Timetable\Layers\PersonalCalendarLayer;
use Psr\Container\ContainerInterface;

/**
 * Timetable UI
 *
 * @version  v29
 * @since    v29
 */
class Timetable implements OutputableInterface
{
    protected $view;
    protected $structure;
    protected $context;
    protected $access;
    protected $layers = [];
    
    /**
     * Construct via the Container
     *
     * @param View $view
     * @param Structure $structure
     * @param TimetableContext $context
     */
    public function __construct(View $view, Structure $structure, TimetableContext $context, TimetableAccess $access)
    {
        $this->view = $view;
        $this->structure = $structure;
        $this->context = $context;
        $this->access = $access;
    }

    /**
     * Set the date for this timetable, which enables the structure to calculate
     * the current week and all other relative date and time settings.
     *
     * @param string|null $date
     * @return self
     */
    public function setDate($date = null)
    {
        $this->structure->setDate($date);
        
        return $this;
    }

    /**
     * Set the context for this timetable, then load the structure.
     *
     * @param TimetableContext $context
     * @return self
     */
    public function setContext(TimetableContext $context)
    {
        $this->context = $context;

        $this->context->loadData($this->access->getPreferences());

        $gibbonTTID = $this->context->has('gibbonTTID')
            ? $this->context->get('gibbonTTID')
            : $this->context->get('ttOptions')['gibbonTTID'] ?? null;
        $this->structure->setTimetable($this->context->get('gibbonSchoolYearID'), $gibbonTTID);
        $this->context->set('gibbonTTID', $this->structure->getActiveTimetable());

        return $this;
    }

    /**
     * Add a custom layer object to the timetable.
     *
     * @param TimetableLayerInterface $layer
     * @return self
     */
    public function addLayer(TimetableLayerInterface $layer)
    {
        if ($layer->checkAccess($this->context)) {
            $this->layers[$layer->getName()] = $layer;
        }

        return $this;
    }

    /**
     * Get a layer by name.
     *
     * @param string $layerName
     * @return TimetableLayerInterface
     */
    public function getLayer(string $layerName)
    {
        return $this->layers[$layerName] ?? null;
    }

    /**
     * Add built-in core layers to the timetable. Omit this method for custom 
     * rendered timetables.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function addCoreLayers(ContainerInterface $container)
    {
        $this->addLayer($container->get(ClassesLayer::class));
        $this->addLayer($container->get(StaffDutyLayer::class));
        $this->addLayer($container->get(StaffCoverLayer::class));
        $this->addLayer($container->get(StaffAbsenceLayer::class));
        $this->addLayer($container->get(ActivitiesLayer::class));
        $this->addLayer($container->get(CalendarEventsLayer::class));
        $this->addLayer($container->get(BookingsLayer::class));
        $this->addLayer($container->get(SchoolCalendarLayer::class));
        $this->addLayer($container->get(PersonalCalendarLayer::class));

        return $this;
    }

    /**
     * Render the timetable templates and return the result as a string.
     *
     * @return string
     */
    public function getOutput() : string
    {
        if (!$this->access->checkAccess($this->context)) {
            return Format::alert(__('You do not have permission to access this timetable at this time.'), 'error');
        }

        $this->loadLayers()->processLayers()->sortLayers()->checkLayers()->toggleLayers();

        return $this->view->fetchFromTemplate('ui/timetable.twig.html', [
            'apiEndpoint'    => Url::fromHandlerRoute('index_tt_ajax.php')->withQueryParams($this->getUrlParams()),
            'preferencesUrl' => Url::fromHandlerRoute('preferences_ajax.php'),
            'gibbonPersonID' => $this->context->get('gibbonPersonID'),
            'gibbonSpaceID'  => $this->context->get('gibbonSpaceID'),
            'gibbonTTID'     => $this->context->get('gibbonTTID'),
            'options'        => $this->context->get('ttOptions'),
            'timetables'     => $this->structure->getTimetables(),
            'structure'      => $this->structure,
            'layers'         => $this->layers,
            'layersToggle'   => json_encode($this->getLayerStates()),
            'format'         => $this->context->get('format'),
            'edit'           => $this->context->get('edit'),
        ]);
    }

    /**
     * Load items within each active layer and update the resulting time range for the timetable.
     *
     * @return self
     */
    protected function loadLayers()
    {
        foreach ($this->layers as $layer) {
            if (!$layer->isActive()) continue;

            $layer->loadItems($this->structure->getDateRange(), $this->context);
        }

        return $this;
    }

    /**
     * Update layers based on special days and absences.
     *
     * @return self
     */
    protected function processLayers()
    {
        $absenceLayer = $this->getLayer('Staff Absence');
        $absences = !empty($absenceLayer) ? $absenceLayer->getItems() : [];

        foreach ($this->layers as $layer) {
            if (!$layer->isActive()) continue;

            foreach ($layer->getItems() as $item) {
                if (!$item->allDay) $this->structure->expandTimeRange($item->timeStart, $item->timeEnd);

                if ($layer->getType() == 'timetabled' && $specialDay = $this->structure->getSpecialDay($item->date)) {
                    $item->constrainTiming($specialDay['schoolStart'] ?? '', $specialDay['schoolEnd'] ?? '');
                }

                if ($layer->getType() == 'timetabled' && !empty($absences)) {
                    foreach ($absences as $absence) {
                        if ($item->checkOverlap($absence)) {
                            $layer->updateItem($item, 'absent');
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Re-order the layers array based on each layer's order number.
     *
     * @return self
     */
    protected function sortLayers()
    {
        uasort($this->layers, function ($a, $b) {
            if ($a->getOrder() != $b->getOrder()) {
                return $a->getOrder() <=> $b->getOrder();
            }

            return $a->getName() <=> $b->getName();
        });

        return $this;
    }

    /**
     * Check for overlap within layers and group items occurring at the same time.
     *
     * @return self
     */
    protected function checkLayers()
    {
        $columns = $this->structure->getColumns();

        foreach ($this->layers as $layer) {
            // Check for timetabled items that overlap periods (for bookings)
            if ($layer->getType() == 'timetabled') {
                foreach ($columns as $date => $column) {
                    foreach ($layer->getItemsByDate($date) as $item) {
                        foreach ($column as $period) {
                            if ($item->checkOverlap($period, false)) {
                                $period->addStatus('overlap');
                            }
                        }
                    }
                }
            }

            // Check for identical timed items within the same layer (stack them)
            $itemsGrouped = array_reduce($layer->getItems(), function ($group, $item) {
                $group[$item->getKey()][] = $item;
                return $group;
            }, []);

            foreach ($itemsGrouped as $itemList) {
                $item = array_shift($itemList);
                if ($item->allDay) continue;
                
                $item->set('overlap', $itemList ?? []);

                foreach ($itemList as $overlap) {
                    $overlap->set('active', false);
                }
            }

            $layer->filterItems(function ($item) {
                return $item->isActive();
            });

            // Check non-timetabled items that overlap lower items (add indicator icon)
            if ($layer->getType() != 'timetabled') {
                foreach ($layer->getItems() as $item) {
                    if ($item->allDay) continue;
                    
                    foreach ($this->layers as $otherLayer) {
                        if (!$otherLayer->isActive()) continue;
                        if ($otherLayer->getOrder() >= $layer->getOrder()) continue;

                        foreach ($otherLayer->getItems() as $otherItem) {
                            if ($item->checkOverlap($otherItem, false) && $otherItem->isActive()) {
                                $item->addStatus('overlap');
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Load user preferences for the active state of each layer.
     *
     * @return self
     */
    protected function toggleLayers()
    {
        if (!$this->context->has('ttLayers')) return;

        $layerStates = $this->context->get('ttLayers'); 

        foreach ($this->layers as $layer) {
            $layer->setActive($layerStates[$layer->getID()] ?? 1);
        }

        return $this;
    }

    /**
     * Return an array with layer names as keys and the active state bool as a value.
     */
    protected function getLayerStates()
    {
        return array_reduce($this->layers, function ($group, $layer) {
            $group[$layer->getID()] = $layer->isActive();
            return $group;
        }, []);
    }

    /**
     * Return an array of parameters for timetable links and API calls.
     *
     * @return array
     */
    protected function getUrlParams() : array
    {
        return [
            'q'              => $_GET['q'] ?? '',
            'gibbonPersonID' => $this->context->get('gibbonPersonID'),
            'gibbonSpaceID'  => $this->context->get('gibbonSpaceID'),
            'gibbonTTID'     => $this->context->get('gibbonTTID'),
        ];
    }
}
