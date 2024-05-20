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

use Gibbon\Domain\Departments\DepartmentGateway;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Domain\Markbook\MarkbookColumnGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Markbook\MarkbookView;
use Gibbon\Services\Format;

// Lock the file so other scripts cannot call it
if (MARKBOOK_VIEW_LOCK !== sha1( $highestAction . $session->get('gibbonPersonID') ) . date('zWy') ) return;

require_once __DIR__ . '/src/MarkbookView.php';
require_once __DIR__ . '/src/MarkbookColumn.php';


    //reset ordering
    if(isset($_GET['reset'], $_GET['gibbonCourseClassID']) && $_GET['reset']==1){
        $data = array('gibbonCourseClassID' => $_GET['gibbonCourseClassID']);
        $sql = 'SET @count:=0;UPDATE gibbonMarkbookColumn SET `sequenceNumber`=@count:=@count+1 WHERE `gibbonCourseClassID` = :gibbonCourseClassID order by gibbonMarkbookColumnID ASC';
        $result = $pdo->executeQuery($data, $sql);
        $_GET['return'] = 'success0';
    } elseif(isset($_GET['reset'], $_GET['gibbonCourseClassID']) && $_GET['reset']==2){
        $data = array('gibbonCourseClassID' => $_GET['gibbonCourseClassID']);
        $sql = 'SET @count:=0;UPDATE gibbonMarkbookColumn SET `sequenceNumber`=@count:=@count+1 WHERE `gibbonCourseClassID` = :gibbonCourseClassID order by `date` ASC';
        $result = $pdo->executeQuery($data, $sql);
        $_GET['return'] = 'success0';
    }

   //Check for access to multiple column add
    $multiAdd = false;
    //Add multiple columns
    if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php')) {
        if ($highestAction2 == 'Edit Markbook_multipleClassesAcrossSchool' or $highestAction2 == 'Edit Markbook_multipleClassesInDepartment' or $highestAction2 == 'Edit Markbook_everything') {
            //Check highest role in any department
            $isCoordinator = isDepartmentCoordinator( $pdo, $session->get('gibbonPersonID') );

            if ($isCoordinator == true or $highestAction2 == 'Edit Markbook_multipleClassesAcrossSchool' or $highestAction2 == 'Edit Markbook_everything') {
                $multiAdd = true;
            }
        }
    }

    //Get class variable
    $gibbonCourseClassID = null;
    if (isset($_GET['gibbonCourseClassID'])) {
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    }

    if ($gibbonCourseClassID == '') {
    	$gibbonCourseClassID = $session->get('markbookClass') ?? '';
    }

    // Grab any taught class
    if ($gibbonCourseClassID == '') {
        $row = getAnyTaughtClass( $pdo, $session->get('gibbonPersonID'), $session->get('gibbonSchoolYearID') );
        $gibbonCourseClassID = (isset($row['gibbonCourseClassID']))? $row['gibbonCourseClassID'] : '';
    }

    if ($gibbonCourseClassID == '') {
        $page->breadcrumbs->add(__('View Markbook'));

        //Add multiple columns
        if ($multiAdd) {
            $params = [
                "gibbonCourseClassID" => $gibbonCourseClassID
            ];
            $page->navigator->addHeaderAction('addMulti', __('Add Multiple Columns'))
                ->setURL('/modules/Markbook/markbook_edit_addMulti.php')
                ->addParams($params)
                ->setIcon('page_new_multi')
                ->displayLabel();
        }
        //Get class chooser
        echo classChooser($guid, $pdo, $gibbonCourseClassID);
        return;
    }

    $session->set('markbookClass', $gibbonCourseClassID);

    //Check existence of and access to this class.
    $class = getClass($pdo, $session->get('gibbonPersonID'), $gibbonCourseClassID, $highestAction );

    if ($class == NULL) {
        $page->breadcrumbs->add(__('View Markbook'));

        //Get class chooser
        echo classChooser($guid, $pdo, $gibbonCourseClassID);

        if ($multiAdd == true) {
            $page->addError(__('The specified record does not exist.'));
        } else {
            $page->addError(__('Your request failed because you do not have access to this action.'));
        }

        return;
    }


    $courseName = $class['courseName'];
    $gibbonYearGroupIDList = $class['gibbonYearGroupIDList'];

    $page->breadcrumbs->add(empty($class)? __('View Markbook') : __('View {courseClass} Markbook', [
        'courseClass' => Format::courseClassName($class['course'], $class['class']),
    ]));

    //Add multiple columns
    if ($multiAdd) {
        $params = [
            "gibbonCourseClassID" => $gibbonCourseClassID
        ];
        $page->navigator->addHeaderAction('addMulti', __('Add Multiple Columns'))
            ->setURL('/modules/Markbook/markbook_edit_addMulti.php')
            ->addParams($params)
            ->setIcon('page_new_multi')
            ->displayLabel();
    }

    //Get class chooser
    echo classChooser($guid, $pdo, $gibbonCourseClassID);

    $departmentAccess = $container->get(DepartmentGateway::class)->selectMemberOfDepartmentByRole($class['gibbonDepartmentID'], $session->get('gibbonPersonID'), ['Coordinator', 'Teacher (Curriculum)'])->fetch();

    //Get teacher list
    $teacherList = getTeacherList( $pdo, $gibbonCourseClassID );
	$canEditThisClass = (isset($teacherList[ $session->get('gibbonPersonID') ]) || $highestAction2 == 'Edit Markbook_everything' || ($highestAction2 == 'Edit Markbook_multipleClassesInDepartment' && !empty($departmentAccess)));

    // Get criteria filter values, including session defaults
    $search = $_GET['search'] ?? '';
    $gibbonSchoolYearTermID = $_GET['gibbonSchoolYearTermID'] ?? $session->get('markbookTerm') ?? '';
    $columnFilter = $_GET['markbookFilter'] ?? $session->get('markbookFilter') ?? '';
    $studentOrderBy = $_GET['markbookOrderBy'] ?? $session->get('markbookOrderBy') ?? 'surname';

    //Get the current page number
    $pageNum = $_GET['page'] ?? $session->get('markbookPage') ?? 0;
    $session->set('markbookPage', $pageNum);

    $markbookGateway = $container->get(MarkbookColumnGateway::class);
    $plannerGateway = $container->get(PlannerEntryGateway::class);

    // Build the markbook object for this class
    $markbook = new MarkbookView($gibbon, $pdo, $gibbonCourseClassID, $container->get(SettingGateway::class));

    // QUERY
    $criteria = $markbookGateway->newQueryCriteria(true)
        ->searchBy($markbookGateway->getSearchableColumns(), $search)
        ->sortBy(['gibbonMarkbookColumn.sequenceNumber', 'gibbonMarkbookColumn.date', 'gibbonMarkbookColumn.complete', 'gibbonMarkbookColumn.completeDate'])
        ->filterBy('term', $gibbonSchoolYearTermID)
        ->filterBy('show', $columnFilter)
        ->pageSize($markbook->getColumnsPerPage())
        ->page($pageNum+1)
        ->fromPOST();

    $columns = $markbookGateway->queryMarkbookColumnsByClass($criteria, $gibbonCourseClassID);
    $columns->transform(function (&$column) use ($plannerGateway) {
        if (isset($column['gibbonPlannerEntryID'])) {
            $column['gibbonPlannerEntry'] = $plannerGateway->getPlannerEntryByID($column['gibbonPlannerEntryID']);
        }
    });

    // Load the columns for the current page
    $markbook->loadColumnsFromDataSet($columns);

    if ($markbook == NULL || $markbook->getColumnCountTotal() < 1) {
        echo "<div class='linkTop'>";
        if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php') and $canEditThisClass) {
            echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/markbook_edit_add.php&gibbonCourseClassID=$gibbonCourseClassID'>".__('Add')."<img title='".__('Add')."' src='./themes/".$session->get('gibbonThemeName')."/img/page_new.png'/></a>";
			if ($markbook->getSetting('enableColumnWeighting') == 'Y') {
	            if (isActionAccessible($guid, $connection2, '/modules/Markbook/weighting_manage.php') == true) {
	                echo " | <a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/weighting_manage.php&gibbonCourseClassID=$gibbonCourseClassID'>".__('Weightings')."<img title='".__('Weightings')."' src='./themes/".$session->get('gibbonThemeName')."/img/run.png'/></a>";
	            }
	        }
		}
        echo '</div>';

        echo "<div class='warning'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        // Cache all personalized target data
        $markbook->cachePersonalizedTargets( $gibbonCourseClassID );

        // Cache all weighting data for efficient use below
        if ($markbook->getSetting('enableColumnWeighting') == 'Y') {
            $markbook->cacheWeightings( );
        }

        // Work out details for external assessment display
        // TODO: Test this more?
        if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_details.php')) {
            $markbook->cacheExternalAssessments( $courseName, $gibbonYearGroupIDList );
        }

        echo '<h3>';
        echo __('Results');
        echo '</h3>';

        // Print table header info
        echo '<p>';
            if (!empty($teacherList)) {
                echo '<b>'.sprintf(__('Class taught by %1$s'), implode(', ', $teacherList) ).'</b>. ';
            }
            if ($markbook->getColumnCountTotal() > $markbook->getColumnsPerPage()) {
                echo __('To see more detail on an item (such as a comment or a grade), hover your mouse over it. To see more columns, use the Newer and Older links.');
            } else {
                echo __('To see more detail on an item (such as a comment or a grade), hover your mouse over it.');
            }
            
            if ($markbook->hasExternalAssessments() == true) {
                echo ' '.__('The Baseline column is populated based on student performance in external assessments, and can be used as a reference point for the grades in the markbook.');
            }
        echo '</p>';

        // Display Pagination
        echo "<div class='linkTop'>";
        if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php') ) {

        	echo "<div style='padding-top: 16px; margin-right: 10px; text-align: left; width: 300px; float: left;'>";

	        	echo ( ($session->get('markbookTerm') == -1)? __("All Terms") : $session->get('markbookTermName') ) ." : ";

                $start = min( max(0, $pageNum * $markbook->getColumnsPerPage())+1, $markbook->getColumnCountTotal() );
                $end = min( max( 1, $markbook->getColumnCountThisPage() + ($pageNum * $markbook->getColumnsPerPage()) ), $markbook->getColumnCountTotal() );

	        	echo __("Records") ." ". $start ."-". $end ." ". __('of') ." ". $markbook->getColumnCountTotal() ;

	        	if ($markbook->getColumnCountTotal() > $markbook->getColumnCountThisPage()) {
	        		echo " : ";
		            if ($pageNum <= 0) {
		                echo __('Older');
		            } else {
		                echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/markbook_view.php&gibbonCourseClassID=$gibbonCourseClassID&page=".($pageNum - 1)."'>".__('Older').'</a>';
		            }
		            echo ' | ';
		            if ((($pageNum + 1) * $markbook->getColumnsPerPage() ) >= $markbook->getColumnCountTotal() ) {
		                echo __('Newer');
		            } else {
		                echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/markbook_view.php&gibbonCourseClassID=$gibbonCourseClassID&page=".($pageNum + 1)."'>".__('Newer').'</a>';
		            }
		        }
	        echo '</div>';
        }

        // Display the Top Links
        if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php') and $canEditThisClass) {
            echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/markbook_edit_add.php&gibbonCourseClassID=$gibbonCourseClassID'>".__('Add')."<img title='".__('Add')."' src='./themes/".$session->get('gibbonThemeName')."/img/page_new.png'/></a> | ";
			echo '<script>
					function resetOrder(){
					    $( "#dialog" ).dialog();
					}
					function resetOrderAction(order){
						if(order==1){
							window.location.href = window.location.href.substr(0,window.location.href.length-1) + "&gibbonCourseClassID='.$gibbonCourseClassID.'&reset=1";
						}else if(order==2){
							window.location.href = window.location.href.substr(0,window.location.href.length-1) + "&gibbonCourseClassID='.$gibbonCourseClassID.'&reset=2";
						}
					}
				</script>';
			echo '<div id="dialog" title="'.__('Reset Order').'" style="display:none;">
                      '.__('Are you sure you want to reset the ordering of all the columns in this class?').'<br>
                      <button onclick="resetOrderAction(1)" class="my-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">'.__('Reset by entry order').'</button><br>
                      <button onclick="resetOrderAction(2)" class="my-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">'.__('Reset by date').'</button>
                    </div>';
			echo "<a href='#' onclick='resetOrder()'>".__('Reset Order')."<img title='".__('Reset Order')."' src='./themes/".$session->get('gibbonThemeName')."/img/reincarnate.png'/></a> | ";
            echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/markbook_edit_targets.php&gibbonCourseClassID=$gibbonCourseClassID'>".__('Targets')."<img title='".__('Set Personalised Attainment Targets')."' src='./themes/".$session->get('gibbonThemeName')."/img/target.png'/></a> | ";
            if ($markbook->getSetting('enableColumnWeighting') == 'Y') {
                if (isActionAccessible($guid, $connection2, '/modules/Markbook/weighting_manage.php') == true) {
                    echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/weighting_manage.php&gibbonCourseClassID=$gibbonCourseClassID'>".__('Weightings')."<img title='".__('Weightings')."' src='./themes/".$session->get('gibbonThemeName')."/img/run.png'/></a> | ";
                }
            }
            echo "<a href='".$session->get('absoluteURL')."/modules/Markbook/markbook_viewExportAll.php?gibbonCourseClassID=$gibbonCourseClassID&return=markbook_view.php'>".__('Export to Excel')."<img title='".__('Export to Excel')."' src='./themes/".$session->get('gibbonThemeName')."/img/download.png'/></a>";

        } else {
            echo '<br clear="both"/>';
        }
        echo '</div>';

        // Check to see if we have no columns to display. This can happen if the page number is incorrect.
        // Do this here so users still have access to buttons.
        if ($markbook->getColumnCountThisPage() <= 0) {
            echo "<div class='warning'>";
            echo __('There are no records to display.');
            echo '</div>';
            return;
        }

        // Hook up the Ajax call to the dragtable event - done here to make use of PHP variables
        ?>
        <script type='text/javascript'>
            $(document).ready(function(){
                $("#myTable").on('dragtablestop', function( event ) {
                    $.ajax({
                        url: "<?php echo $session->get('absoluteURL') ?>/modules/Markbook/markbook_viewAjax.php",
                        data: { order: $(this).dragtable('order'), sequence: <?php echo $markbook->getMinimumSequenceNumber(); ?> },
                        method: "POST",
                    })
                    .done(function( data ) {
                        if (data != '') alert( data );
                    })
                    .fail(function() {
                        //alert( '<?php echo __('Error'); ?>'  );
                    });
                });
            });
        </script>
        <?php

        // Wrap the table and add top scroll bar
        echo '<div class="doublescroll-wrapper">';
        echo "<div class='doublescroll-top'><div class='doublescroll-top-tablewidth'></div></div>";
        echo "<div class='doublescroll-container'>";

        echo "<table id='myTable' class='mini markbook colorOddEven' cellspacing='0'>";
        echo "<thead>";
        echo "<tr class='head'>";
	        echo "<th class='notdraggable firstColumn dragtable-drag-boundary' data-header='student'>";
	            echo "<span>";
	            echo __('Student');
	            echo "</span>";
	        echo '</th>';

        //Show Baseline data header
		$markbook->hasExternalAssessments();
		$externalAssessmentFields=$markbook->getExternalAssessments();
        if ($markbook->hasExternalAssessments() == true) {
			echo "<th data-header='assessment' class='dataColumn notdraggable dragtable-drag-boundary'>";

			$title = __($externalAssessmentFields[2]).' ';
            $title .= __(substr($externalAssessmentFields[3], (strpos($externalAssessmentFields[3], '_') + 1))).' | ';
            $title .= __($externalAssessmentFields[1]);
			$title .= ' | '.$externalAssessmentFields[4].' '.__('Scale').' ';

            echo "<div class='verticalText' title='$title'>";
            echo __('Baseline').'<br/>';
            echo '</div>';
            echo '</th>';
        }

        //Show target grade header
        if ($markbook->hasPersonalizedTargets()) {
            echo "<th class='dataColumn studentTarget notdraggable dragtable-drag-boundary' data-header='target'>";
            $title = __('Personalised attainment target grade');

            //Get DAS
            $DAS = $markbook->getDefaultAssessmentScale();
			if (!empty($DAS)) {
                $title .= ' | '.$DAS['name'].' '.__('Scale').' ';
            }

            echo "<div class='verticalText' title='$title'>";
            echo __('Target').'<br/>';
            echo '</div>';
            echo '</th>';
        }

        $columnID = array();
        $attainmentID = array();
        $effortID = array();
        // Display headers for each of the markbook columns
        for ($i = 0; $i < $markbook->getColumnCountThisPage(); ++$i) {

            $column = $markbook->getColumn( $i );
            $columnType = $column->getData('type');
            $unit = getUnit($connection2, $column->getData('gibbonUnitID'), '', $column->getData('gibbonCourseClassID') );

            // Build a mini list for the hover-over info
            // TODO: Move this stuff into markbookColumn class
            $info = '<h6 style="color:#ffffff;">'.$column->getData('description').'</h6>';
            $info .= '<ul style="margin: 0;">';
            $info .= '<li>'.__('Type').' - '.$markbook->getTypeDescription( $columnType ) .'</li>';

            $weightInfo = '';
            $includeMarks = !empty($column->getData('completeDate'));

            if (isset($unit[0])) {
                $info .= '<li>'.__('Unit').' - '. $unit[0] .'</li>';
            }

            if ($markbook->getSetting('enableGroupByTerm') == 'Y' && $column->getData('date') != '') {
                $info .= '<li>'. __('Assigned on ').' '.Format::date($column->getData('date') ).'</li>';
            }

            if ($column->getData('completeDate') != '') {
                $info .= '<li>'. __('Marked on').' '.Format::date($column->getData('completeDate') ).'</li>';
            } else {
                $info .= '<li>'. __('Unmarked').'</li>';
                $weightInfo .= __('Unmarked').'<br/>';
                $includeMarks = false;
            }

            if ($markbook->getSetting('enableColumnWeighting') == 'Y' ) {
                $info .= '<li>'. __('Column Weighting').' '.floatval( $column->getData('attainmentWeighting') ).'</li>';

                if ($column->hasAttainmentWeighting() == false) {
                    $weightInfo .= __('Column Weighting').' '.floatval( $column->getData('attainmentWeighting') ).'<br/>';
                    $includeMarks = false;
                }
            }

            if ($markbook->getSetting('enableTypeWeighting') == 'Y' ) {
                $info .= '<li>'. __('Type Weighting').' '.floatval( $markbook->getWeightingByType($columnType) ).'</li>';

                if ( empty($markbook->getWeightingByType($columnType))) {
                    $weightInfo .= __('Type Weighting').' '.floatval( $markbook->getWeightingByType($columnType) ).'<br/>';
                    $includeMarks = false;
                }
            }


            if ($markbook->getReportableByType($columnType) == 'N'  ) {
                $weightInfo .= __('Reportable').'? '.$markbook->getReportableByType($columnType).'<br/>';
                $includeMarks = false;
            }

            $info .= '</ul>';

            echo "<th class='marksColumn notdraggable' data-header='".$column->gibbonMarkbookColumnID."' style='padding: 0px 0px 30px 0px !important; text-align: center;vertical-align: top;'>";

            echo ($canEditThisClass) ? "<div class='dragtable-drag-handle'></div>" :  "<br/>";

            echo "<span title='".htmlPrep( $info )."'>".$column->getData('name').'</span><br/>';
            echo "<span class='details'>";


            echo $markbook->getTypeDescription( $column->getData('type') );

            if ($column->hasAttachment( $session->get('absolutePath') )) {
                echo " | <a 'title='".__('Download more information')."' href='".$session->get('absoluteURL').'/'.$column->getData('attachment')."' target='_blank'>".__("More Info")."</a><br/>";
            } else {
                echo '<br/>';
            }

            echo (isset($unit[0]))? __('Unit').' - '. $unit[0].'<br/>' : '<br/>';


            echo '</span>';
            if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php') and $canEditThisClass) {
                echo '<div class="columnActions">';
                echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/markbook_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$column->gibbonMarkbookColumnID."'><img title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";

                echo "<a class='miniIcon' href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$column->gibbonMarkbookColumnID."'><img title='".__('Enter Data')."' src='./themes/".$session->get('gibbonThemeName')."/img/markbook.png'/> ";

                    // Add mini checkmarks if the column is marked and included in calculations
                    if ( $includeMarks ) {
                        $weightInfo = __('Marked on').' '.Format::date($column->getData('completeDate') ).'<br/>';
                        echo "<img title='$weightInfo' src='./themes/".$session->get('gibbonThemeName')."/img/iconTick_double.png'/>";
                    } else {
                        if ($markbook->getSetting('enableColumnWeighting') == 'Y' ) {
                            $weightInfo = '<strong>'.__('Excluded from averages').':</strong><br/>'. $weightInfo;
                        }
                        echo "<img title='$weightInfo' src='./themes/".$session->get('gibbonThemeName')."/img/iconCross.png'/>";
                    }

                echo "</a> ";
                echo "<a class='thickbox' href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/'.$session->get('module')."/markbook_edit_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$column->gibbonMarkbookColumnID."&width=650&height=135'><img title='".__('Delete')."' src='./themes/".$session->get('gibbonThemeName')."/img/garbage.png'/></a> ";
                echo "<a href='".$session->get('absoluteURL').'/modules/Markbook/markbook_viewExport.php?gibbonMarkbookColumnID='.$column->gibbonMarkbookColumnID."&gibbonCourseClassID=$gibbonCourseClassID&return=markbook_view.php'><img title='".__('Export to Excel')."' src='./themes/".$session->get('gibbonThemeName')."/img/download.png'/></a>";
                echo '</div>';
            }

            echo '<table class="columnLabels blank rounded-t-none" cellspacing=0><tr>';

            if ($column->gibbonMarkbookColumnID == false ) { //or $contents == false
            	echo '<th>';
            	echo '</th>';
            } else {
                if ($enableModifiedAssessment == 'Y') {
                    echo "<th class='columnLabel smallColumn'>";
                        echo __('Mod');
                    echo '</th>';
                }
                if ($column->displayAttainment() ) {

                    echo "<th class='columnLabel medColumn'>";

                    $scale = '';
                    if ($markbook->getSetting('enableRawAttainment') == 'Y' && $session->has('markbookFilter') ) {
                        if ($session->get('markbookFilter') == 'raw' && $column->displayRawMarks() and $column->hasAttainmentRawMax()) {
                            $scale = ' - ' . __('Raw Marks') .' '. __('out of') .': '. floatval($column->getData('attainmentRawMax') );
                        }
                    }

                    if (empty($scale)) {
                        
                            $dataScale = array('gibbonScaleID' => $column->getData('gibbonScaleIDAttainment'));
                            $sqlScale = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                            $resultScale = $connection2->prepare($sqlScale);
                            $resultScale->execute($dataScale);

                        if ($resultScale->rowCount() == 1) {
                            $rowScale = $resultScale->fetch();
                            $scale = ' - '.$rowScale['name'];
                            if ($rowScale['usage'] != '') {
                                $scale = $scale.': '.$rowScale['usage'];
                            }
                        }
                    }

                    echo "<span title='".$markbook->getSetting('attainmentName').htmlPrep($scale)."'>".$markbook->getSetting('attainmentAbrev').'</span>';
                    echo '</th>';
                }
                if ($column->displayEffort() ) {
                    echo "<th class='columnLabel medColumn'>";
                    
                        $dataScale = array('gibbonScaleID' => $column->getData('gibbonScaleIDEffort'));
                        $sqlScale = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                        $resultScale = $connection2->prepare($sqlScale);
                        $resultScale->execute($dataScale);
                    $scale = '';
                    if ($resultScale->rowCount() == 1) {
                        $rowScale = $resultScale->fetch();
                        $scale = ' - '.$rowScale['name'];
                        if ($rowScale['usage'] != '') {
                            $scale = $scale.': '.$rowScale['usage'];
                        }
                    }

                    echo "<span title='".$markbook->getSetting('effortName').htmlPrep($scale)."'>".$markbook->getSetting('effortAbrev').'</span>';
                    echo '</th>';
                }
                if ($column->displayComment()) {
                    echo "<th class='columnLabel largeColumn'>";
                    echo "<span title='".__('Comment')."'>".__('Com').'</span>';
                    echo '</th>';
                }
                if ($column->displayUploadedResponse()) {
                    echo "<th class='columnLabel smallColumn'>";
                    echo "<span title='".__('Uploaded Response')."'>".__('Upl').'</span>';
                    echo '</th>';
                }
                if ($column->displaySubmission()) {
                    echo "<th class='columnLabel smallColumn'>";
                    echo "<span title='".__('Submitted Work')."'>".__('Sub').'</span>';
                    echo '</th>';

                }
            }
            echo '</tr></table>';

            echo '</th>';
        }

        $title = sprintf(__('Weighted mean of all marked columns using Primary Assessment Scale for %1$s, if numeric'),
        $markbook->getSetting('attainmentName') );

        // Headers for the columns at the end of the markbook
        if ($markbook->getSetting('enableColumnWeighting') == 'Y' && $columnFilter != 'unmarked') {

            // Display headings for overall term and category averages
            if ($columnFilter == 'averages') {

                // Display all used column types
                if ($markbook->getSetting('enableTypeWeighting') == 'Y' ) {
                    if ( ($markbook->getSetting('enableGroupByTerm') == 'Y' && $gibbonSchoolYearTermID > 0) ||
                         ($markbook->getSetting('enableGroupByTerm') == 'N' && $gibbonSchoolYearTermID <= 0) ) {
                        foreach ($markbook->getGroupedMarkbookTypes('term') as $type) {
                            echo "<th class='dataColumn notdraggable dragtable-drag-boundary' data-header='$type'>";
                            echo '<div class="verticalText">' . $markbook->getTypeDescription($type) . '</div>';
                            echo '</th>';
                        }
                    }
                } else if (count($markbook->getGroupedMarkbookTypes('year')) > 0 && $gibbonSchoolYearTermID > 0) {
                    foreach ($markbook->getGroupedMarkbookTypes('year') as $type) {
                        echo "<th class='dataColumn notdraggable dragtable-drag-boundary' data-header='$type'>";
                        echo '<div class="verticalText">' . $markbook->getTypeDescription($type) . '</div>';
                        echo '</th>';
                    }
                }

                // Display all used terms
                if ( ($markbook->getSetting('enableGroupByTerm') == 'Y' && $gibbonSchoolYearTermID <= 0) ) {
                    foreach ($markbook->getCurrentTerms() as $term) {
                        echo "<th class='dataColumn notdraggable dragtable-drag-boundary' data-header='".$term['nameShort']."'>";
                        echo '<div class="verticalText">' . $term['name'] . '</div>';
                        echo '</th>';
                    }
                }
            }

            if ($markbook->getSetting('enableGroupByTerm') == 'Y' && $gibbonSchoolYearTermID > 0) {
                echo "<th class='dataColumn dataDivider notdraggable dragtable-drag-boundary' data-header='term'>";
                echo '<div class="verticalText">' . $session->get('markbookTermName') . '</div>';
                echo '</th>';
            }

            echo "<th class='dataColumn dataDivider notdraggable dragtable-drag-boundary' data-header='cumulative'>";
                echo "<div class='verticalText' title='$title'>";
                echo __('Cumulative');
                echo '</div>';
            echo '</th>';


            if ($markbook->getSetting('enableTypeWeighting') == 'Y' && count($markbook->getGroupedMarkbookTypes('year')) > 0 && $gibbonSchoolYearTermID <= 0) {

                if ($columnFilter == 'averages' && $gibbonSchoolYearTermID <= 0) {
                    foreach ($markbook->getGroupedMarkbookTypes('year') as $type) {
                        echo "<th class='dataColumn notdraggable dragtable-drag-boundary' data-header='$type'>";
                        echo '<div class="verticalText">' . $markbook->getTypeDescription($type) . '</div>';
                        echo '</th>';
                    }
                }

                echo "<th class='dataColumn notdraggable dragtable-drag-boundary' data-header='final'>";
                echo '<div class="verticalText">' .__('Final Grade') . '</div>';
                echo '</th>';
            }


        }

        echo '</tr>';
        echo "</thead>";

        // Start displaying the main table data - get the students in this course and begin looping over them
        echo "<tbody>";

        try {
            if ($studentOrderBy == 'rollOrder') {
                $dataStudents = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonSchoolYearID'=>$session->get('gibbonSchoolYearID') );
                $sqlStudents = "SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart, rollOrder FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY ISNULL(rollOrder), rollOrder, surname, preferredName";
            } else if ($studentOrderBy == 'preferredName') {
                $dataStudents = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sqlStudents = "SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY preferredName, surname";
            } else {
                $dataStudents = array('gibbonCourseClassID' => $gibbonCourseClassID);
                $sqlStudents = "SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
            }

            $resultStudents = $connection2->prepare($sqlStudents);
            $resultStudents->execute($dataStudents);
        } catch (PDOException $e) {
        }

        $count = 0;
        $totals = array();

        if ($resultStudents->rowCount() < 1) {
            echo '<tr>';
            echo '<td colspan='.($markbook->getColumnCountTotal() + 1).'>';
            echo '<i>'.__('There are no records to display.').'</i>';
            echo '</td>';
            echo '</tr>';
        } else {
            while ($rowStudents = $resultStudents->fetch()) {
                ++$count;

                echo "<tr >";
                echo '<td class="firstColumn '.($count % 2 == 0 ? 'odd' : 'even').'">';

                if ($studentOrderBy == 'rollOrder' && !empty($rowStudents['rollOrder']) ) {
                    echo $rowStudents['rollOrder'].') ';
                }

                echo "<a class='studentName' href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowStudents['gibbonPersonID'].'&subpage=Markbook#'.$gibbonCourseClassID."'>";

                $reverseName = ( $studentOrderBy == 'surname' or $studentOrderBy == 'rollOrder' or empty($studentOrderBy) );
                echo Format::name('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student', $reverseName);

                echo '</a>';
                echo '</td>';

                // Display baseline
                if ($markbook->hasExternalAssessments() == true) {
                    echo '<td class="dataColumn">';
                    
                        $dataEntry = array('gibbonPersonID' => $rowStudents['gibbonPersonID'], 'gibbonExternalAssessmentFieldID' => $externalAssessmentFields[0]);
                        $sqlEntry = "SELECT gibbonScaleGrade.value, gibbonScaleGrade.descriptor, gibbonExternalAssessmentStudent.date
							FROM gibbonExternalAssessmentStudentEntry
								JOIN gibbonExternalAssessmentStudent ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentStudentID=gibbonExternalAssessmentStudent.gibbonExternalAssessmentStudentID)
								JOIN gibbonScaleGrade ON (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID)
							WHERE gibbonPersonID=:gibbonPersonID
								AND gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID
								AND NOT gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID=''
							ORDER BY date DESC";
                        $resultEntry = $connection2->prepare($sqlEntry);
                        $resultEntry->execute($dataEntry);
                    if ($resultEntry->rowCount() >= 1) {
                        $rowEntry = $resultEntry->fetch();
                        echo "<a title='".__($rowEntry['descriptor']).' | '.__('Test taken on').' '.Format::date($rowEntry['date'])."' href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowStudents['gibbonPersonID']."&subpage=External Assessment'>".__($rowEntry['value']).'</a>';
                    }
                    echo '</td>';
                }

                // Display personalized target
                if ($markbook->hasPersonalizedTargets()) {
                    echo '<td class="dataColumn studentTarget">';
                        echo $markbook->getTargetForStudent( $rowStudents['gibbonPersonID'] );
                    echo '</td>';
                }

                // The main markbook loop - iterate over each student's markbook entry per column
                for ($i = 0; $i < $markbook->getColumnCountThisPage(); ++$i) {

                	$column = $markbook->getColumn( $i );

                	echo "<td class='columnLabel' style='padding: 0 !important;'>";
                	echo '<table class="columnLabels blank" cellspacing=0><tr>';


                    
                        $dataEntry = array('gibbonMarkbookColumnID' => $column->gibbonMarkbookColumnID, 'gibbonPersonIDStudent' => $rowStudents['gibbonPersonID']);
                        $sqlEntry = 'SELECT * FROM gibbonMarkbookEntry WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent LIMIT 1';
                        $resultEntry = $connection2->prepare($sqlEntry);
                        $resultEntry->execute($dataEntry);
                    if ($resultEntry->rowCount() == 1) {
                        $rowEntry = $resultEntry->fetch();

                        if ($enableModifiedAssessment == 'Y') {
                            echo "<td class='medColumn'>";
                                echo $rowEntry['modifiedAssessment'];
                            echo "</td>";
                        }
                        if ($column->displayAttainment()) {

                            echo "<td class='medColumn'>";

                            if ($column->hasAttainmentGrade()) {
                                $styleAttainment = getAlertStyle($alert, $rowEntry['attainmentConcern']);
                                $attainment = '';
                                $attainmentDesc = $rowEntry['attainmentDescriptor'];
                                if ($rowEntry['attainmentValue'] != '') {
                                    $attainment = __($rowEntry['attainmentValue']);
                                }
                                if ($rowEntry['attainmentValue'] == 'Complete') {
                                    $attainment = __('Com');
                                } elseif ($rowEntry['attainmentValue'] == 'Incomplete') {
                                    $attainment = __('Inc');
                                }

                                if ($markbook->getSetting('enableRawAttainment') == 'Y' && $column->displayRawMarks() && $column->hasAttainmentRawMax()) {

                                    if (isset($rowEntry['attainmentValueRaw']) && !empty($rowEntry['attainmentValueRaw'])) {
                                        if ($session->get('markbookFilter') == 'raw') {
                                            $attainment = $rowEntry['attainmentValueRaw'];
                                        } else {
                                            $attainmentDesc .= '<br/>'. $rowEntry['attainmentValueRaw'] . ' / ' . floatval($column->getData('attainmentRawMax'));
                                        }
                                    }
                                }


                                echo "<div $styleAttainment title='".htmlPrep($attainmentDesc)."'>" . $attainment;

                                if ($attainment !== '' &&  is_numeric(rtrim($attainment, "%"))) {
                                    @$totals['attainment'][$i]['total'] += floatval($attainment);
                                    @$totals['attainment'][$i]['count'] += 1;
                                }
                            }
                            if ($column->hasAttainmentRubric()) {
                                echo "<a class='thickbox rubricIcon' href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/'.$session->get('module').'/markbook_view_rubric.php&gibbonRubricID='.$column->getData('gibbonRubricIDAttainment')."&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$column->gibbonMarkbookColumnID.'&gibbonPersonID='.$rowStudents['gibbonPersonID']."&mark=FALSE&type=attainment&width=1100&height=550'><img title='".__('View Rubric')."' src='./themes/".$session->get('gibbonThemeName')."/img/rubric.png'/></a>";
                            }

                            if ($column->hasAttainmentGrade()) {

                                if (empty($attainment) && $column->hasAttainmentRubric() == false) {
                                    if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit.php") && $canEditThisClass) {
                                        print "<a class='markbookQuickEdit' href='" . $session->get("absoluteURL") . "/index.php?q=/modules/" . $session->get("module") . "/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=" . $column->gibbonMarkbookColumnID . "#".$rowStudents["gibbonPersonID"]."'><img style='margin-top: 3px' title='" . __("Edit") . "' src='./themes/" . $session->get("gibbonThemeName") . "/img/config.png' width='14' height='14'/></a> " ;
                                    }
                                }

                                echo '</div>';
                            }
                            echo '</td>';
                        }

                        if ($column->displayEffort()) {

                            echo "<td class='medColumn'>";
                            if ($column->hasEffortGrade()) {
                                $styleEffort = getAlertStyle($alert, $rowEntry['effortConcern']);
                                $effort = '';
                                if ($rowEntry['effortValue'] != '') {
                                    $effort = __($rowEntry['effortValue']);
                                }
                                if ($rowEntry['effortValue'] == 'Complete') {
                                    $effort = __('Com');
                                } elseif ($rowEntry['effortValue'] == 'Incomplete') {
                                    $effort = __('Inc');
                                }
                                echo "<div $styleEffort title='".htmlPrep($rowEntry['effortDescriptor'])."'>" . $effort;
                            }
                            if ($column->hasEffortRubric()) {
                                echo "<a class='thickbox rubricIcon' href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/'.$session->get('module').'/markbook_view_rubric.php&gibbonRubricID='.$column->getData('gibbonRubricIDEffort')."&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$column->gibbonMarkbookColumnID.'&gibbonPersonID='.$rowStudents['gibbonPersonID']."&mark=FALSE&type=effort&width=1100&height=550'><img title='".__('View Rubric')."' src='./themes/".$session->get('gibbonThemeName')."/img/rubric.png'/></a>";
                            }
                            if ($column->hasEffortGrade()) {

                                if (empty($effort) && $column->hasEffortRubric() == false) {

                                    if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit.php") && $canEditThisClass) {
                                        print "<a class='markbookQuickEdit' href='" . $session->get("absoluteURL") . "/index.php?q=/modules/" . $session->get("module") . "/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=" . $column->gibbonMarkbookColumnID . "#".$rowStudents["gibbonPersonID"]."'><img style='margin-top: 3px' title='" . __("Edit") . "' src='./themes/" . $session->get("gibbonThemeName") . "/img/config.png' width='14' height='14'/></a> " ;
                                    }
                                }

                                echo '</div>';
                            }
                            echo '</td>';
                        }
                        if ($column->displayComment()) {

                            echo "<td class='largeColumn'>";
                            $style = '';
                            if ($rowEntry['comment'] != '') {
                                if (mb_strlen($rowEntry['comment']) < 11) {
                                    echo htmlPrep($rowEntry['comment']);
                                } else {
                                    echo "<span $style title='".htmlPrep($rowEntry['comment'])."'>".mb_substr($rowEntry['comment'], 0, 10).'...</span>';
                                }
                            }
                            echo '</td>';
                        }
                        if ($column->displayUploadedResponse()) {

                            echo "<td class='smallColumn'>";
                            if ($rowEntry['response'] != '') {
                                echo "<a title='".__('Uploaded Response')."' href='".$session->get('absoluteURL').'/'.$rowEntry['response']."'>Up</a><br/>";
                            }
                        }
                        echo '</td>';
                    } else {
                        $editLink = '';
                        if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit.php") && $canEditThisClass) {
                            $editLink = "<a class='markbookQuickEdit' href='" . $session->get("absoluteURL") . "/index.php?q=/modules/" . $session->get("module") . "/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=" . $column->gibbonMarkbookColumnID . "#".$rowStudents["gibbonPersonID"]."'><img style='margin-top: 3px' title='" . __("Add") . "' src='./themes/" . $session->get("gibbonThemeName") . "/img/page_new_mini.png'/></a> " ;
                        }

                        if ($enableModifiedAssessment == 'Y') {
                            echo '<td class="medColumn">'.$editLink.'</td>';
                        }
                        if ($column->displayAttainment()) {
                            echo '<td class="medColumn">'.$editLink.'</td>';
                        }
                        if ($column->displayEffort()) {
                            echo '<td class="medColumn">'.$editLink.'</td>';
                        }
                        if ($column->displayComment()) {
                            echo '<td class="largeColumn">'.$editLink.'</td>';
                        }
                        if ($column->displayUploadedResponse()) {
                            echo '<td class="smallColumn"></td>';
                        }
                            
                    }

                    if ($column->displaySubmission()) {

                        echo "<td class='smallColumn'>";
                        
                            $dataWork = array('gibbonPlannerEntryID' => $column->getData('gibbonPlannerEntryID'), 'gibbonPersonID' => $rowStudents['gibbonPersonID']);
                            $sqlWork = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                            $resultWork = $connection2->prepare($sqlWork);
                            $resultWork->execute($dataWork);
                        if ($resultWork->rowCount() > 0) {
                            $rowWork = $resultWork->fetch();

                            if ($rowWork['status'] == 'Exemption') {
                                $linkText = __('Exe');
                            } elseif ($rowWork['version'] == 'Final') {
                                $linkText = __('Fin');
                            } else {
                                $linkText = __('Dra').$rowWork['count'];
                            }

                            $style = '';
                            $status = 'On Time';
                            if ($rowWork['status'] == 'Exemption') {
                                $status = __('Exemption');
                            } elseif ($rowWork['status'] == 'Late') {
                                $style = "style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'";
                                $status = __('Late');
                            }

                            if ($rowWork['type'] == 'File') {
                                echo "<span title='".$rowWork['version'].". $status. ".__('Submitted at').' '.substr($rowWork['timestamp'], 11, 5).' '.__('on').' '.Format::date(substr($rowWork['timestamp'], 0, 10))."' $style><a href='".$session->get('absoluteURL').'/'.$rowWork['location']."'>$linkText</a></span>";
                            } elseif ($rowWork['type'] == 'Link') {
                                echo "<span title='".$rowWork['version'].". $status. ".__('Submitted at').' '.substr($rowWork['timestamp'], 11, 5).' '.__('on').' '.Format::date(substr($rowWork['timestamp'], 0, 10))."' $style><a target='_blank' href='".$rowWork['location']."'>$linkText</a></span>";
                            } else {
                                echo "<span title='$status. ".__('Recorded at').' '.substr($rowWork['timestamp'], 11, 5).' '.__('on').' '.Format::date(substr($rowWork['timestamp'], 0, 10))."' $style>$linkText</span>";
                            }
                        } else {
                            if (date('Y-m-d H:i:s') < $column->getData('homeworkDueDateTime') ) {
                                echo "<span title='".__('Pending')."'>Pen</span>";
                            } else {
                                if ($rowStudents['dateStart'] > $column->getData('lessonDate') ) {
                                    echo "<span title='".__('Student joined school after assessment was given.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>".__('NA').'</span>';
                                } else {
                                    if ($column->getData('homeworkSubmissionRequired') == 'Required') {
                                        echo "<span title='".__('Incomplete')."' style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>".__('Inc').'</span>';
                                    } else {
                                        echo "<span title='".__('Not submitted online')."'>".__('NA').'</span>';
                                    }
                                }
                            }
                        }
                        echo '</td>';

                    }
                	echo '</tr></table>';
                }

                // These are the columns that show up at the end of the markbook, they must match their headers above the main loop
                // Calculate and output weighted average marks
                if ($markbook->getSetting('enableColumnWeighting') == 'Y' && $columnFilter != 'unmarked') {

                    // Display overall term and category averages
                    if ($columnFilter == 'averages') {

                        if ($markbook->getSetting('enableTypeWeighting') == 'Y' ) {
                            if ( ($markbook->getSetting('enableGroupByTerm') == 'Y' && $gibbonSchoolYearTermID > 0) ||
                                 ($markbook->getSetting('enableGroupByTerm') == 'N' && $gibbonSchoolYearTermID <= 0) ) {

                                // Display all used column types
                                foreach ($markbook->getGroupedMarkbookTypes('term') as $type) {
                                    echo '<td class="dataColumn">';
                                        echo $markbook->getFormattedAverage( $markbook->getTypeAverage($rowStudents['gibbonPersonID'], $gibbonSchoolYearTermID, $type) );
                                    echo '</td>';
                                    @$totals['typeAverage'][$type] += floatval($markbook->getTypeAverage($rowStudents['gibbonPersonID'], $gibbonSchoolYearTermID, $type));
                                }
                            }
                        } else if (count($markbook->getGroupedMarkbookTypes('year')) > 0 && $gibbonSchoolYearTermID > 0) {
                            foreach ($markbook->getGroupedMarkbookTypes('year') as $type) {
                                echo '<td class="dataColumn">';
                                    echo $markbook->getFormattedAverage( $markbook->getTypeAverage($rowStudents['gibbonPersonID'], $gibbonSchoolYearTermID, $type) );
                                echo '</td>';
                            }
                        }

                        if ( ($markbook->getSetting('enableGroupByTerm') == 'Y' && $gibbonSchoolYearTermID <= 0) ) {
                            foreach ($markbook->getCurrentTerms() as $term) {
                                echo '<td class="dataColumn">';
                                    echo $markbook->getFormattedAverage( $markbook->getTermAverage($rowStudents['gibbonPersonID'], $term['gibbonSchoolYearTermID']) );
                                echo '</td>';
                                @$totals['termAverage'][$term['gibbonSchoolYearTermID']] += floatval($markbook->getTermAverage($rowStudents['gibbonPersonID'], $term['gibbonSchoolYearTermID']));
                            }
                        }
                    }

                    if ($markbook->getSetting('enableGroupByTerm') == 'Y' && $gibbonSchoolYearTermID > 0) {
                        echo '<td class="dataColumn dataDivider">';
                        echo $markbook->getFormattedAverage( $markbook->getTermAverage($rowStudents['gibbonPersonID'], $gibbonSchoolYearTermID) );
                        echo '</td>';
                        @$totals['termAverage'][$gibbonSchoolYearTermID] += floatval($markbook->getTermAverage($rowStudents['gibbonPersonID'], $gibbonSchoolYearTermID));
                    }

                    echo '<td class="dataColumn dataDivider">';
                    echo $markbook->getFormattedAverage( $markbook->getCumulativeAverage($rowStudents['gibbonPersonID']) );
                    echo '</td>';
                    if ($markbook->getCumulativeAverage($rowStudents['gibbonPersonID']) != '') {
                        @$totals['cumulativeAverage'] += floatval($markbook->getCumulativeAverage($rowStudents['gibbonPersonID']));
                        @$totals['count'] += 1;
                    }

                    if ($markbook->getSetting('enableTypeWeighting') == 'Y' && count($markbook->getGroupedMarkbookTypes('year')) > 0 && $gibbonSchoolYearTermID <= 0) {

                        if ($columnFilter == 'averages') {
                            foreach ($markbook->getGroupedMarkbookTypes('year') as $type) {
                                echo '<td class="dataColumn">';
                                    echo $markbook->getFormattedAverage( $markbook->getTypeAverage($rowStudents['gibbonPersonID'], 'final', $type) );
                                echo '</td>';
                                @$totals[$type] += floatval($markbook->getTypeAverage($rowStudents['gibbonPersonID'], 'final', $type));
                            }
                        }

                        echo '<td class="dataColumn">';
                        echo $markbook->getFormattedAverage($markbook->getFinalGradeAverage($rowStudents['gibbonPersonID']));
                        echo '</td>';
                        @$totals['finalGrade'] += floatval($markbook->getFinalGradeAverage($rowStudents['gibbonPersonID']));
                    }
                }



                echo '</tr>';
            }
        }

        // Class Average
        if ($markbook->getSetting('enableColumnWeighting') == 'Y' && $columnFilter != 'unmarked') {
            echo '<tr style="height: 25px;">';
            echo '<td class="firstColumn right dataDividerTop">'.__('Class Average').':</td>';

            if ($markbook->hasExternalAssessments()) {
                echo '<td class="dataColumn dataDividerTop"></td>';
            }
            
            if ($markbook->hasPersonalizedTargets()) {
                echo '<td class="dataColumn dataDividerTop"></td>';
            }

            // Assignment Attainment Averages
            for ($i = 0; $i < $markbook->getColumnCountThisPage(); ++$i) {
                $attainmentCount = (isset($totals['attainment'][$i]['count']))? $totals['attainment'][$i]['count'] : 0;
                $attainmentTotal = (isset($totals['attainment'][$i]['total']))? $totals['attainment'][$i]['total'] : 0;
                $attainmentAverage = ($attainmentCount > 0 && $attainmentTotal > 0)? ($attainmentTotal / $attainmentCount) : '';

                if ($columnFilter == 'raw' && $markbook->getSetting('enableRawAttainment') == 'Y') {
                    echo '<td class="dataColumn dataDivider dataDividerTop">'.round(floatval($attainmentAverage), 1).'</td>';
                } else {
                    echo '<td class="dataColumn dataDivider dataDividerTop">'.$markbook->getFormattedAverage($attainmentAverage).'</td>';
                }
            }

            $count = isset($totals['count'])? min($totals['count'], $count) : $count;
            
            // Type Averages
            if ($columnFilter == 'averages') {
                if ($markbook->getSetting('enableTypeWeighting') == 'Y' ) {
                    if ( ($markbook->getSetting('enableGroupByTerm') == 'Y' && $gibbonSchoolYearTermID > 0) ||
                         ($markbook->getSetting('enableGroupByTerm') == 'N' && $gibbonSchoolYearTermID <= 0) ) {

                        foreach ($markbook->getGroupedMarkbookTypes('term') as $type) {
                            $typeAverage = ($count > 0 && $totals['typeAverage'][$type] > 0)? ($totals['typeAverage'][$type] / $count) : '';
                            echo '<td class="dataColumn dataDividerTop">'.$markbook->getFormattedAverage($typeAverage).'</td>';
                        }
                    }
                }
            }

            // Term Average
            if ($markbook->getSetting('enableGroupByTerm') == 'Y' && isset($totals['termAverage']) && count($totals['termAverage']) >= 1) {
                foreach ($totals['termAverage'] as $termTotal) {
                    $termAverage = ($count > 0 && $termTotal > 0)? ($termTotal / $count) : '';
                    echo '<td class="dataColumn dataDivider dataDividerTop">'.$markbook->getFormattedAverage($termAverage).'</td>';
                }
            }

            // Cumulative Average
            $cumulativeAverage = ($count > 0 && !empty($totals['cumulativeAverage']))? ($totals['cumulativeAverage'] / $count) : '';
            echo '<td class="dataColumn dataDivider dataDividerTop">'.$markbook->getFormattedAverage($cumulativeAverage).'</td>';

            if ($markbook->getSetting('enableTypeWeighting') == 'Y' && count($markbook->getGroupedMarkbookTypes('year')) > 0 && $gibbonSchoolYearTermID <= 0) {

                // Final Assignment Averages
                if ($columnFilter == 'averages') {
                    foreach ($markbook->getGroupedMarkbookTypes('year') as $type) {
                        $typeAverage = ($count > 0 && $totals[$type] > 0)? ($totals[$type] / $count) : '';
                        echo '<td class="dataColumn dataDividerTop">'.$markbook->getFormattedAverage($typeAverage).'</td>';
                    }
                }

                // Final Grade Average
                $finalGrade = ($count > 0 && $totals['finalGrade'] > 0)? ($totals['finalGrade'] / $count) : '';
                echo '<td class="dataColumn dataDividerTop">'.$markbook->getFormattedAverage($finalGrade).'</td>';
            }
            echo '</tr>';
        }


        echo "</tbody>";
        echo '</table>';

        echo '</div>';
        echo '</div><br/>';

    }
