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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Messenger\MessengerGateway;

$page->breadcrumbs->add(__('Manage Messages'));

if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET["q"], $connection2);
    if ($highestAction==FALSE) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    $search = $_GET['search'] ?? '';

    // CRITERIA
    $messengerGateway = $container->get(MessengerGateway::class);
    $criteria = $messengerGateway->newQueryCriteria(true)
        ->searchBy($messengerGateway->getSearchableColumns(), $search)
        ->filterBy('confidential', $session->get('gibbonPersonID'))
        ->sortBy(['timestamp'], 'DESC')
        ->fromPOST();

    $form = Form::create('searchForm', $gibbon->session->get('absoluteURL').'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');
    $form->setTitle(__('Search'));

    $form->addHiddenValue('q', '/modules/'.$gibbon->session->get('module').'/messenger_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search In'))->description(__('Subject, body.'));
        $row->addTextField('search')->setValue($search);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

    echo $form->getOutput();

    // QUERY
    $messages = $messengerGateway->queryMessages($criteria, $session->get('gibbonSchoolYearID'), $highestAction != 'Manage Messages_all' ? $session->get('gibbonPersonID') : '' );
    $sendingMessages = $messengerGateway->getSendingMessages();

    // DATA TABLE
    $table = DataTable::createPaginated('messages', $criteria);
    $table->setTitle(__('Messages'));

    if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php')) {
        $table->addHeaderAction('new', __('New Message'))
            ->setURL('/modules/Messenger/messenger_post.php')
            ->setIcon('page_new')
            ->addParam('search', $search)
            ->displayLabel();
    }

    if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_postQuickWall.php')) {
        $table->addHeaderAction('newWall', __('New Quick Wall Message'))
            ->setURL('/modules/Messenger/messenger_postQuickWall.php')
            ->setIcon('page_new')
            ->addParam('search', $search)
            ->displayLabel()
            ->prepend(' | ');
    }
    
    $table->addColumn('subject', __('Subject'))
        ->context('primary')
        ->format(function ($values) {
            $tag = $values['confidential'] == 'Y' ? Format::tag(__('Confidential'), 'dull ml-2') : '';
            return Format::bold($values['subject']).$tag;
        });

    $table->addColumn('timestamp', __('Date Sent'))
        ->description(__('Dates Published'))
        ->format(function ($values) {
            $output = Format::date($values['timestamp']).'<br/>';

            if ($values['messageWall'] == 'Y') {
                if (!empty($values['messageWall_date1'])) $output .= Format::small(Format::date($values['messageWall_date1'])).'<br/>';
                if (!empty($values['messageWall_date2'])) $output .= Format::small(Format::date($values['messageWall_date2'])).'<br/>';
                if (!empty($values['messageWall_date3'])) $output .= Format::small(Format::date($values['messageWall_date3'])).'<br/>';
            }
            return $output;
        });

    if ($highestAction == 'Manage Messages_all') {
        $table->addColumn('author', __('Author'))
            ->context('primary')
            ->sortable(['surname', 'preferredName'])
            ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Staff', false]));
    }

    $table->addColumn('recipients', __('Recipients'))
        ->notSortable()
        ->format(function ($values) use (&$pdo, &$sendingMessages) {
            $output = '';
            if (!empty($sendingMessages[$values['gibbonMessengerID']])) {
                $output .= '<div class="statusBar" data-id="'.$sendingMessages[$values['gibbonMessengerID']].'">';
                $output .= '<div class="mb-2"><img class="align-middle w-56 -mt-px -ml-1" src="./themes/Default/img/loading.gif">'
                    .'<span class="tag ml-2 message">'.__('Sending').'</span></div>';
                $output .= '</div>';
            }

            $data = ['gibbonMessengerID' => $values['gibbonMessengerID']];
            $sql = "SELECT type, id FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID ORDER BY type, id";
            $targets = $pdo->select($sql, $data)->fetchAll();

            foreach ($targets as $target) {
                if ($target['type']=='Activity') {
                    $data = ['gibbonActivityID'=>$target['id']];
                    $sql = "SELECT name FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID";
                    
                    if ($targetData = $pdo->select($sql, $data)->fetch()) {
                        $output .= '<b>' . __($target['type']) . '</b> - ' . $targetData['name'] . '<br/>';
                    }
                }
                elseif ($target['type']=='Class') {
                    $data = ['gibbonCourseClassID'=>$target['id']];
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID";
                    
                    if ($targetData = $pdo->select($sql, $data)->fetch()) {
                        $output .= '<b>' . __($target['type']) . '</b> - ' . $targetData['course'] . '.' . $targetData['class'] . '<br/>';
                    }
                } elseif ($target['type']=='Course') {
                    $data = ['gibbonCourseID'=>$target['id']];
                    $sql = "SELECT name FROM gibbonCourse WHERE gibbonCourseID=:gibbonCourseID";
                    
                    if ($targetData = $pdo->select($sql, $data)->fetch()) {
                        $output .= '<b>' . __($target['type']) . '</b> - ' . $targetData['name'] . '<br/>';
                    }
                } elseif ($target['type']=='Role') {
                    $data = ['gibbonRoleID'=>$target['id']];
                    $sql = "SELECT name FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID";
                    
                    if ($targetData = $pdo->select($sql, $data)->fetch()) {
                        $output .= '<b>' . __($target['type']) . '</b> - ' . __($targetData['name']) . '<br/>';
                    }
                } elseif ($target['type']=='Role Category') {
                    $output .= '<b>' . __($target['type']) . '</b> - ' . __($target['id']) . '<br/>';
                } elseif ($target['type']=='Form Group') {
                   
                    $data = ['gibbonFormGroupID'=>$target['id']];
                    $sql = "SELECT name FROM gibbonFormGroup WHERE gibbonFormGroupID=:gibbonFormGroupID";
                
                    if ($targetData = $pdo->select($sql, $data)->fetch()) {
                        $output .= '<b>' . __($target['type']) . '</b> - ' . $targetData['name'] . '<br/>';
                    }
                } elseif ($target['type']=='Year Group') {

                    $data = ['gibbonYearGroupID'=>$target['id']];
                    $sql = "SELECT name FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID";
                    
                    if ($targetData = $pdo->select($sql, $data)->fetch()) {
                        $output .= '<b>' . __($target['type']) . '</b> - ' . __($targetData['name']) . '<br/>';
                    }
                } elseif ($target['type']=='Applicants') {

                    $data = ['gibbonSchoolYearID'=>$target['id']];
                    $sql = "SELECT name FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
                    
                    if ($targetData = $pdo->select($sql, $data)->fetch()) {
                        $output .= '<b>' . __($target['type']) . '</b> - ' . $targetData['name'] . '<br/>';
                    }
                } elseif ($target['type']=='Houses') {

                    $data = ['gibbonHouseID'=>$target['id']];
                    $sql = "SELECT name FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID";
                    
                    if ($targetData = $pdo->select($sql, $data)->fetch()) {
                        $output .= '<b>' . __($target['type']) . '</b> - ' . $targetData['name'] . '<br/>';
                    }
                } elseif ($target['type']=='Transport') {
                    $output .= '<b>' . __($target['type']) . '</b> - ' . __($target['id']) . '<br/>';
                } elseif ($target['type']=='Attendance') {
                    $output .= '<b>' . __($target['type']) . '</b> - ' . __($target['id']) . '<br/>';
                } elseif ($target['type']=='Individuals') {
                    
                    $data = ['gibbonPersonID'=>$target['id']];
                    $sql = "SELECT preferredName, surname FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID";
                    
                    if ($targetData = $pdo->select($sql, $data)->fetch()) {
                        $output .= '<b>' . __($target['type']) . '</b> - ' . Format::name('', $targetData['preferredName'], $targetData['surname'], 'Student', true) . '<br/>';
                    }
                } elseif ($target['type']=='Group') {
                    $data = ['gibbonGroupID'=>$target['id']];
                    $sql = 'SELECT name FROM gibbonGroup WHERE gibbonGroupID=:gibbonGroupID';

                    if ($targetData = $pdo->select($sql, $data)->fetch()) {
                        $output .= '<b>' . __($target['type']) . '</b> - ' . $targetData['name'] . '<br/>';
                    }
                }
            }

            return $output;
        });

    $table->addColumn('email', __('Email'))->format(function ($values) use (&$session) {
        return $values['email'] == 'Y'
            ? '<img title="'.__('Sent by email.').'" src="'.$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName').'/img/iconTick.png"/>'
            : '<img title="'.__('Not sent by email.').'" src="'.$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName').'/img/iconCross.png"/>';
    });

    $table->addColumn('messageWall', __('Wall'))->format(function ($values) use (&$session) {
        return $values['messageWall'] == 'Y'
            ? '<img title="'.__('Sent by message wall.').'" src="'.$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName').'/img/iconTick.png"/>'
            : '<img title="'.__('Not sent by message wall.').'" src="'.$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName').'/img/iconCross.png"/>';
    });

    $table->addColumn('sms', __('SMS'))->format(function ($values) use (&$session) {
        return $values['sms'] == 'Y'
            ? '<img title="'.__('Sent by SMS.').'" src="'.$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName').'/img/iconTick.png"/>'
            : '<img title="'.__('Not sent by SMS.').'" src="'.$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName').'/img/iconCross.png"/>';
    });

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonMessengerID')
        ->addParam('sidebar', 'true')
        ->addParam('search', $criteria->getSearchText(true))
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Messenger/messenger_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Messenger/messenger_manage_delete.php');

            if (!is_null($values['emailReceipt'])) {
                $actions->addAction('send', __('View Send Report'))
                        ->setURL('/modules/Messenger/messenger_manage_report.php')
                        ->setIcon('target');
            }
        });

    echo $table->render($messages);
}
?>

<script>
$('.statusBar').each(function(index, element) {
    var refresh = setInterval(function () {
        var path = "<?php echo $gibbon->session->get('absoluteURL') ?>/modules/Messenger/messenger_manage_ajax.php";
        var postData = { gibbonLogID: $(element).data('id') };
        $(element).load(path, postData, function(responseText, textStatus, jqXHR) {
            if (responseText.indexOf('Sent') >= 0) {
                clearInterval(refresh);
            }
        });
    }, 3000);
});
</script>
