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
use Gibbon\UI\Timetable\Structure;
use Gibbon\UI\Timetable\TimetableLayerInterface;

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

    protected $gibbonPersonID;
    protected $gibbonTTID;

    protected $layers = [];
    
    public function __construct(View $view, Session $session, Structure $structure)
    {
        $this->view = $view;
        $this->session = $session;
        $this->structure = $structure;
    }

    public function create(string $date = null)
    {
        $this->structure->setDate($date);
        
        return $this;
    }

    public function setTimetable(string $gibbonTTID, string $gibbonPersonID = null)
    {
        $this->gibbonPersonID = $gibbonPersonID;
        $this->gibbonTTID = $gibbonTTID;

        $this->structure->setTimetable($gibbonTTID);

        return $this;
    }

    public function addLayer(TimetableLayerInterface $layer)
    {
        $this->layers[$layer->getName()] = $layer;

        return $this;
    }

    public function addCoreLayers()
    {
        return $this;
    }

    public function getLayers()
    {
        return $this->layers;
    }

    public function getOutput() : string
    {
        $this->loadLayers()->sortLayers();

        return $this->view->fetchFromTemplate('ui/timetable.twig.html', [
            'apiEndpoint'    => Url::fromHandlerRoute('index_tt_ajax.php')->withQueryParams($this->getUrlParams()),
            'gibbonPersonID' => $this->gibbonPersonID,
            'structure'      => $this->structure,
            'layers'         => $this->getLayers(),
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

            $layer->loadItems($this->structure->getStartDate(), $this->structure->getEndDate(), $this->gibbonTTID, $this->gibbonPersonID);

            foreach ($layer->getItems() as $item) {
                if (!$item->allDay) $this->structure->updateTimeRange($item->timeStart, $item->timeEnd);
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
            'gibbonPersonID'       => $this->gibbonPersonID,
            'gibbonTTID'           => $this->gibbonTTID,
            'schoolCalendar'       => $this->session->get('viewCalendarSchool'),
            'personalCalendar'     => $this->session->get('viewCalendarPersonal'),
            'spaceBookingCalendar' => $this->session->get('viewCalendarSpaceBooking'),
            'fromTT'               => 'Y',
        ];
    }
}
