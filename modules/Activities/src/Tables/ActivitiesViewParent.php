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

namespace Gibbon\Module\Activities\Tables;

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Http\Url;
use Gibbon\Support\Facades\Access;

/**
 * ActivitiesViewParent
 *
 * @version v30
 * @since   v30
 */
class ActivitiesViewParent extends DataTable
{
    protected $activityGateway;

    public function __construct(ActivityGateway $activityGateway)
    {
        $this->activityGateway = $activityGateway;
    }

    public function createTable($gibbonSchoolYearID, $gibbonPersonID, $child)
    {
        $canSignUp = Access::allows('Activities', 'explore_activity_signUp', 'Activities_registerByParent');
        $canExplore = Access::allows('Activities', 'explore');

        $criteria = $this->activityGateway->newQueryCriteria()
            ->sortBy(['sequenceNumber', 'accessOpenDate'])
            ->fromPOST();

        $activities = $this->activityGateway->queryActivitiesByParticipant($criteria, $gibbonSchoolYearID, $gibbonPersonID);

        $table = DataTable::create('activities')->withData($activities);
        $table->setTitle(Format::name('', $child['preferredName'], $child['surname'], 'Student', false, true));

        if ($canExplore) {
            $table->addHeaderAction('explore', __('Explore Activities'))
                ->setURL(Url::fromModuleRoute('Activities', 'explore.php')->withQueryParam('gibbonPersonID', $gibbonPersonID))
                ->setIcon('squares');
        }

        $table->addColumn('category', __('Category'))
            ->sortable(['category'])
            ->context('primary')
            ->width('20%')
            ->format(function ($activity) use ($canExplore) {
                $url = Url::fromModuleRoute('Activities', 'explore_category.php')->withQueryParams(['gibbonActivityCategoryID' => $activity['gibbonActivityCategoryID'], 'sidebar' => 'false']);
                return $canExplore 
                    ? Format::link($url, $activity['category'])
                    : $activity['category'];
            });

            $table->addColumn('choices', __('Activity'))
                ->context('primary')
                ->width('40%')
                ->format(function ($activity) {
                    if (empty($activity['choices'])) {
                        return $activity['name'].'<br/>'.Format::small($activity['type']);
                    }
                    
                    $choices = explode(',', $activity['choices']);
                    return Format::small(__('Activity Choices')).':<br/>'.Format::list($choices, 'ol', 'ml-2 my-0 text-xs');
                });

            $table->addColumn('status', __('Status'))
                ->width('12%')
                ->format(function ($activity) {
                    if (empty($activity['status'])) return Format::small(__('N/A'));

                    return $activity['status'] == 'Pending' 
                        ? (!empty($activity['choices']) ? Format::tag($activity['status'], 'message')  : '')
                        : $activity['status'];
                });
        
        $table->addActionColumn()
            ->addParam('gibbonActivityCategoryID')
            ->addParam('gibbonActivityID')
            ->format(function ($activity, $actions) use ($child, $canExplore, $canSignUp) {
                if ($canExplore && !empty($activity['gibbonActivityID'])) {
                    $actions->addAction('view', __('View Details'))
                        ->addParam('sidebar', 'false')
                        ->setURL('/modules/Activities/explore_activity.php');
                }

                if (empty($activity['gibbonActivityID'])) {
                    // Check that sign up is open based on the date
                    $signUpIsOpen = false;

                    $categoryYearGroups = explode(',', $activity['gibbonYearGroupIDParentRegister'] ?? ''); 
                    if (!in_array($child['gibbonYearGroupID'], $categoryYearGroups)) {
                        return;
                    }
    
                    if (!empty($activity['accessOpenDate']) && !empty($activity['accessCloseDate'])) {
                        $accessOpenDate = \DateTime::createFromFormat('Y-m-d H:i:s', $activity['accessOpenDate'])->format('U');
                        $accessCloseDate = \DateTime::createFromFormat('Y-m-d H:i:s', $activity['accessCloseDate'])->format('U');
                        $now = (new \DateTime('now'))->format('U');
    
                        $signUpIsOpen = $accessOpenDate <= $now && $accessCloseDate >= $now;
                    }
    
                    if ($signUpIsOpen && $canSignUp) {
                        $actions->addAction('add', __('Sign Up'))
                                ->setURL('/modules/Activities/explore_activity_signUp.php')
                                ->addParam('gibbonActivityCategoryID', $activity['gibbonActivityCategoryID'])
                                ->addParam('gibbonPersonID', $child['gibbonPersonID'])
                                ->setIcon('attendance')
                                ->modalWindow(750, 440);
                    }
    
                    return;
                }
            });

        return $table;
    }
}
