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
use Gibbon\Contracts\Services\Session;
use Gibbon\UI\Timetable\Layers\StaffDutyLayer;
use Gibbon\UI\Timetable\Structure;
use Gibbon\UI\Timetable\TimetableLayerInterface;
use Gibbon\UI\Timetable\Layers\ClassesLayer;
use Gibbon\UI\Timetable\Layers\ActivitiesLayer;
use Gibbon\UI\Timetable\Layers\BookingsLayer;
use Gibbon\UI\Timetable\Layers\CalendarAPILayer;
use Gibbon\UI\Timetable\Layers\StaffCoverLayer;
use Gibbon\UI\Timetable\Layers\StaffAbsenceLayer;

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

    protected $gibbonPersonID;
    protected $gibbonTTID;

    protected $layers = [];
    
    public function __construct(View $view, Session $session, Structure $structure, TimetableContext $context)
    {
        $this->view = $view;
        $this->session = $session;
        $this->structure = $structure;
        $this->context = $context;
    }

    public function setDate(string $date = null)
    {
        $this->structure->setDate($date);
        
        return $this;
    }

    public function setContext(TimetableContext $context)
    {
        $this->context = $context;

        return $this;
    }

    public function setTimetable(string $gibbonTTID, string $gibbonPersonID = null)
    {
        $this->context->set('gibbonSchoolYearID', $this->session->get('gibbonSchoolYearID'));
        $this->context->set('gibbonPersonID', $gibbonPersonID);
        $this->context->set('gibbonTTID', $gibbonTTID);

        $this->structure->setTimetable($gibbonTTID);

        return $this;
    }

    public function addLayer(TimetableLayerInterface $layer)
    {
        $this->layers[$layer->getName()] = $layer;

        return $this;
    }

    public function addCoreLayers($container)
    {
        $this->addLayer($container->get(ClassesLayer::class));
        $this->addLayer($container->get(StaffDutyLayer::class));
        $this->addLayer($container->get(StaffCoverLayer::class));
        $this->addLayer($container->get(StaffAbsenceLayer::class));
        $this->addLayer($container->get(ActivitiesLayer::class));
        $this->addLayer($container->get(BookingsLayer::class));
        $this->addLayer($container->get(CalendarAPILayer::class));

        return $this;
    }

    public function getOutput() : string
    {
        $this->loadLayers()->sortLayers();

        return $this->view->fetchFromTemplate('ui/timetable.twig.html', [
            'apiEndpoint'    => Url::fromHandlerRoute('index_tt_ajax.php')->withQueryParams($this->getUrlParams()),
            'gibbonPersonID' => $this->context->get('gibbonPersonID'),
            'structure'      => $this->structure,
            'layers'         => $this->layers,
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
            if (!$layer->getActive()) continue;

            $layer->loadItems($this->structure->getDateRange(), $this->context);

            foreach ($layer->getItems() as $item) {
                if (!$item->allDay) $this->structure->expandTimeRange($item->timeStart, $item->timeEnd);
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
