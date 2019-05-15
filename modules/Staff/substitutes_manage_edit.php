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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Services\Format;
use Gibbon\Tables\Action;

if (isActionAccessible($guid, $connection2, '/modules/Staff/substitutes_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Substitutes'), 'substitutes_manage.php', ['search' => $search])
        ->add(__('Edit Substitute'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSubstituteID = $_GET['gibbonSubstituteID'] ?? '';
    $smsGateway = getSettingByScope($connection2, 'Messenger', 'smsGateway');

    if (empty($gibbonSubstituteID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(SubstituteGateway::class)->getByID($gibbonSubstituteID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $person = $container->get(UserGateway::class)->getByID($values['gibbonPersonID']);

    if (empty($person)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    echo "<div class='linkTop'>";
    if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_edit.php')) {
        echo (new Action('edit', __('Edit User')))
            ->setURL('/modules/User Admin/user_manage_edit.php')
            ->addParam('gibbonPersonID', $values['gibbonPersonID'])
            ->displayLabel()
            ->getOutput();
    }

    if ($search != '') {
        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/substitutes_manage.php&search=$search'>".__('Back to Search Results').'</a>  ';
    }
    echo '</div>';

    $form = Form::create('subsManage', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/substitutes_manage_editProcess.php?search=$search");

    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonSubstituteID', $gibbonSubstituteID);

    $form->addRow()->addHeading(__('Basic Information'));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Person'));
        $row->addSelectUsers('gibbonPersonID')
            ->placeholder()
            ->required()
            ->readonly()
            ->selected($values['gibbonPersonID']);

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $types = $container->get(SettingGateway::class)->getSettingByScope('Staff', 'substituteTypes');
    $types = array_filter(array_map('trim', explode(',', $types)));

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($types);

    $row = $form->addRow();
        $row->addLabel('priority', __('Priority'))->description(__('Higher priority substitutes appear first when booking coverage.'));
        $row->addSelect('priority')->fromArray(range(-9, 9))->required()->selected(0);
        
    $row = $form->addRow();
        $row->addLabel('details', __('Details'))->description(__('Additional information such as year group preference, language preference, etc.'));
        $row->addTextArea('details')->setRows(2)->maxlength(255);

    $form->addRow()->addHeading(__('Contact Information'));

    $row = $form->addRow();
        $row->addLabel('phone1Label', __('Phone').' 1');
        $phone = $row->addTextField('phone1')
            ->readonly()
            ->setValue(Format::phone($person['phone1'], $person['phone1CountryCode'], $person['phone1Type']));

    if (!empty($person['phone1']) && !empty($smsGateway)) {
        $phone->append(
            $form->getFactory()
                ->createButton(__('Test SMS'))
                ->addClass('testSMS alignRight')
                ->setTabIndex(-1)
                ->getOutput()
        );
    }

    $row = $form->addRow();
        $row->addLabel('emailLabel', __('Email'));
        $row->addTextField('email')->readonly()->setValue($person['email']);


    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

     $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
?>

<script>
$(document).ready(function() {
    $('.testSMS').on('click', function() {
        if (confirm("<?php echo __('Test SMS').'?'; ?>")) {
            $.ajax({
                url: './modules/Staff/substitutes_manage_edit_smsAjax.php',
                data: {
                    from: "<?php echo $_SESSION[$guid]['preferredName'].' '.$_SESSION[$guid]['surname']; ?>",    
                    phoneNumber: "<?php echo $person['phone1CountryCode'].$person['phone1']; ?>"
                },
                type: 'POST',
                success: function(data) {
                    alert(data);
                }
            });
        }
    });
}) ;
</script>
