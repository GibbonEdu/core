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

@session_start();

use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/i18n_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Language Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<p>';
    echo __($guid, 'Inactive languages are not yet ready for use within the system as they are still under development. They cannot be set to default, nor selected by users.');
    echo '</p>';

    $form = Form::create('i18n_manage', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/i18n_manageProcess.php');
    
    $form->setClass('fullWidth');
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    
    $row = $form->addRow()->setClass('heading head');
        $row->addContent('Name');
        $row->addContent('Code');
        $row->addContent('Active');
        $row->addContent('Maintainer');
        $row->addContent('Default');

    try {
        $data = array();
        $sql = 'SELECT * FROM gibboni18n ORDER BY name';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if (!$result || $result->rowCount() == 0) {
        $form->addRow()->addAlert('There are no records to display.', 'error');
    } else {
        while ($i18n = $result->fetch()) {
            $class = ($i18n['active'] == 'N')? 'error' : '';

            $row = $form->addRow()->addClass($class);
            $row->addContent($i18n['name'])->wrap('<b>', '</b>');
            $row->addContent($i18n['code']);
            $row->addContent(ynExpander($guid, $i18n['active']));

            $maintainer = $row->addContent($i18n['maintainerName']);
            if (!empty($i18n['maintainerWebsite'])) {
                $maintainer->wrap("<a href='".$i18n['maintainerWebsite']."'>", '</a>');
            }

            if ($i18n['active'] == 'Y') {
                $checked = ($i18n['systemDefault'] == 'Y')? $i18n['gibboni18nID'] : '';

                $row->addRadio('gibboni18nID')
                    ->setClass('')
                    ->fromArray(array($i18n['gibboni18nID'] => ''))
                    ->checked($checked);
            } else {
                $row->addContent();
            }
        }
    }
    
    $form->addRow()->addSubmit();
    
    echo $form->getOutput();
}
