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
use Gibbon\Data\Importer;
use Gibbon\Data\ImportType;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;

require __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, "/modules/System Admin/import_run.php")==false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $type = $_GET['type'] ?? '';
    $step = isset($_GET['step'])? min(max(1, $_GET['step']), 4) : 1;

    $importType = ImportType::loadImportType($type, $pdo);

    $nameParts = array_map('trim', explode('-', $importType->getDetail('name')));
    $name = implode(' - ', array_map('__', $nameParts));

    $page->breadcrumbs
        ->add(__('Import From File'), 'import_manage.php')
        ->add($name, 'import_run.php', ['type' => $type])
        ->add(__('Step {number}', ['number' => $step]));

    // Some script performance tracking
    $memoryStart = memory_get_usage();
    $timeStart = microtime(true);

    $importer = new Importer($pdo);



    if ($importType->isImportAccessible($guid, $connection2) == false) {
        echo Format::alert(__('You do not have access to this action.'));
        return;
    } elseif (empty($importType)) {
        echo Format::alert(__('Your request failed because your inputs were invalid.'));
        return;
    } elseif (!$importType->isValid()) {
        echo Format::alert(__('There was an error reading the file {value}.', ['value' => $type]));
        return;
    }

    $steps = [
        1 => __('Select File'),
        2 => __('Confirm Data'),
        3 => __('Dry Run'),
        4 => __('Live Run'),
    ];

    echo "<ul class='multiPartForm'>";
    printf("<li class='step %s'>%s</li>", ($step >= 1)? "active" : "", $steps[1]);
    printf("<li class='step %s'>%s</li>", ($step >= 2)? "active" : "", $steps[2]);
    printf("<li class='step %s'>%s</li>", ($step >= 3)? "active" : "", $steps[3]);
    printf("<li class='step %s'>%s</li>", ($step >= 4)? "active" : "", $steps[4]);
    echo "</ul>";

    echo '<h2>';
    echo __('Step {number}', ['number' => $step]).' - '.__($steps[$step]);
    echo '</h2>';

    //STEP 1, SELECT TERM -----------------------------------------------------------------------------------
    if ($step==1) {
        $data = array('type' => $type);
        $sql = "SELECT gibbonLog.gibbonLogID
                FROM gibbonLog WHERE gibbonLog.title = CONCAT('Import - ', :type) 
                ORDER BY gibbonLog.timestamp DESC LIMIT 1" ;
        $importLog = $pdo->selectOne($sql, $data);

        echo Format::alert(__("Always backup your database before performing any imports. You will have the opportunity to review the data on the next step, however there's no guarantee the import won't change or overwrite important data."), 'message');

        $form = Form::create('importStep1', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/import_run.php&type='.$type.'&step=2');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $availableModes = array();
        $modes = $importType->getDetail('modes');
        if (!empty($modes['update']) && !empty($modes['insert'])) {
            $availableModes['sync'] = __('Update & Insert');
        }
        if (!empty($modes['update'])) {
            $availableModes['update'] = __('Update');
        }
        if (!empty($modes['insert'])) {
            $availableModes['insert'] = __('Insert');
        }

        $row = $form->addRow();
        $row->addLabel('mode', __('Mode'));
        $row->addSelect('mode')->fromArray($availableModes)->required();

        $columnOrders = array(
            'guess'      => __('Best Guess'),
            'last'       => __('Last Import'),
            'linearplus' => __('From Exported Data'),
            'linear'     => __('From Default Order (see notes)'),
            'skip'       => __('Skip Non-Required Fields'),
        );
        $selectedOrder = (!empty($importLog))? 'last' : 'guess';
        $row = $form->addRow();
        $row->addLabel('columnOrder', __('Column Order'));
        $row->addSelect('columnOrder')->fromArray($columnOrders)->required()->selected($selectedOrder);

        $row = $form->addRow();
        $row->addLabel('file', __('File'))->description(__('See Notes below for specification.'));
        $row->addFileUpload('file')->required()->accepts('.csv,.xls,.xlsx,.xml,.ods');

        $row = $form->addRow();
        $row->addLabel('fieldDelimiter', __('Field Delimiter'));
        $row->addTextField('fieldDelimiter')->required()->maxLength(1)->setValue(',');

        $row = $form->addRow();
        $row->addLabel('stringEnclosure', __('String Enclosure'));
        $row->addTextField('stringEnclosure')->required()->maxLength(1)->setValue('"');

        $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

        echo $form->getOutput();

        $usesDates = false;
        $importSpecification = array_reduce($importType->getAllFields(), function ($group, $fieldName) use ($importType, &$usesDates) {
            if ($importType->getField($fieldName, 'filter') == 'date') $usesDates = true;

            if (!$importType->isFieldHidden($fieldName)) {
                $group[] = [
                    'count' => count($group) + 1,
                    'name'  => __($importType->getField($fieldName, 'name'))
                                .($importType->isFieldRequired($fieldName)? ' <strong class="highlight">*</strong>' : ''),
                    'desc' => __($importType->getField($fieldName, 'desc')),
                    'type' => $importType->readableFieldType($fieldName),
                ];
            }
            return $group;
        }, []);
    
        $notes = '<ol>';
        $notes .= '<li style="color: #c00; font-weight: bold">'.__('Always include a header row in the uploaded file.').'</li>';
        $notes .= '<li>'.__('Imports cannot be run concurrently (e.g. make sure you are the only person importing at any one time).').'</li>';
        if ($usesDates) {
            $notes .= '<li>'.__("Dates are converted based on the separator used: American mm/dd/yy or mm/dd/yyyy, European dd.mm.yy, dd.mm.yyyy or dd-mm-yyyy. To avoid potential ambiguity, it's best to use ISO YYYY-MM-DD.");
            $notes .= ' <a href="http://php.net/manual/en/function.strtotime.php#refsect1-function.strtotime-notes" target="_blank"><i><small>'.__('More info').'</i></small></a></li>';
        }
        $notes .= '</ol>';

        $table = DataTable::create('notes');
        $table->setTitle(__('Notes'));
        $table->setDescription($notes);

        if (isActionAccessible($guid, $connection2, '/modules/System Admin/export_run.php')) {
            $table->addHeaderAction('export', __('Export Columns'))
                ->setURL('/modules/System Admin/export_run.php')
                ->addParam('type', $type)
                ->addParam('sidebar', 'false')
                ->setIcon('download')
                ->directLink()
                ->displayLabel();
        }

        $table->addColumn('count', '#');
        $table->addColumn('name', __('Name'));
        $table->addColumn('desc', __('Description'));
        $table->addColumn('type', __('Type'))->width('20%');

        echo $table->render(new DataSet($importSpecification));
    }

    //STEP 2, CONFIG -----------------------------------------------------------------------------------
    elseif ($step==2) {
        $mode = (isset($_POST['mode']))? $_POST['mode'] : null;

        //Check file type
        if ($importer->isValidMimeType($_FILES['file']['type']) == false) {
            echo Format::alert(__('Import cannot proceed, as the submitted file has a MIME-TYPE of %1$s, and as such does not appear to be a CSV file.', ['%1$s' => $_FILES['file']['type']]));
        } elseif (empty($_POST["fieldDelimiter"]) or empty($_POST["stringEnclosure"])) {
            echo Format::alert(__('Import cannot proceed, as the "Field Delimiter" and/or "String Enclosure" fields have been left blank.'));
        } elseif ($mode != "sync" and $mode != "insert" and $mode != "update") {
            echo Format::alert(__('Import cannot proceed, as the "Mode" field has been left blank.'));
        } else {
            $proceed=true ;
            $columnOrder=(isset($_POST['columnOrder']))? $_POST['columnOrder'] : 'guess';

            if ($columnOrder == 'last') {
                $data = array('type' => $type);
                $sql = "SELECT * FROM gibbonLog WHERE gibbonLog.title = CONCAT('Import - ', :type) 
                        ORDER BY gibbonLog.timestamp DESC LIMIT 1" ;

                $importLog = $pdo->selectOne($sql, $data);
                $importLog = isset($importLog['serialisedArray'])? unserialize($importLog['serialisedArray']) : [];
                $columnOrderLast = $importLog['columnOrder'] ?? [];
            }

            $importer->fieldDelimiter = (!empty($_POST['fieldDelimiter']))? stripslashes($_POST['fieldDelimiter']) : ',';
            $importer->stringEnclosure = (!empty($_POST['stringEnclosure']))? stripslashes($_POST['stringEnclosure']) : '"';

            // Load the CSV or Excel data from the uploaded file
            $csvData = $importer->readFileIntoCSV();

            $headings = $importer->getHeaderRow();
            $firstLine = $importer->getFirstRow();

            if (empty($csvData) || empty($headings) || empty($firstLine)) {
                echo Format::alert(__('There was an error reading the file {value}.', ['value' => $_FILES['file']['name']]));
                return;
            }

            echo "<script>";
            echo "var csvFirstLine = " . json_encode($firstLine) .";";
            echo "var columnDataSkip = " . Importer::COLUMN_DATA_SKIP .";";
            echo "var columnDataCustom = " . Importer::COLUMN_DATA_CUSTOM .";";
            echo "var columnDataFunction = " . Importer::COLUMN_DATA_FUNCTION .";";
            echo "</script>";
            
            $form = Form::create('importStep2', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/import_run.php&type='.$type.'&step=3');
            $form->setClass('w-full blank');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('mode', $mode);
            $form->addHiddenValue('fieldDelimiter', urlencode($_POST['fieldDelimiter']));
            $form->addHiddenValue('stringEnclosure', urlencode($_POST['stringEnclosure']));
            $form->addHiddenValue('ignoreErrors', 0);

            // SYNC SETTINGS
            if ($mode == "sync" || $mode == "update") {
                $lastFieldValue = ($columnOrder == 'last' && isset($columnOrderLast['syncField']))? $columnOrderLast['syncField'] : 'N';
                $lastColumnValue = ($columnOrder == 'last' && isset($columnOrderLast['syncColumn']))? $columnOrderLast['syncColumn'] : '';

                if ($columnOrder == 'linearplus') {
                    $lastFieldValue = 'Y';
                    $lastColumnValue = $importType->getPrimaryKey();
                }

                $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth');

                $row = $table->addRow();
                $row->addLabel('syncField', __('Sync').'?')->description(__('Only rows with a matching database ID will be imported.'));
                $row->addYesNoRadio('syncField')->checked($lastFieldValue);

                $form->toggleVisibilityByClass('syncDetails')->onRadio('syncField')->when('Y');
                $row = $table->addRow()->addClass('syncDetails');
                $row->addLabel('syncColumn', __('Primary Key'))
                    ->description($importType->getPrimaryKey());
                $row->addSelect('syncColumn')
                    ->fromArray($headings)
                    ->selected($lastColumnValue)
                    ->placeholder()
                    ->required();
            }

            $form->addRow()->addContent('&nbsp;');

            // COLUMN SELECTION
            if (!empty($importType->getAllFields())) {
                $table = $form->addRow()->addTable()->setClass('colorOddEven fullWidth');

                $header = $table->addHeaderRow();
                $header->addContent(__('Field Name'));
                $header->addContent(__('Type'));
                $header->addContent(__('Column'));
                $header->addContent(__('Example'));

                $count = 0;

                $defaultColumns = function ($fieldName) use (&$importType, $mode) {
                    $columns = [];
                    
                    if ($importType->isFieldRequired($fieldName) == false || ($mode == 'update' && !$importType->isFieldUniqueKey($fieldName))) {
                        $columns[Importer::COLUMN_DATA_SKIP] = '[ '.__('Skip this Column').' ]';
                    }
                    if ($importType->getField($fieldName, 'custom')) {
                        $columns[Importer::COLUMN_DATA_CUSTOM] = '[ '.__('Custom').' ]';
                    }
                    if ($importType->getField($fieldName, 'function')) {
                        $columns[Importer::COLUMN_DATA_FUNCTION] = '[ '.__('Generate').' ]';
                        //data-function='". $importType->getField($fieldName, 'function') ."'
                    }
                    return $columns;
                };

                $columns = array_reduce(range(0, count($headings)-1), function ($group, $index) use (&$headings) {
                    $group[strval($index)." "] = $headings[$index];
                    return $group;
                }, array());

                $columnIndicators = function ($fieldName) use (&$importType, $mode) {
                    $output = '';
                    if ($importType->isFieldRequired($fieldName) && !($mode == 'update' && !$importType->isFieldUniqueKey($fieldName))) {
                        $output .= " <strong class='highlight'>*</strong>";
                    }
                    if ($importType->isFieldUniqueKey($fieldName)) {
                        $output .= "<img title='" . __('Must be unique') . "' src='./themes/Default/img/target.png' style='float: right; width:14px; height:14px;margin-left:4px;'>";
                    }
                    if ($importType->isFieldRelational($fieldName)) {
                        $relationalTable = $importType->getField($fieldName, 'relationship')['table'] ?? '';
                        $output .= "<img title='" .__('Relationship') .': '.$relationalTable. "' src='./themes/Default/img/refresh.png' style='float: right; width:14px; height:14px;margin-left:4px;'>";
                    }
                    return $output;
                };

                foreach ($importType->getAllFields() as $fieldName) {
                    if ($importType->isFieldHidden($fieldName)) {
                        $columnIndex = Importer::COLUMN_DATA_HIDDEN;
                        if ($importType->isFieldLinked($fieldName)) {
                            $columnIndex = Importer::COLUMN_DATA_LINKED;
                        }
                        if (!empty($importType->getField($fieldName, 'function'))) {
                            $columnIndex = Importer::COLUMN_DATA_FUNCTION;
                        }

                        $form->addHiddenValue("columnOrder[$count]", $columnIndex);
                        $count++;
                        continue;
                    }
                    
                    $selectedColumn = '';
                    if ($columnOrder == 'linear' || $columnOrder == 'linearplus') {
                        $selectedColumn = ($columnOrder == 'linearplus')? $count+1 : $count;
                    } elseif ($columnOrder == 'last') {
                        $selectedColumn = isset($columnOrderLast[$count])? $columnOrderLast[$count] : '';
                    } elseif ($columnOrder == 'guess' || $columnOrder == 'skip') {
                        foreach ($headings as $index => $columnName) {
                            if (mb_strtolower($columnName) == mb_strtolower($fieldName) || mb_strtolower($columnName) == mb_strtolower($importType->getField($fieldName, 'name'))) {
                                $selectedColumn = $index;
                                break;
                            }
                        }
                    }

                    if ($columnOrder == 'skip' && !($importType->isFieldRequired($fieldName) && !($mode == 'update' && !$importType->isFieldUniqueKey($fieldName)))) {
                        $selectedColumn = Importer::COLUMN_DATA_SKIP;
                    }

                    $row = $table->addRow();
                    $row->addContent(__($importType->getField($fieldName, 'name')))
                            ->wrap('<span class="'.$importType->getField($fieldName, 'desc').'">', '</span>')
                            ->append($columnIndicators($fieldName));
                    $row->addContent($importType->readableFieldType($fieldName));
                    $row->addSelect('columnOrder['.$count.']')
                            ->setID('columnOrder'.$count)
                            ->fromArray($defaultColumns($fieldName))
                            ->fromArray($columns)
                            ->required()
                            ->setClass('columnOrder mediumWidth')
                            ->selected($selectedColumn)
                            ->placeholder();
                    $row->addTextField('columnText['.$count.']')
                            ->setID('columnText'.$count)
                            ->setClass('shortWidth columnText')
                            ->readonly()
                            ->disabled();

                    $count++;
                }
            }

            $form->addRow()->addContent('&nbsp;');

            // CSV PREVIEW
            $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth');

            $row = $table->addRow();
            $row->addLabel('csvData', __('Data'));
            $row->addTextArea('csvData')->setRows(4)->setCols(74)->setClass('')->readonly()->setValue($csvData);

            $row = $table->addRow();
            $row->addFooter();
            $row->addSubmit();

            echo $form->getOutput();
        }
    }

    //STEP 3 & 4, DRY & LIVE RUN  -----------------------------------------------------------------------------------
    elseif ($step==3 || $step==4) {
        // Gather our data
        $mode = $_POST['mode'] ?? null;
        $syncField = $_POST['syncField'] ?? null;
        $syncColumn = $_POST['syncColumn'] ?? null;

        $csvData = $_POST['csvData'] ?? null;
        if ($step==4) {
            $columnOrder = isset($_POST['columnOrder'])? unserialize($_POST['columnOrder']) : null;
            $columnText = isset($_POST['columnText'])? unserialize($_POST['columnText']) : null;
        } else {
            $columnOrder = $_POST['columnOrder'] ?? null;
            $columnText = $_POST['columnText'] ?? null;
        }

        $fieldDelimiter = isset($_POST['fieldDelimiter'])? urldecode($_POST['fieldDelimiter']) : null;
        $stringEnclosure = isset($_POST['stringEnclosure'])? urldecode($_POST['stringEnclosure']) : null;

        $ignoreErrors = $_POST['ignoreErrors'] ?? false;

        if (empty($csvData) || empty($columnOrder)) {
            echo Format::alert(__('Your request failed because your inputs were invalid.'));
            return;
        } elseif ($mode != "sync" and $mode != "insert" and $mode != "update") {
            echo Format::alert(__('Import cannot proceed, as the "Mode" field has been left blank.'));
        } elseif (($mode == 'sync' || $mode == 'update') && (!empty($syncField) && $syncColumn < 0)) {
            echo Format::alert(__('Your request failed because your inputs were invalid.'));
            return;
        } elseif (empty($fieldDelimiter) or empty($stringEnclosure)) {
            echo Format::alert(__('Import cannot proceed, as the "Field Delimiter" and/or "String Enclosure" fields have been left blank.'));
        } else {
            $importer->mode = $mode;
            $importer->syncField = ($syncField == 'Y');
            $importer->syncColumn = $syncColumn;
            $importer->fieldDelimiter = (!empty($fieldDelimiter))? stripslashes($fieldDelimiter) : ',';
            $importer->stringEnclosure = (!empty($stringEnclosure))? stripslashes($stringEnclosure) : '"';

            
            $importSuccess = $buildSuccess = $databaseSuccess = true;
            $importSuccess = $importer->readCSVString($csvData);

            foreach ($importType->getTables() as $tableName) {

                $importType->switchTable($tableName);

                if ($importSuccess || $ignoreErrors) {
                    $buildSuccess &= $importer->buildTableData($importType, $columnOrder, $columnText);
                }

                if ($buildSuccess || $ignoreErrors) {
                    $databaseSuccess &= $importer->importIntoDatabase($importType, ($step == 4));
                }
            }

            $overallSuccess = ($importSuccess && $buildSuccess && $databaseSuccess);

            if ($overallSuccess) {
                if ($step == 3) {
                    echo Format::alert(__('The data was successfully validated. This is a <b>DRY RUN!</b> No changes have been made to the database. If everything looks good here, you can click submit to complete this import.'), 'message');
                } else {
                    echo Format::alert(__('The import completed successfully and all relevant database fields have been created and/or updated.'), 'success');
                }
            } elseif ($ignoreErrors) {
                echo Format::alert(__('Imported with errors ignored.'), 'warning');
            } else {
                echo Format::alert($importer->getLastError());
            }

            $logs = $importer->getLogs();

            if (count($logs) > 0) {
                $table = DataTable::create('logs');
                $table->modifyRows(function ($log, $row) {
                    return $row->addClass($log['type'] ?? '');
                });

                $table->addColumn('row', __('Row'));
                $table->addColumn('field', __('Field'))
                    ->format(function ($log) {
                        return $log['field_name'].(!empty($log['field']) ? ' ('. $log['field'] .')' : '');
                    });
                $table->addColumn('info', __('Message'));

                echo $table->render(new DataSet($logs));
                echo '<br/>';
            }

            $executionTime = mb_substr(microtime(true) - $timeStart, 0, 6).' sec';
            $memoryUsage = Format::filesize(max(0, memory_get_usage() - $memoryStart)); 
            
            $results = array(
                'step'            => $step,
                'importSuccess'   => $importSuccess,
                'buildSuccess'    => $buildSuccess,
                'databaseSuccess' => $databaseSuccess,
                'rows'            => $importer->getRowCount(),
                'rowerrors'       => $importer->getErrorRowCount(),
                'errors'          => $importer->getErrorCount(),
                'warnings'        => $importer->getWarningCount(),
                'inserts'         => $importer->getDatabaseResult('inserts'),
                'inserts_skipped' => $importer->getDatabaseResult('inserts_skipped'),
                'updates'         => $importer->getDatabaseResult('updates'),
                'updates_skipped' => $importer->getDatabaseResult('updates_skipped'),
                'executionTime'   => $executionTime,
                'memoryUsage'     => $memoryUsage,
                'ignoreErrors'    => $ignoreErrors,
            );

            echo $page->fetchFromTemplate('importer.twig.html', $results);
            
            if ($step==3) {
                $form = Form::create('importStep2', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/import_run.php&type='.$type.'&step=4');
                $form->setClass('w-full blank');

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                $form->addHiddenValue('mode', $mode);
                $form->addHiddenValue('syncField', $syncField);
                $form->addHiddenValue('syncColumn', $syncColumn);
                $form->addHiddenValue('columnOrder', serialize($columnOrder));
                $form->addHiddenValue('columnText', serialize($columnText));
                $form->addHiddenValue('fieldDelimiter', urlencode($fieldDelimiter));
                $form->addHiddenValue('stringEnclosure', urlencode($stringEnclosure));

                // CSV PREVIEW
                $table = $form->addRow()->addTable()->setClass('smallIntBorder fullWidth');

                $row = $table->addRow();
                $row->addLabel('csvData', __('Data'));
                $row->addTextArea('csvData')->setRows(4)->setCols(74)->setClass('')->readonly()->setValue($csvData);

                $row = $table->addRow();
                $row->onlyIf(!$overallSuccess)->addCheckbox('ignoreErrors')->description(__('Ignore Errors? (Expert Only!)'))->setValue($ignoreErrors)->setClass('');
                $row->onlyIf($overallSuccess)->addContent('');
                
                if (!$overallSuccess && !$ignoreErrors) {
                    $row->addButton(__('Failed'))->setID('submitStep3')->disabled()->addClass('right');
                } else {
                    $row->addSubmit()->setID('submitStep3');
                }
                    
                echo $form->getOutput();
            }

            if ($step==4) {

                // Output passwords if generated
                if (!empty($importer->outputData['passwords'])) {
                    $table = DataTable::create('output');
                    $table->setTitle(__('New Password'));
                    $table->setDescription(__('These passwords have been generated by the import process. They have <b>NOT</b> been recorded anywhere: please copy & save them now if you wish to record them.'));

                    $table->addColumn('username', __('Username'));
                    $table->addColumn('password', __('Password'));

                    echo $table->render(new DataSet($importer->outputData['passwords']));
                }

                $columnOrder['syncField'] =  $syncField;
                $columnOrder['syncColumn'] =  $syncColumn;

                $importer->createImportLog($_SESSION[$guid]['gibbonPersonID'], $type, $results, $columnOrder);
            }
        }
    }
}
