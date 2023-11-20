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

namespace Gibbon\View\Components;

use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Tables\Action;
use Gibbon\Http\Url;

/**
 * Page navigation options such as school year and search results.
 *
 * @version v23
 * @since   v23
 */
class Navigator
{
    protected $schoolYearGateway;

    protected $schoolYears = [];

    protected $actions = [];

    public function __construct(SchoolYearGateway $schoolYearGateway)
    {
        $this->schoolYearGateway = $schoolYearGateway;
    }

    public function shouldDisplay()
    {
        return !empty($this->schoolYears) || !empty($this->actions);
    }

    public function addSchoolYearNavigation(string $gibbonSchoolYearID, array $params = [])
    {
        $this->schoolYears = [
            'current'  => $this->schoolYearGateway->getByID($gibbonSchoolYearID),
            'previous' => $this->schoolYearGateway->getPreviousSchoolYearByID($gibbonSchoolYearID),
            'next'     => $this->schoolYearGateway->getNextSchoolYearByID($gibbonSchoolYearID),
            'years'    => $this->schoolYearGateway->getSchoolYearList(),
            'params'   => $params,
        ];
    }

    public function addSearchResultsAction(Url $url)
    {
        $action = $this->addHeaderAction('searchResults', __('Back to Search Results'))
            ->setIcon('search')
            ->setUrl($url)
            ->displayLabel(true)
            ->directLink();

        return $action;
    }

    public function addHeaderAction($name, $label = '')
    {
        $this->actions[$name] = new Action($name, $label);

        return $this->actions[$name];
    }

    public function getData() : array
    {
        return $this->shouldDisplay() ? [
            'schoolYears' => $this->schoolYears,
            'actions' => $this->actions,
        ] : [];
    }
}
