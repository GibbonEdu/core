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
use Gibbon\Data\Validator;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$time = time();

if (!$session->has('gibbonPersonID')) {
    echo Format::alert(__('You do not have access to this action.'));
    exit();
} else {
    if (empty($_POST) or empty($_FILES)) {
        echo "<span style='font-weight: bold; color: #ff0000'>";
        echo __('Your request failed due to an attachment error.');
        echo '</span>';
        exit();
    } else {
        //Proceed!
        $id = $_POST['id'] ?? '';
        $imagesAsLinks = !empty($_POST['imagesAsLinks']) && $_POST['imagesAsLinks'] == 'Y';

        if ($id == '') {
            echo "<span style='font-weight: bold; color: #ff0000'>";
            echo __('Your request failed because your inputs were invalid.');
            echo '</span>';
            exit();
        } else {
            //Check if multiple files
            $multiple = false;
            $multipleCount = 0;
            for ($i = 1; $i < 5; ++$i) {
                if (isset($_FILES[$id.'file'.$i])) {
                    ++$multipleCount;
                }
            }
            if ($multipleCount > 1) {
                $multiple = true;
            }

            $fileUploader = new Gibbon\FileUploader($pdo, $session);

            //Insert files
            for ($i = 1; $i < 5; ++$i) {
                $html = '';
                if (isset($_FILES[$id.'file'.$i])) {
                    $file = $_FILES[$id.'file'.$i];

                    // Upload the file, return the /uploads relative path
                    $attachment = $fileUploader->uploadFromPost($file);

                    if (empty($attachment)) {
                        echo "<span style='font-weight: bold; color: #ff0000'>";
                            echo __('Your request failed due to an attachment error.');
                            echo ' '.$fileUploader->getLastError();
                        echo '</span>';
                        exit();
                    } else {
                        $extension = strrchr($attachment, '.');
                        $name = mb_substr(basename($file['name']), 0, mb_strrpos(basename($file['name']), '.'));
                        $name = preg_replace('[/~`!@%#$%^&*()+={}\[\]|\\:;"\'<>,.?\/]', '', $name);

                        if ((strcasecmp($extension, '.gif') == 0 or strcasecmp($extension, '.jpg') == 0 or strcasecmp($extension, '.jpeg') == 0 or strcasecmp($extension, '.png') == 0) and $imagesAsLinks == false) {
                            $html = "<a target='_blank' style='font-weight: bold' href='".$session->get('absoluteURL').'/'.$attachment."'><img class='resource' style='max-width: 100%' src='".$session->get('absoluteURL').'/'.$attachment."'></a>";
                        } else {
                            $html = "<a target='_blank' style='font-weight: bold' href='".$session->get('absoluteURL').'/'.$attachment."'>".$name.'</a>';
                        }
                    }
                }
                if ($multiple) {
                    echo '<br/>';
                }
                echo $html;
            }
        }
    }
}
