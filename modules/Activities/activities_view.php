<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Activities\ActivityGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $page->breadcrumbs->add(__('View Activities'));          

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, array('success0' => 'Registration was successful.', 'success1' => 'Unregistration was successful.', 'success2' => 'Registration was successful, but the activity is full, so you are on the waiting list.'));
        }

        //Get current role category
        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);

        //Check access controls
        $allActivityAccess = getSettingByScope($connection2, 'Activities', 'access');
        $hideExternalProviderCost = getSettingByScope($connection2, 'Activities', 'hideExternalProviderCost');

        if (!($allActivityAccess == 'View' or $allActivityAccess == 'Register')) {
            echo "<div class='error'>";
            echo __($guid, 'Activity listing is currently closed.');
            echo '</div>';
        } else {
            if ($allActivityAccess == 'View') {
                echo "<div class='warning'>";
                echo __($guid, 'Registration is currently closed, but you can still view activities.');
                echo '</div>';
            }

            $disableExternalProviderSignup = getSettingByScope($connection2, 'Activities', 'disableExternalProviderSignup');
            if ($disableExternalProviderSignup == 'Y') {
                echo "<div class='warning'>";
                echo __($guid, 'Registration for activities offered by outside providers is disabled. Check activity details for instructions on how to register for such acitvities.');
                echo '</div>';
            }

            $gibbonPersonID = null;

            //If student, set gibbonPersonID to self
            if ($roleCategory == 'Student' and $highestAction == 'View Activities_studentRegister') {
                $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
            }
            //IF PARENT, SET UP LIST OF CHILDREN
            $countChild = 0;
            if ($roleCategory == 'Parent' and $highestAction == 'View Activities_studentRegisterByParent') {
                
                if (isset($_GET['gibbonPersonID'])) {
                    $gibbonPersonID = $_GET['gibbonPersonID'];
                }
                try {
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'Access denied.');
                    echo '</div>';
                } else {
                    $options = array();
                    while ($row = $result->fetch()) {
                        try {
                            $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => date('Y-m-d'));
                            $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateEnd IS NULL OR dateEnd>=:date) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                            $resultChild = $connection2->prepare($sqlChild);
                            $resultChild->execute($dataChild);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultChild->rowCount() > 0) {
                            while ($rowChild = $resultChild->fetch()) {
                                $options[$rowChild['gibbonPersonID']] = formatName('', $rowChild['preferredName'], $rowChild['surname'], 'Student', true);
                                ++$countChild;
                            }

                            if ($resultChild->rowCount() == 1) {
                                $gibbonPersonID = key($options);
                            }
                        }
                    }

                    if ($countChild == 0) {
                        echo "<div class='error'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    }
                }
            }

            echo '<h2>';
            echo __($guid, 'Filter & Search');
            echo '</h2>';

            $search = isset($_GET['search'])? $_GET['search'] : null;

            $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php','get');
            $form->setClass('noIntBorder fullWidth');

            $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/activities_view.php");

            if ($countChild > 0 and $roleCategory == 'Parent' and $highestAction == 'View Activities_studentRegisterByParent') {
                $row = $form->addRow();
                    $row->addLabel('gibbonPersonID', __('Child'))->description('Choose the child you are registering for.');
                    $row->addSelect('gibbonPersonID')->fromArray($options)->selected($gibbonPersonID)->placeholder(($countChild > 1)? '' : null);
            }

            $row = $form->addRow();
                $row->addLabel('search', __('Search'))->description('Activity name.');
                $row->addTextField('search')->setValue($search)->maxLength(20);

            $row = $form->addRow();
                $row->addSearchSubmit($gibbon->session, __('Clear Search'));

            echo $form->getOutput();

            echo '<h2>';
            echo __($guid, 'Activities');
            echo '</h2>';

            //Set pagination variable
            $page = 1;
            if (isset($_GET['page'])) {
                $page = $_GET['page'];
            }
            if ((!is_numeric($page)) or $page < 1) {
                $page = 1;
            }

            $today = date('Y-m-d');

            //Set special where params for different roles and permissions
            $continue = true;
            $and = '';
            $gibbonYearGroupID = null;

            if ($roleCategory == 'Student' and $highestAction == 'View Activities_studentRegister') {
                $continue = false;
                try {
                    $dataStudent = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sqlStudent = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID';
                    $resultStudent = $connection2->prepare($sqlStudent);
                    $resultStudent->execute($dataStudent);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

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
                if (!empty($gibbonPersonID)) {
                    try {
                        $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID'], 'date' => date('Y-m-d'));
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateEnd IS NULL  OR dateEnd>=:date) AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultChild->rowCount() == 1) {
                        try {
                            $dataStudent = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                            $sqlStudent = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID';
                            $resultStudent = $connection2->prepare($sqlStudent);
                            $resultStudent->execute($dataStudent);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($resultStudent->rowCount() == 1) {
                            $rowStudent = $resultStudent->fetch();
                            $gibbonYearGroupID = $rowStudent['gibbonYearGroupID'];
                            if ($gibbonYearGroupID != '') {
                                $continue = true;
                                $and = " AND gibbonYearGroupIDList LIKE '%$gibbonYearGroupID%'";
                            }
                        }
                    }
                } else {
                    echo '<div class="message" style="font-size:14px;padding: 16px;">';
                    echo __('Select a child in your family view their available activities.');
                    echo '</div>';
                    return;
                }
            }

            if ($continue == false) {
                echo "<div class='error'>";
                echo __('There are no records to display.');
                echo '</div>';
            } else {
                //Should we show date as term or date?
                $dateType = getSettingByScope($connection2, 'Activities', 'dateType');
                if ($dateType == 'Term') {
                    $maxPerTerm = getSettingByScope($connection2, 'Activities', 'maxPerTerm');
                } else {
                    $dateType = 'Date';
                }

                $schoolTerms = getTerms($connection2, $_SESSION[$guid]['gibbonSchoolYearID']);
                $yearGroups = getYearGroups($connection2);

                // Toggle Features
                $canAccessRegistration = !empty($gibbonPersonID) && (($roleCategory == 'Student' && $highestAction == 'View Activities_studentRegister') || ($roleCategory == 'Parent' && $highestAction == 'View Activities_studentRegisterByParent' && $countChild > 0));
                $paymentOn = getSettingByScope($connection2, 'Activities', 'payment') != 'None' && getSettingByScope($connection2, 'Activities', 'payment') != 'Single';

                // Registration Limit Check
                if ($allActivityAccess == 'Register' && $canAccessRegistration) {
                    if ($dateType == 'Term' and $maxPerTerm > 0) {
                        echo "<div class='warning'>";
                        echo __($guid, "Remember, each student can register for no more than $maxPerTerm activities per term. Your current registration count by term is:");
                        $terms = getTerms($connection2, $_SESSION[$guid]['gibbonSchoolYearID']);
                        echo '<ul>';
                        for ($i = 0; $i < count($terms); $i = $i + 2) {
                            echo '<li>';
                            echo '<b>'.$terms[($i + 1)].':</b> ';

                            try {
                                $dataActivityCount = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearTermIDList' => '%'.$terms[$i].'%');
                                $sqlActivityCount = "SELECT * FROM gibbonActivityStudent JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearTermIDList LIKE :gibbonSchoolYearTermIDList AND NOT status='Not Accepted'";
                                $resultActivityCount = $connection2->prepare($sqlActivityCount);
                                $resultActivityCount->execute($dataActivityCount);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

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
                                $activityCountByType = getStudentActivityCountByType($pdo, $activity['name'], $gibbonPersonID);
                                $activityCountRemaining = max(0, $activity['maxPerStudent'] - $activityCountByType);

                                if ($activityCountRemaining > 0) { 
                                    echo '<div class="warning" style="font-size:14px;padding: 16px;">';
                                    echo '<strong>'.$activity['name'].' '.__('Registration Available').':</strong> ';
                                    echo sprintf(__('Each student can register for %1$s %2$s activities.'), $activity['maxPerStudent'], $activity['name']).'<br/>&nbsp;<br/>';
                                    echo sprintf(__('Your current registration count is: %1$s'), $activityCountByType).'<br/>&nbsp;<br/>';
                                    echo '<span style="font-weight: bold; color: #444;">'.sprintf(__('You can register for %1$s more %2$s activities.'), $activityCountRemaining, $activity['name']).'</span>';
                                    echo '</div>';
                                } else if ($activityCountByType > 0) {
                                    echo '<div class="success" style="font-size:14px;padding: 16px;">';
                                    echo '<strong>'.$activity['name'].' '.__('Registration Complete').':</strong> ';
                                    echo sprintf(__('You have registered for %1$s %2$s activities.'), $activityCountByType, $activity['name']);
                                    echo '</div>';
                                }
                            }
                        }
                    }
                }

                $activityGateway = $container->get(ActivityGateway::class);
    
                // CRITERIA
                $criteria = $activityGateway->newQueryCriteria()
                    ->searchBy($activityGateway->getSearchableColumns(), $search)
                    ->sortBy($dateType != 'Date' ? ['registrationOrder', 'gibbonSchoolYearTermIDList'] : ['registrationOrder', 'gibbonActivity.type'] )
                    ->sortBy('gibbonActivity.name')
                    ->pageSize(50)
                    ->fromArray($_POST);

                $activities = $activityGateway->queryActivitiesBySchoolYear($criteria, $_SESSION[$guid]['gibbonSchoolYearID'], $dateType, $gibbonYearGroupID);

                // DATA TABLE
                $table = DataTable::createPaginated('viewActivities', $criteria);

                // Add enrolment details & row highlights only when viewing registerable activities
                if ($canAccessRegistration && !empty($gibbonPersonID)) {
                    $enroledActivities = $activityGateway->selectActivityEnrolmentByStudent($_SESSION[$guid]['gibbonSchoolYearID'], $gibbonPersonID)->fetchGroupedUnique();

                    $activities->transform(function(&$activity) use ($enroledActivities) {
                        $activity['enrolmentFull'] = $activity['waitingList'] != 'Y' && $activity['enrolment'] >= $activity['maxParticipants'];
    
                        if (isset($enroledActivities[$activity['gibbonActivityID']])) {
                            $activity['currentEnrolment'] = $enroledActivities[$activity['gibbonActivityID']];
                        }
                    });

                    $table->modifyRows(function ($activity, $row)  {
                        if (!empty($activity['currentEnrolment'])) $row->addClass('current');
                        else if ($activity['registration'] != 'Y') $row->addClass('dull');
                        else if ($activity['enrolmentFull']) $row->addClass('error');
    
                        return $row;
                    });
                }

                $table->addColumn('name', __('Activity'))
                    ->format(function($activity) {
                        return $activity['name'].'<br/><span class="small emphasis">'.$activity['type'].'</span>';
                    });

                $table->addColumn('provider', __('Provider'))
                    ->width('10%')
                    ->format(function($activity) use ($guid) {
                        return ($activity['provider'] == 'School')? $_SESSION[$guid]['organisationNameShort'] : __('External');
                    });

                $table->addColumn('date', $dateType != 'Date'? __('Term') : __('Dates'))
                    ->width('18%')
                    ->description(__('Days'))
                    ->sortable($dateType != 'Date' ? ['gibbonSchoolYearTermIDList'] : ['programStart', 'programEnd'])
                    ->format(function($activity) use ($dateType, $schoolTerms, $activityGateway) {
                        if (empty($schoolTerms)) return '';

                        $output = '';
                        if ($dateType != 'Date') {
                            $dateRange = '';
                            if (!empty(array_intersect($schoolTerms, explode(',', $activity['gibbonSchoolYearTermIDList'])))) {
                                $termList = array_map(function ($item) use ($schoolTerms) {
                                    $index = array_search($item, $schoolTerms);
                                    return ($index !== false && isset($schoolTerms[$index+1]))? $schoolTerms[$index+1] : '';
                                }, explode(',', $activity['gibbonSchoolYearTermIDList']));
                                $output .= implode('<br/>', $termList);
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
                    ->format(function($activity) use ($yearGroups) {
                        return ($activity['yearGroupCount'] >= count($yearGroups)/2)? '<i>'.__('All').'</i>' : $activity['yearGroups'];
                    });

                if ($paymentOn) {
                    $table->addColumn('payment', __('Cost'))
                        ->width('15%')
                        ->description($_SESSION[$guid]['currency'])
                        ->format(function($activity) {
                            $payment = ($activity['payment'] > 0) 
                                ? Format::currency($activity['payment']) . '<br/>' . __($activity['paymentType'])
                                : '<i>'.__('None').'</i>';
                            if ($activity['paymentFirmness'] != 'Finalised') $payment .= '<br/><i>'.__($activity['paymentFirmness']).'</i>';
            
                            return $payment;
                        });
                }

                if ($canAccessRegistration) {
                    $table->addColumn('enrolmentAvailable', __('Enrolment'))
                        ->sortable(false)
                        ->format(function($activity) use ($disableExternalProviderSignup) {
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
                    ->format(function ($activity, $actions) use ($pdo, $gibbonPersonID, $allActivityAccess, $canAccessRegistration, $disableExternalProviderSignup) {
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
                            $activityCountByType = getStudentActivityCountByType($pdo, $activity['type'], $gibbonPersonID);

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
