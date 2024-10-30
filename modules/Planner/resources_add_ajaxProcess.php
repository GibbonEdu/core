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

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['*link' => 'URL']);

$time = time();

if (isActionAccessible($guid, $connection2, '/modules/Planner/resources_manage_add.php') == false) {
    echo "<span style='font-weight: bold; color: #ff0000'>";
    echo __('Your request failed because you do not have access to this action.');
    echo '</span>';
    exit();
} else {
    if (empty($_POST)) {
        echo "<span style='font-weight: bold; color: #ff0000'>";
        echo __('Your request failed due to an attachment error.');
        echo '</span>';
        exit();
    } else {
        //Proceed!
        $id = $_POST['id'] ?? '';
        $type = $_POST[$id.'type'] ?? '';
        if ($type == 'File') {
            $content = $_FILES[$id.'file'] ?? '';
        } elseif ($type == 'Link') {
            $content = $_POST[$id.'link'] ?? '';
        }
        $name = $_POST[$id.'name'] ?? '';
        $name = preg_replace('/[^a-zA-Z0-9\-\_ ]/', '', $name);

        $category = $_POST[$id.'category'] ?? '';
        $purpose = $_POST[$id.'purpose'] ?? '';
        $tags = strtolower($_POST[$id.'tags'] ?? '');
        $gibbonYearGroupIDList = implode(',', $_POST[$id.'gibbonYearGroupID'] ?? []);
        $description = $_POST[$id.'description'] ?? '';

        if (($type != 'File' and $type != 'Link') or is_null($content) or $name == '' or $category == '' or $tags == '' or $id == '') {
            echo "<span style='font-weight: bold; color: #ff0000'>";
            echo __('Your request failed because your inputs were invalid.');
            echo '</span>';
            exit();
        } else {
            if ($type == 'File') {
                $fileUploader = new Gibbon\FileUploader($pdo, $session);

                if (!empty($_FILES[$id.'file']['tmp_name'])) {
                    $file = (isset($_FILES[$id.'file']))? $_FILES[$id.'file'] : null;

                    // Upload the file, return the /uploads relative path
                    $attachment = $fileUploader->uploadFromPost($file, $name);

                    if (empty($attachment)) {
                        echo "<span style='font-weight: bold; color: #ff0000'>";
                            echo __('Your request failed due to an attachment error.');
                            echo ' '.$fileUploader->getLastError();
                        echo '</span>';
                        exit();
                    } else {
                        $content = $attachment;
                    }
                }
            }

            //Update tag counts
            $partialFail = false;
            $tags = explode(',', $_POST[$id.'tags'] ?? '');
            $tagList = [];
            foreach ($tags as $tag) {
                $tag = trim(preg_replace('/[^a-zA-Z0-9\-\_ ]/', '', $tag));
                if (!empty($tag)) {
                    $tagList[] = $tag;
                    try {
                        $dataTags = array('tag' => $tag);
                        $sqlTags = 'SELECT * FROM gibbonResourceTag WHERE tag=:tag';
                        $resultTags = $connection2->prepare($sqlTags);
                        $resultTags->execute($dataTags);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                    if ($resultTags->rowCount() == 1) {
                        $rowTags = $resultTags->fetch();
                        try {
                            $dataTag = array('count' => ($rowTags['count'] + 1), 'tag' => $tag);
                            $sqlTag = 'UPDATE gibbonResourceTag SET count=:count WHERE tag=:tag';
                            $resultTag = $connection2->prepare($sqlTag);
                            $resultTag->execute($dataTag);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    } elseif ($resultTags->rowCount() == 0) {
                        try {
                            $dataTag = array('tag' => $tag);
                            $sqlTag = 'INSERT INTO gibbonResourceTag SET tag=:tag, count=1';
                            $resultTag = $connection2->prepare($sqlTag);
                            $resultTag->execute($dataTag);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    } else {
                        $partialFail = true;
                    }
                }
            }

            //Write to database
            try {
                $data = array('type' => $type, 'content' => $content, 'name' => $name, 'category' => $category, 'purpose' => $purpose, 'tags' => implode(',', $tagList), 'gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'description' => $description, 'gibbonPersonID' => $session->get('gibbonPersonID'), 'timestamp' => date('Y-m-d H:i:s', $time));
                $sql = 'INSERT INTO gibbonResource SET type=:type, content=:content, name=:name, category=:category, purpose=:purpose, tags=:tags, gibbonYearGroupIDList=:gibbonYearGroupIDList, description=:description, gibbonPersonID=:gibbonPersonID, timestamp=:timestamp';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<span style='font-weight: bold; color: #ff0000'>";
                echo '</span>';
                exit();
            }

            if ($partialFail == true) {
                echo "<span style='font-weight: bold; color: #ff0000'>";
                echo __('Your request was successful, but some data was not properly saved.');
                echo '</span>';
            } else {
                $html = '';
                $extension = '';
                if ($type == 'Link') {
                    $extension = strrchr($content, '.');
                    if (strcasecmp($extension, '.gif') == 0 or strcasecmp($extension, '.jpg') == 0 or strcasecmp($extension, '.jpeg') == 0 or strcasecmp($extension, '.png') == 0) {
                        $html = "<a target='_blank' style='font-weight: bold' href='".$content."'><img class='resource' style='max-width: 100%' src='".$content."'></a>";
                    } else {
                        $html = "<a target='_blank' style='font-weight: bold' href='".$content."'>".$name.'</a>';
                    }
                } elseif ($type == 'File') {
                    $extension = strrchr($content, '.');
                    if (strcasecmp($extension, '.gif') == 0 or strcasecmp($extension, '.jpg') == 0 or strcasecmp($extension, '.jpeg') == 0 or strcasecmp($extension, '.png') == 0) {
                        $html = "<a target='_blank' style='font-weight: bold' href='".$session->get('absoluteURL').'/'.$content."'><img class='resource' style='max-width: 100%' src='".$session->get('absoluteURL').'/'.$content."'></a>";
                    } else {
                        $html = "<a target='_blank' style='font-weight: bold' href='".$session->get('absoluteURL').'/'.$content."'>".$name.'</a>';
                    }
                }
                echo $html;
            }
        }
    }
}
