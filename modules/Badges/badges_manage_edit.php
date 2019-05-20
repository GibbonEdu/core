<?php

use Gibbon\Forms\Form;
use Gibbon\FileUploader;
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//Module includes
include './modules/Badges/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Badges/badges_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo 'You do not have access to this action.';
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs
            ->add(__('Manage Badges'),'badges_manage.php')
            ->add(__('Edit Badges'));    

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $badgesBadgeID = $_GET['badgesBadgeID'];
    if ($badgesBadgeID == '') { echo "<div class='error'>";
        echo 'You have not specified a policy.';
        echo '</div>';
    } else {
        try {
            $data = array('badgesBadgeID' => $badgesBadgeID);
            $sql = 'SELECT * FROM badgesBadge WHERE badgesBadgeID=:badgesBadgeID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo 'The selected policy does not exist.';
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            if ($_GET['search'] != '' || $_GET['category'] != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$gibbon->session->get('absoluteURL','').'/index.php?q=/modules/Badges/badges_manage.php&search='.$_GET['search'].'&category='.$_GET['category']."'>Back to Search Results</a>";
                echo '</div>';
            }

            $form = Form::create('badges', $gibbon->session->get('absoluteURL','').'/modules/'.$gibbon->session->get('module').'/badges_manage_editProcess.php?badgesBadgeID='.$badgesBadgeID.'&search='.$_GET['search'].'&category='.$_GET['category']);
    
            $form->addHiddenValue('address', $gibbon->session->get('address'));

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->required()->maxLength(10);

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->required();
            
            $categories = getSettingByScope($connection2, 'Badges', 'badgeCategories');
            $categories = !empty($categories) ? array_map('trim', explode(',', $categories)) : [];
            $row = $form->addRow();
                $row->addLabel('category', __('Category'));
                $row->addSelect('category')->fromArray($categories)->required()->placeholder();

            $row = $form->addRow();
                $row->addLabel('description', __('Description'));
                $row->addTextArea('description');
            
            $fileUploader = new FileUploader($pdo, $gibbon->session);
            
            $row = $form->addRow();
                $row->addLabel('file', __('Logo'))->description(__('240px x 240px'));
                $row->addFileUpload('file')
                    ->accepts($fileUploader->getFileExtensions('Graphics/Design'))
                    ->setAttachment('logo', $gibbon->session->get('absoluteURL',''), $values['logo']);
            
            $row = $form->addRow();
                $row->addLabel('logoLicense', __('Logo License/Credits'));
                $row->addTextArea('logoLicense');
                
            $row = $form->addRow();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
