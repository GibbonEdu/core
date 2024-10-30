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
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/services_manage.php') == false) {
    // Access denied
    echo Format::alert(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Manage Services'));

    $form = Form::create('manageServices', $session->get('absoluteURL').'/modules/'.$session->get('module').'/services_manageProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));

    // VALUE ADDED
    $form->addRow()->addHeading('gibbonedu.com Services', __('gibbonedu.com Services'));

    $settingGateway = $container->get(SettingGateway::class);

    $settingName = $settingGateway->getSettingByScope('System', 'gibboneduComOrganisationName', true);
    $row = $form->addRow();
        $row->addLabel($settingName['name'], __($settingName['nameDisplay']))
            ->description(__($settingName['description']));
        $row->addTextField($settingName['name'])->setValue($settingName['value']);

    $settingKey = $settingGateway->getSettingByScope('System', 'gibboneduComOrganisationKey', true);
    $row = $form->addRow();
        $row->addLabel($settingKey['name'], __($settingKey['nameDisplay']))
            ->description(__($settingKey['description']));
        $row->addTextField($settingKey['name'])->setValue($settingKey['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    if (!empty($settingName['value']) && !empty($settingKey['value'])) {
        echo '<h3>';
        echo __('Extended Services');
        echo '</h3>';

        echo '<div id="servicesCheck">';
        echo "<div style='width: 100%; text-align: center'>";
        echo '<img style="margin: 10px 0 5px 0" src="'.$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName').'/img/loading.gif" alt="Loading"/><br/>';
        echo '</div>';
        echo '</div>';
    }
}

?>
<script type='text/javascript'>
    $(document).ready(function(){
        var path = '<?php echo $session->get('absoluteURL').'/modules/System%20Admin/services_manage_ajax.php'; ?>';
        var orgName = $('#gibboneduComOrganisationName').val();
        var orgKey = $('#gibboneduComOrganisationKey').val();

        if (orgName && orgKey) {
            $('#servicesCheck').load(path, {
                'address': '<?php echo $session->get('address'); ?>',
                'gibboneduComOrganisationName': orgName, 
                'gibboneduComOrganisationKey': orgKey
            });
        }
    });
</script>
