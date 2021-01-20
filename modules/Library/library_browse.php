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
use Gibbon\Domain\Library\LibraryGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('Browse The Library'));

if (isActionAccessible($guid, $connection2, '/modules/Library/library_browse.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    //Get display settings
    $browseBGColorStyle = null;
    $browseBGColor = getSettingByScope($connection2, 'Library', 'browseBGColor');
    if ($browseBGColor != '') {
        $browseBGColorStyle = "; background-color: $browseBGColor";
    }
    $browseBGImageStyle = null;
    $browseBGImage = getSettingByScope($connection2, 'Library', 'browseBGImage');
    if ($browseBGImage != '') {
        $browseBGImageStyle = "; background-image: url(\"$browseBGImage\")";
    }

    //Set pagination variable
    $page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    echo "<div style='width: 1050px; border: 1px solid #444; margin-bottom: 30px; background-repeat: no-repeat; min-height: 450px; $browseBGColorStyle $browseBGImageStyle'>";
    echo "<div style='width: 762px; margin: 0 auto'>";
    //Display filters
    echo "<table class='noIntBorder borderGrey mb-1' cellspacing='0' style='width: 100%; background-color: rgba(255,255,255,0.8); margin-top: 30px'>";
    echo '<tr>';
    echo "<td style='width: 10px'></td>";
    echo "<td style='width: 50%; padding-top: 5px; text-align: center; vertical-align: top'>";
    echo "<div style='color: #CC0000; margin-bottom: -2px; font-weight: bold; font-size: 135%'>" . __('Monthly Top 5') . '</div>';
    try {
        $dataTop = array('timestampOut' => date('Y-m-d H:i:s', (time() - (60 * 60 * 24 * 30))));
        $sqlTop = "SELECT gibbonLibraryItem.name, producer, COUNT( * ) AS count FROM gibbonLibraryItem JOIN gibbonLibraryItemEvent ON (gibbonLibraryItemEvent.gibbonLibraryItemID=gibbonLibraryItem.gibbonLibraryItemID) JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) WHERE timestampOut>=:timestampOut AND gibbonLibraryItem.borrowable='Y' AND gibbonLibraryItemEvent.type='Loan' AND gibbonLibraryType.name='Print Publication' GROUP BY producer, name ORDER BY count DESC LIMIT 0, 5";
        $resultTop = $connection2->prepare($sqlTop);
        $resultTop->execute($dataTop);
    } catch (PDOException $e) {
        echo "<div class='error'>" . $e->getMessage() . '</div>';
    }
    if ($resultTop->rowCount() < 1) {
        echo "<div class='warning'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        $count = 0;
        while ($rowTop = $resultTop->fetch()) {
            ++$count;
            if ($rowTop['name'] != '') {
                if (strlen($rowTop['name']) > 35) {
                    echo "<div style='margin-top: 6px; font-weight: bold'>$count. " . substr($rowTop['name'], 0, 35) . '...</div>';
                } else {
                    echo "<div style='margin-top: 6px; font-weight: bold'>$count. " . $rowTop['name'] . '</div>';
                }
                if ($rowTop['producer'] != '') {
                    if (strlen($rowTop['producer']) > 35) {
                        echo "<div style='font-style: italic; font-size: 85%'> by " . substr($rowTop['producer'], 0, 35) . '...</div>';
                    } else {
                        echo "<div style='font-style: italic; font-size: 85%'> by " . $rowTop['producer'] . '</div>';
                    }
                }
            }
        }
    }
    echo '</td>';
    echo "<td style='width: 50%; padding-top: 5px; text-align: center; vertical-align: top'>";
    echo "<div style='color: #CC0000; margin-bottom: -5px; font-weight: bold; font-size: 135%'>" . __('New Titles') . '</div>';
    try {
        $dataTop = array();
        $sqlTop = "SELECT gibbonLibraryItem.name, producer FROM gibbonLibraryItem JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) WHERE gibbonLibraryItem.borrowable='Y' AND gibbonLibraryType.name='Print Publication'  ORDER BY timestampCreator DESC LIMIT 0, 5";
        $resultTop = $connection2->prepare($sqlTop);
        $resultTop->execute($dataTop);
    } catch (PDOException $e) {
        echo "<div class='error'>" . $e->getMessage() . '</div>';
    }
    if ($resultTop->rowCount() < 1) {
        echo "<div class='warning'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        $count = 0;
        while ($rowTop = $resultTop->fetch()) {
            ++$count;
            if ($rowTop['name'] != '') {
                if (strlen($rowTop['name']) > 35) {
                    echo "<div style='margin-top: 6px; font-weight: bold'>$count. " . substr($rowTop['name'], 0, 35) . '...</div>';
                } else {
                    echo "<div style='margin-top: 6px; font-weight: bold'>$count. " . $rowTop['name'] . '</div>';
                }
                if ($rowTop['producer'] != '') {
                    if (strlen($rowTop['producer']) > 35) {
                        echo "<div style='font-style: italic; font-size: 85%'> by " . substr($rowTop['producer'], 0, 35) . '...</div>';
                    } else {
                        echo "<div style='font-style: italic; font-size: 85%'> by " . $rowTop['producer'] . '</div>';
                    }
                }
            }
        }
    }
    echo '</td>';
    echo "<td style='width: 5px'></td>";
    echo '</tr>';
    echo '</table>';

    //Get current filter values
    $name = isset($_REQUEST['name']) ? trim($_REQUEST['name']) : null;
    $producer = isset($_REQUEST['producer']) ? trim($_REQUEST['producer']) : null;
    $category = isset($_REQUEST['category']) ? trim($_REQUEST['category']) : null;
    $collection = isset($_REQUEST['collection']) ? trim($_REQUEST['collection']) : null;
    $everything = isset($_REQUEST['everything']) ? trim($_REQUEST['everything']) : null;

    $gibbonLibraryItemID = isset($_GET['gibbonLibraryItemID']) ? trim($_GET['gibbonLibraryItemID']) : null;

    // Build the category/collection arrays
    $sql = "SELECT gibbonLibraryTypeID as value, name, fields FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
    $result = $pdo->executeQuery(array(), $sql);

    $categoryList = ($result->rowCount() > 0) ? $result->fetchAll() : array();
    $collections = $collectionsChained = array();
    $categories = array_reduce($categoryList, function ($group, $item) use (&$collections, &$collectionsChained) {
        $group[$item['value']] = __($item['name']);
        foreach (unserialize($item['fields']) as $field) {
            if ($field['name'] == 'Collection' and $field['type'] == 'Select') {
                foreach (explode(',', $field['options']) as $collectionItem) {
                    $collectionItem = trim($collectionItem);
                    $collections[$collectionItem] = __($collectionItem);
                    $collectionsChained[$collectionItem] = $item['value'];
                }
            }
        }
        return $group;
    }, array());


    $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'] . '/index.php', 'get');
    $form->setClass('noIntBorder fullWidth borderGrey mb-6');

    $form->addHiddenValue('q', '/modules/Library/library_browse.php');

    $row = $form->addRow();

    $col = $row->addColumn()->setClass('quarterWidth');
    $col->addLabel('name', __('Title'));
    $col->addTextField('name')->setClass('fullWidth')->setValue($name);

    $col = $row->addColumn()->setClass('quarterWidth');
    $col->addLabel('producer', __('Author/Producer'));
    $col->addTextField('producer')->setClass('fullWidth')->setValue($producer);

    $col = $row->addColumn()->setClass('quarterWidth');
    $col->addLabel('category', __('Category'));
    $col->addSelect('category')
        ->fromArray($categories)
        ->setClass('fullWidth')
        ->selected($category)
        ->placeholder();

    $col = $row->addColumn()->setClass('quarterWidth');
    $col->addLabel('collection', __('Collection'));
    $col->addSelect('collection')
        ->fromArray($collections)
        ->chainedTo('category', $collectionsChained)
        ->setClass('fullWidth')
        ->selected($collection)
        ->placeholder();

    $col = $form->addRow()->addColumn();
    $col->addLabel('everything', __('All Fields'));
    $col->addTextField('everything')->setClass('fullWidth')->setValue($everything);

    $row = $form->addRow();
    $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    //Cache TypeFields
    $sql = "SELECT gibbonLibraryTypeID as groupBy, gibbonLibraryType.* FROM gibbonLibraryType";
    $typeFields = $pdo->select($sql)->fetchGroupedUnique();

    $gateway = $container->get(LibraryGateway::class);
    $criteria = $gateway->newQueryCriteria(true)
        ->sortBy('id')
        ->filterBy('name', $name)
        ->filterBy('producer', $producer)
        ->filterBy('category', $category)
        ->filterBy('collection', $collection)
        ->filterBy('everything', $everything)
        ->fromPOST();
    $books = $gateway->queryBrowseItems($criteria);
    $table = DataTable::createPaginated('books', $criteria);

    $table->addExpandableColumn('details')->format(function ($item) {
        $typeFields = unserialize($item['fields']);
        $details = "<table class='smallIntBorder' style='width:100%;'>";
        foreach ($typeFields as $fieldName => $fieldValue) {
            $details .= sprintf('<tr><td><b>%1$s</b></td><td>%2$s</td></tr>', __($fieldName), $fieldValue);
        }
        $details .= "</table>";
        return $details;
    });

    $table->addColumn('imageLocation', __('Cover Art'))->notSortable()->format(function ($item) {
        return Format::photo($item['imageLocation']);
    });

    $table->addColumn('name', __('Name'))
        ->description(__('Author/Producer'))
        ->format(function ($item) {
            return sprintf('<b>%1$s</b><br/><span style="font-size: 85%%; font-style:italic;">%2$s</span>', $item['name'], __($item['producer']));
        });

    $table->addColumn('id', __('ID'))
        ->description(__('Status'))
        ->format(function ($item) {
            return sprintf('<b>%1$s</b><br/><span style="font-size: 85%%; font-style:italic;">%2$s</span>', $item['id'], __($item['status']));
        });

    $table->addColumn('spaceName', __('Location'))
        ->sortable(['spaceName', 'locationDetail'])
        ->format(function ($item) {
            return sprintf('<b>%1$s</b><br/><span style="font-size: 85%%; font-style:italic;">%2$s</span>', $item['spaceName'], $item['locationDetail']);
        });

    echo $table->render($books);

    echo '</div>';
    echo '</div>';
}
