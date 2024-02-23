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

use Gibbon\Http\Url;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_space_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $gibbonSpaceID = isset($_REQUEST['gibbonSpaceID']) ? $_REQUEST['gibbonSpaceID'] : '';
        $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : null;
        $gibbonTTID = isset($_REQUEST['gibbonTTID']) ? $_REQUEST['gibbonTTID'] : null;

        
            $data = array('gibbonSpaceID' => $gibbonSpaceID);
            $sql = 'SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified room does not seem to exist.');
            echo '</div>';
        } else {
            $row = $result->fetch();

            $page->breadcrumbs
                ->add(__('View Timetable by Facility'), 'tt_space.php')
                ->add($row['name']);
            
            //Create Details Table
            $table = DataTable::createDetails('basicInfo');

            $table->addColumn('name', __('Name'));

            $table->addColumn('type', __('Type'));

            $table->addColumn('capacity', __('Capacity'));

            $table->addColumn('projector', __('Projector'))
                ->width('100%');
            
            $table->addColumn('phoneInternal', __('Phone Number'))
                ->width('100%');

            echo $table->render([$row]);

            if ($search != '') {
                $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Timetable', 'tt_space.php')->withQueryParam('search', $search));
            }

            $page->navigator->addHeaderAction('print', __('Print'))
                ->setURL('/report.php')
                ->addParam('q', '/modules/Timetable/tt_space_view.php')
                ->addParam('gibbonSpaceID', $gibbonSpaceID)
                ->addParam('gibbonTTID', $gibbonTTID)
                ->addParam('ttDate', $_REQUEST['ttDate'] ?? '')
                ->setIcon('print')
                ->setTarget('_blank')
                ->directLink()
                ->displayLabel();

            $ttDate = null;
            if (!empty($_REQUEST['ttDate'])) {
                $date = Format::dateConvert($_REQUEST['ttDate']);
                $ttDate = strtotime('last Sunday +1 day', strtotime($date));
            }

            if (isset($_POST['fromTT'])) {
                if ($_POST['fromTT'] == 'Y') {
                    if (isset($_POST['spaceBookingCalendar'])) {
                        if ($_POST['spaceBookingCalendar'] == 'on' or $_POST['spaceBookingCalendar'] == 'Y') {
                            $session->set('viewCalendarSpaceBooking', 'Y');
                        } else {
                            $session->set('viewCalendarSpaceBooking', 'N');
                        }
                    } else {
                        $session->set('viewCalendarSpaceBooking', 'N');
                    }
                }
            }

            $tt = renderTTSpace($guid, $connection2, $gibbonSpaceID, $gibbonTTID, false, $ttDate, '/modules/Timetable/tt_space_view.php', "&gibbonSpaceID=$gibbonSpaceID&search=$search");

            if ($tt != false) {
                echo $tt;
            } else {
                echo $page->getBlankSlate();
            }
        }
    }
}
