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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\School\SchoolYearTermGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $page->breadcrumbs->add(__('View Activities'));

        $page->return->addReturns(['success0' => __('Registration was successful.'), 'success1' => __('Unregistration was successful.'), 'success2' => __('Registration was successful, but the activity is full, so you are on the waiting list.')]);

        //Get current role category
        $roleCategory = $session->get('gibbonRoleIDCurrentCategory');

        //Check access controls
        $settingGateway = $container->get(SettingGateway::class);
        $allActivityAccess = $settingGateway->getSettingByScope('Activities', 'access');
        $hideExternalProviderCost = $settingGateway->getSettingByScope('Activities', 'hideExternalProviderCost');

        if (!($allActivityAccess == 'View' or $allActivityAccess == 'Register')) {
            echo "<div class='error'>";
            echo __('Activity listing is currently closed.');
            echo '</div>';
        } else {
            if ($allActivityAccess == 'View') {
                echo "<div class='warning'>";
                echo __('Registration is currently closed, but you can still view activities.');
                echo '</div>';
            }

            $disableExternalProviderSignup = $settingGateway->getSettingByScope('Activities', 'disableExternalProviderSignup');
            if ($disableExternalProviderSignup == 'Y') {
                echo "<div class='warning'>";
                echo __('Please check activity details for instructions on how to register for activities offered by outside providers.');
                echo '</div>';
            }

            $gibbonPersonID = null;

            //If student, set gibbonPersonID to self
            if ($roleCategory == 'Student' and $highestAction == 'View Activities_studentRegister') {
                $gibbonPersonID = $session->get('gibbonPersonID');
            }
            //IF PARENT, SET UP LIST OF CHILDREN
            $countChild = 0;
            if ($roleCategory == 'Parent' and $highestAction == 'View Activities_studentRegisterByParent') {
                $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

                    $data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
                    $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);

                if ($result->rowCount() < 1) {
                    echo $page->getBlankSlate();
                } else {
                    $options = array();
                    while ($row = $result->fetch()) {

                        $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'date' => date('Y-m-d'));
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL OR dateEnd>=:date) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);
                        if ($resultChild->rowCount() > 0) {
                            while ($rowChild = $resultChild->fetch()) {
                                $options[$rowChild['gibbonPersonID']] = Format::name('', $rowChild['preferredName'], $rowChild['surname'], 'Student', true);
                                ++$countChild;
                            }
                        }
                    }

                    if (count($options) == 1) {
                        $gibbonPersonID = key($options);
                    }

                    if ($countChild == 0) {
                        echo $page->getBlankSlate();
                    }
                }
            }

            $search = $_GET['search'] ?? '';

            $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php','get');
            $form->setTitle(__('Filter & Search'));
            $form->setClass('noIntBorder fullWidth');

            $form->addHiddenValue('q', "/modules/".$session->get('module')."/activities_view.php");

            if ($countChild > 0 and $roleCategory == 'Parent' and $highestAction == 'View Activities_studentRegisterByParent') {
                $row = $form->addRow();
                    $row->addLabel('gibbonPersonID', __('Child'))->description(__('Choose the child you are registering for.'));
                    $row->addSelect('gibbonPersonID')->fromArray($options)->selected($gibbonPersonID)->placeholder(($countChild > 1)? '' : null);
            }

            $row = $form->addRow();
                $row->addLabel('search', __('Search'))->description(__('Activity name.'));
                $row->addTextField('search')->setValue($search)->maxLength(20);

            $row = $form->addRow();
                $row->addSearchSubmit($session, __('Clear Search'));

            echo $form->getOutput();

            echo '<h2>';
            echo __('Activities');
            echo '</h2>';

            //Set special where params for different roles and permissions
            $continue = true;
            $and = '';
            $gibbonYearGroupID = null;

            if ($roleCategory == 'Student' and $highestAction == 'View Activities_studentRegister') {
                $continue = false;

                    $dataStudent = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                    $sqlStudent = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID';
                    $resultStudent = $connection2->prepare($sqlStudent);
                    $resultStudent->execute($dataStudent);

                if ($resultStudent->rowCount() == 1) {
                    $rowStudent = $resultStudent->fetch();
                    $gibbonYearGroupID = $rowStudent['gibbonYearGroupID'];
                    if ($gibbonYearGroupID != '') {
                        $continue = true;
                        $and = " AND gibbonYearGroupIDList LIKE '%$gibbonYearGroupID%'";
                    }
                }
            }
            if ($roleCategory == 'Parent' and $highestAction == 'View Activities_studentRegisterByParent' and $countChild > 0) {
                $continue = false;

                //Confirm access to this student
                $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $session->get('gibbonPersonID'), 'date' => date('Y-m-d'));
                $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL  OR dateEnd>=:date) AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                $resultChild = $connection2->prepare($sqlChild);
                $resultChild->execute($dataChild);
                if ($resultChild->rowCount() == 1) {

                    $dataStudent = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                    $sqlStudent = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID';
                    $resultStudent = $connection2->prepare($sqlStudent);
                    $resultStudent->execute($dataStudent);

                    if ($resultStudent->rowCount() == 1) {
                        $rowStudent = $resultStudent->fetch();
                        $gibbonYearGroupID = $rowStudent['gibbonYearGroupID'];
                        if ($gibbonYearGroupID != '') {
                            $continue = true;
                            $and = " AND gibbonYearGroupIDList LIKE '%$gibbonYearGroupID%'";
                        }
                    }

                } else {
                    echo '<div class="message">';
                    echo __('Select a child in your family view their available activities.');
                    echo '</div>';
                    $continue = true;
                }
            }

            if ($continue == false) {
                echo $page->getBlankSlate();
            } else {
                //Should we show date as term or date?
                $dateType = $settingGateway->getSettingByScope('Activities', 'dateType');
                if ($dateType == 'Term') {
                    $maxPerTerm = $settingGateway->getSettingByScope('Activities', 'maxPerTerm');
                } else {
                    $dateType = 'Date';
                }

                /**
                 * @var SchoolYearTermGateway
                 */
                $schoolYearTermGateway = $container->get(SchoolYearTermGateway::class);
                $schoolTerms = $schoolYearTermGateway->selectTermsBySchoolYear((int) $session->get('gibbonSchoolYearID'))->fetchKeyPair();
                $yearGroups = getYearGroups($connection2);

                // Toggle Features
                $canAccessRegistration = !empty($gibbonPersonID) && (($roleCategory == 'Student' && $highestAction == 'View Activities_studentRegister') || ($roleCategory == 'Parent' && $highestAction == 'View Activities_studentRegisterByParent' && $countChild > 0));
                $paymentOn = $settingGateway->getSettingByScope('Activities', 'payment') != 'None' && $settingGateway->getSettingByScope('Activities', 'payment') != 'Single';

                $activityGateway = $container->get(ActivityGateway::class);

                // CRITERIA
                $criteria = $activityGateway->newQueryCriteria()
                    ->searchBy($activityGateway->getSearchableColumns(), $search)
                    ->sortBy($dateType != 'Date' ? ['registrationOrder', 'gibbonSchoolYearTermIDList'] : ['registrationOrder', 'gibbonActivity.type'] )
                    ->sortBy('gibbonActivity.name')
                    ->pageSize(50)
                    ->fromArray($_POST);

                $activities = $activityGateway->queryActivitiesBySchoolYear($criteria, $session->get('gibbonSchoolYearID'), $dateType, $gibbonYearGroupID);

                // Registration Limit Check
                if ($allActivityAccess == 'Register' && $canAccessRegistration && $activities->count() > 0) {
                    if ($dateType == 'Term' and $maxPerTerm > 0) {
                        echo "<div class='warning'>";
                        echo __("Remember, each student can register for no more than $maxPerTerm activities per term. Your current registration count by term is:");
                        $terms = getTerms($connection2, $session->get('gibbonSchoolYearID'));
                        echo '<ul>';
                        for ($i = 0; $i < count($terms); $i = $i + 2) {
                            echo '<li>';
                            echo '<b>'.$terms[($i + 1)].':</b> ';


                                $dataActivityCount = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearTermIDList' => '%'.$terms[$i].'%');
                                $sqlActivityCount = "SELECT * FROM gibbonActivityStudent JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearTermIDList LIKE :gibbonSchoolYearTermIDList AND NOT status='Not Accepted'";
                                $resultActivityCount = $connection2->prepare($sqlActivityCount);
                                $resultActivityCount->execute($dataActivityCount);

                            if ($resultActivityCount->rowCount() >= 0) {
                                echo $resultActivityCount->rowCount().' activities';
                            }
                            echo '</li>';
                        }
                        echo '</ul>';
                        echo '</div>';
                    } else if ($dateType == 'Date') {
                        $sql = "SELECT gibbonActivityTypeID, name, maxPerStudent FROM gibbonActivityType WHERE access='Register' AND maxPerStudent > 0";
                        $activitiesWithLimits = $pdo->select($sql);

                        if ($activitiesWithLimits->rowCount() > 0) {
                            while ($activity = $activitiesWithLimits->fetch()) {
                                $activityCountByType = $activityGateway->getStudentActivityCountByType($activity['name'], $gibbonPersonID);
                                $activityCountRemaining = max(0, $activity['maxPerStudent'] - $activityCountByType);

                                if ($activityCountRemaining > 0) {
                                    echo '<div class="warning">';
                                        echo '<strong>'.$activity['name'].' '.__('Registration Available').':</strong> ';
                                        echo sprintf(__('Each student can register for %1$s %2$s activities.'), $activity['maxPerStudent'], $activity['name']).'<br/>&nbsp;<br/>';
                                        echo sprintf(__('Your current registration count is: %1$s'), $activityCountByType).'<br/>&nbsp;<br/>';
                                        echo '<span style="font-weight: bold; color: #444;">'.sprintf(__('You can register for %1$s more %2$s activities.'), $activityCountRemaining, $activity['name']).'</span>';
                                    echo '</div>';
                                } else if ($activityCountByType > 0) {
                                    echo '<div class="success">';
                                        echo '<strong>'.$activity['name'].' '.__('Registration Complete').':</strong> ';
                                        echo sprintf(__('You have registered for %1$s %2$s activities.'), $activityCountByType, $activity['name']);
                                    echo '</div>';
                                }
                            }
                        }
                    }
                }

                // DATA TABLE
                $table = DataTable::createPaginated('viewActivities', $criteria);

                // Add enrolment details & row highlights only when viewing registerable activities
                if ($canAccessRegistration && !empty($gibbonPersonID)) {
                    $enroledActivities = $activityGateway->selectActivityEnrolmentByStudent($session->get('gibbonSchoolYearID'), $gibbonPersonID)->fetchGroupedUnique();

                    $activities->transform(function (&$activity) use ($enroledActivities) {
                        $activity['enrolmentFull'] = $activity['waitingList'] != 'Y' && $activity['enrolment'] >= $activity['maxParticipants'];

                        if (isset($enroledActivities[$activity['gibbonActivityID']])) {
                            $activity['currentEnrolment'] = $enroledActivities[$activity['gibbonActivityID']];
                        }
                    });

                    $table->modifyRows(function ($activity, $row)  {
                        if (!empty($activity['currentEnrolment']) && $activity['currentEnrolment']['status'] != 'Waiting List') $row->addClass('current');
                        else if (!empty($activity['currentEnrolment']) && $activity['currentEnrolment']['status'] == 'Waiting List') $row->addClass('warning');
                        else if ($activity['registration'] != 'Y') $row->addClass('dull');
                        else if ($activity['enrolmentFull']) $row->addClass('error');

                        return $row;
                    });
                }

                $table->addColumn('name', __('Activity'))
                    ->context('primary')
                    ->format(function ($activity) {
                        return $activity['name'].'<br/><span class="small emphasis">'.$activity['type'].'</span>';
                    });

                $table->addColumn('provider', __('Provider'))
                    ->context('secondary')
                    ->width('10%')
                    ->format(function ($activity) use ($session) {
                        return ($activity['provider'] == 'School')? $session->get('organisationNameShort') : __('External');
                    });

                $table->addColumn('date', $dateType != 'Date'? __('Term') : __('Dates'))
                    ->description(__('Days'))
                    ->context('secondary')
                    ->width('18%')
                    ->sortable($dateType != 'Date' ? ['gibbonSchoolYearTermIDList'] : ['programStart', 'programEnd'])
                    ->format(function ($activity) use ($dateType, $schoolTerms, $activityGateway) {
                        if (empty($schoolTerms)) return '';

                        $output = '';
                        if ($dateType != 'Date') {
                            $termList = array_intersect_key($schoolTerms, array_flip(explode(',', $activity['gibbonSchoolYearTermIDList'] ?? '')));
                            if (!empty($termList)) {
                                return implode('<br/>', $termList);
                            }
                        } else {
                            $output .= Format::dateRangeReadable($activity['programStart'], $activity['programEnd']);
                        }

                        $output .= '<br/><span class="small emphasis">';
                        $output .= implode(', ', $activityGateway->selectWeekdayNamesByActivity($activity['gibbonActivityID'])->fetchAll(\PDO::FETCH_COLUMN));
                        $output .= '</span>';

                        return $output;
                    });


                $table->addColumn('yearGroups', __('Years'))
                    ->width('15%')
                    ->format(function ($activity) use ($yearGroups) {
                        return ($activity['yearGroupCount'] >= count($yearGroups)/2)? '<i>'.__('All').'</i>' : $activity['yearGroups'];
                    });

                if ($paymentOn) {
                    $table->addColumn('payment', __('Cost'))
                        ->width('15%')
                        ->description($session->get('currency'))
                        ->format(function ($activity) {
                            $payment = ($activity['payment'] > 0)
                                ? Format::currency($activity['payment']) . '<br/>' . __($activity['paymentType'])
                                : '<i>'.__('None').'</i>';
                            if ($activity['paymentFirmness'] != 'Finalised') $payment .= '<br/><i>'.__($activity['paymentFirmness']).'</i>';

                            return $payment;
                        });
                }

                if ($canAccessRegistration) {
                    $table->addColumn('enrolmentAvailable', __('Enrolment'))
                        ->context('primary')
                        ->sortable(false)
                        ->format(function ($activity) use ($disableExternalProviderSignup) {
                            if ($activity['provider'] == 'External' and $disableExternalProviderSignup == 'Y') {
                                return '<i>'.__('See activity details').'</i>';
                            } else if (!empty($activity['currentEnrolment'])) {
                                return $activity['currentEnrolment']['status'];
                            } elseif ($activity['registration'] == 'N') {
                                return __('Closed');
                            } else if ($activity['enrolmentFull']) {
                                return __('Full');
                            }
                        });
                }

                // ACTIONS
                $table->addActionColumn()
                    ->addParam('gibbonActivityID')
                    ->addParam('search', $criteria->getSearchText(true))
                    ->format(function ($activity, $actions) use ($activityGateway, $gibbonPersonID, $allActivityAccess, $canAccessRegistration, $disableExternalProviderSignup) {
                        $actions->addAction('view', __('View Details'))
                            ->isModal(1000, 550)
                            ->setURL('/modules/Activities/activities_view_full.php');

                        $signup = true;
                        if ($allActivityAccess == 'View' || $activity['access'] == 'View') {
                            $signup = false;
                        }
                        if ($activity['registration'] == 'N') {
                            $signup = false;
                        }
                        if ($activity['provider'] == 'External' and $disableExternalProviderSignup == 'Y') {
                            $signup = false;
                        }

                        if (!$canAccessRegistration || !$signup) return;

                        if (isset($activity['currentEnrolment'])) {
                            $actions->addAction('unregister', __('Unregister'))
                                ->addParam('mode', 'unregister')
                                ->addParam('gibbonPersonID', $gibbonPersonID)
                                ->setURL('/modules/Activities/activities_view_register.php')
                                ->setIcon('garbage');
                        } else {
                            $activityCountByType = $activityGateway->getStudentActivityCountByType($activity['type'], $gibbonPersonID);

                            if (!$activity['enrolmentFull'] && ($activity['maxPerStudent'] == 0 || $activityCountByType < $activity['maxPerStudent'])) {
                                $actions->addAction('enrolment', __('Register'))
                                    ->addParam('mode', 'register')
                                    ->addParam('gibbonPersonID', $gibbonPersonID)
                                    ->setURL('/modules/Activities/activities_view_register.php')
                                    ->setIcon('attendance');
                            }
                        }
                    });

                echo $table->render($activities);
            }
        }
    }
}
