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
use Gibbon\Forms\OutputableInterface;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Contracts\Services\Session;
use Gibbon\UI\Timetable\Structure;
use Gibbon\UI\Timetable\TimetableLayerInterface;
use Gibbon\UI\Timetable\Layers\StaffDutyLayer;
use Gibbon\UI\Timetable\Layers\ClassesLayer;
use Gibbon\UI\Timetable\Layers\ActivitiesLayer;
use Gibbon\UI\Timetable\Layers\BookingsLayer;
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
    protected $session;
    protected $structure;
    protected $context;

    protected $userGateway;

    protected $gibbonPersonID;
    protected $gibbonTTID;

    protected $layers = [];
    
    /**
     * Construct via the Container
     *
     * @param View $view
     * @param Session $session
     * @param Structure $structure
     * @param TimetableContext $context
     * @param UserGateway $userGateway
     */
    public function __construct(View $view, Session $session, Structure $structure, TimetableContext $context, UserGateway $userGateway)
    {
        $this->view = $view;
        $this->session = $session;
        $this->structure = $structure;
        $this->context = $context;
        $this->userGateway = $userGateway;
    }

    /**
     * Set the date for this timetable, which enables the structure to calculate
     * the current week and all other relative date and time settings.
     *
     * @param string|null $date
     * @return self
     */
    public function setDate(string $date = null)
    {
        $this->structure->setDate($date);
        
        return $this;
    }

    /**
     * Manually set the timetable context with custom values.
     *
     * @param TimetableContext $context
     * @return self
     */
    public function setContext(TimetableContext $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Set the active timetable by ID, and optionally the user for this timetable.
     *
     * @param string $gibbonTTID
     * @param string|null $gibbonPersonID
     * @return self
     */
    public function setTimetable(string $gibbonTTID, string $gibbonPersonID = null)
    {
        $this->context->set('gibbonSchoolYearID', $this->session->get('gibbonSchoolYearID'));
        $this->context->set('gibbonPersonID', $gibbonPersonID);
        $this->context->set('gibbonTTID', $gibbonTTID);
        
        $this->structure->setTimetable($gibbonTTID);

        return $this;
    }

    /**
     * Add a custom layer object to the timetable.
     */
    public function addLayer(TimetableLayerInterface $layer)
    {
        $this->layers[$layer->getName()] = $layer;

        return $this;
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
        $this->loadLayers()->sortLayers()->toggleLayers();

        return $this->view->fetchFromTemplate('ui/timetable.twig.html', [
            'apiEndpoint'    => Url::fromHandlerRoute('index_tt_ajax.php')->withQueryParams($this->getUrlParams()),
            'preferencesUrl' => Url::fromHandlerRoute('preferences_ajax.php'),
            'gibbonPersonID' => $this->context->get('gibbonPersonID'),
            'gibbonTTID'     => $this->context->get('gibbonTTID'),
            'structure'      => $this->structure,
            'layers'         => $this->layers,
            'layersToggle'   => json_encode($this->getLayerPreferences()),
            'timetables'     => ['00000015' => 'Secondary Timetable', '00000016' => 'Primary Timetable'],
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

            foreach ($layer->getItems() as $item) {
                if (!$item->allDay) $this->structure->expandTimeRange($item->timeStart, $item->timeEnd);

                // Constrain timetabled layer items to timing changes here... ?
                // $item = $this->structure->constrainTiming($item, $specialDay['schoolStart'], $specialDay['schoolEnd']);
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
     * Load user preferences for the active state of each layer.
     *
     * @return self
     */
    protected function toggleLayers()
    {
        if (!$this->context->has('gibbonPersonID')) return;

        $user = $this->userGateway->getByID($this->context->get('gibbonPersonID'), ['preferences']);
        $preferences = !empty($user['preferences']) ? json_decode($user['preferences'] ?? '', true) : []; 

        foreach ($this->layers as $layer) {
            $layer->setActive($preferences['ttLayers'][$layer->getID()] ?? 1);
        }

        return $this;
    }

    /**
     * Return an array with layer names as keys and the active state bool as a value.
     */
    protected function getLayerPreferences()
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
            'q'                    => $_GET['q'] ?? '',
            'gibbonPersonID'       => $this->context->get('gibbonPersonID'),
            'gibbonTTID'           => $this->context->get('gibbonTTID'),
            'schoolCalendar'       => $this->session->get('viewCalendarSchool'),
            'personalCalendar'     => $this->session->get('viewCalendarPersonal'),
            'spaceBookingCalendar' => $this->session->get('viewCalendarSpaceBooking'),
            'fromTT'               => 'Y',
        ];
    }
}
