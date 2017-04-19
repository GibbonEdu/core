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

include '../../functions.php';
include '../../config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

$gibbonResourceID = $_GET['gibbonResourceID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/resources_manage_edit.php&gibbonResourceID=$gibbonResourceID&search=".$_GET['search'];
$time = time();

if (isActionAccessible($guid, $connection2, '/modules/Resources/resources_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if (empty($_POST)) {
        $URL .= '&return=warning1';
        header("Location: {$URL}");
    } else {
        $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
        if ($highestAction == false) {
            $URL .= "&return=error0$params";
            header("Location: {$URL}");
        } else {
            //Proceed!
            //Check if school year specified
            if ($gibbonResourceID == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                try {
                    if ($highestAction == 'Manage Resources_all') {
                        $data = array('gibbonResourceID' => $gibbonResourceID);
                        $sql = 'SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonResourceID=:gibbonResourceID ORDER BY timestamp DESC';
                    } elseif ($highestAction == 'Manage Resources_my') {
                        $data = array('gibbonResourceID' => $gibbonResourceID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = 'SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonResource.gibbonPersonID=:gibbonPersonID AND gibbonResourceID=:gibbonResourceID ORDER BY timestamp DESC';
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() != 1) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                } else {
                    $row = $result->fetch();

                    $type = $_POST['type'];
                    if ($type == 'File') {
                        $content = $_FILES['file'];
                    } elseif ($type == 'HTML') {
                        $content = $_POST['html'];
                    } elseif ($type == 'Link') {
                        $content = $_POST['link'];
                    }
                    $name = $_POST['name'];
                    $category = $_POST['category'];
                    $purpose = $_POST['purpose'];
                    $tags = strtolower($_POST['tags']);
                    $gibbonYearGroupIDList = '';
                    for ($i = 0; $i < $_POST['count']; ++$i) {
                        if (isset($_POST["gibbonYearGroupIDCheck$i"])) {
                            if ($_POST["gibbonYearGroupIDCheck$i"] == 'on') {
                                $gibbonYearGroupIDList = $gibbonYearGroupIDList.$_POST["gibbonYearGroupID$i"].',';
                            }
                        }
                    }
                    $gibbonYearGroupIDList = substr($gibbonYearGroupIDList, 0, (strlen($gibbonYearGroupIDList) - 1));
                    $description = $_POST['description'];

                    if (($type != 'File' and $type != 'HTML' and $type != 'Link') or (is_null($content) and $type != 'File') or $name == '' or $category == '' or $tags == '') {
                        $URL .= '&return=error3';
                        header("Location: {$URL}");
                    } else {
                        if ($type == 'File') {
                            $attachment = '';
                            if ($_FILES['file']['tmp_name'] != '') {
                                //Check for folder in uploads based on today's date
                                $path = $_SESSION[$guid]['absolutePath'];
                                if (is_dir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time)) == false) {
                                    mkdir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time), 0777, true);
                                }
                                $unique = false;
                                $count = 0;
                                while ($unique == false and $count < 100) {
                                    $suffix = randomPassword(16);
                                    $attachment = 'uploads/'.date('Y', $time).'/'.date('m', $time).'/'.preg_replace('/[^a-zA-Z0-9]/', '', $name)."_$suffix".strrchr($_FILES['file']['name'], '.');
                                    if (!(file_exists($path.'/'.$attachment))) {
                                        $unique = true;
                                    }
                                    ++$count;
                                }

                                if (!(move_uploaded_file($_FILES['file']['tmp_name'], $path.'/'.$attachment))) {
                                    $URL .= '&return=warning1';
                                    header("Location: {$URL}");
                                }
                            }
                            if ($attachment == '') {
                                $content = $row['content'];
                            } else {
                                $content = $attachment;
                            }
                        }

                        //Deal with tags
                        try {
                            $sql = 'LOCK TABLES gibbonResourceTag WRITE';
                            $result = $connection2->query($sql);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        //Update old tag counts
                        $partialFail = false;
                        $tags = explode(',', $row['tags']);
                        foreach ($tags as $tag) {
                            if (trim($tag) != '') {
                                try {
                                    $dataTags = array('tag' => trim($tag));
                                    $sqlTags = 'SELECT * FROM gibbonResourceTag WHERE tag=:tag';
                                    $resultTags = $connection2->prepare($sqlTags);
                                    $resultTags->execute($dataTags);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                                if ($resultTags->rowCount() == 1) {
                                    $rowTags = $resultTags->fetch();
                                    try {
                                        $dataTag = array('count' => ($rowTags['count'] - 1), 'tag' => trim($tag));
                                        $sqlTag = 'UPDATE gibbonResourceTag SET count=:count WHERE tag=:tag';
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

                        //Update new tag counts
                        $tags = explode(',', $_POST['tags']);
                        $tagList = '';
                        foreach ($tags as $tag) {
                            if (trim($tag) != '') {
                                $tagList .= trim($tag).",";
                                try {
                                    $dataTags = array('tag' => trim($tag));
                                    $sqlTags = 'SELECT * FROM gibbonResourceTag WHERE tag=:tag';
                                    $resultTags = $connection2->prepare($sqlTags);
                                    $resultTags->execute($dataTags);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                                if ($resultTags->rowCount() == 1) {
                                    $rowTags = $resultTags->fetch();
                                    try {
                                        $dataTag = array('count' => ($rowTags['count'] + 1), 'tag' => trim($tag));
                                        $sqlTag = 'UPDATE gibbonResourceTag SET count=:count WHERE tag=:tag';
                                        $resultTag = $connection2->prepare($sqlTag);
                                        $resultTag->execute($dataTag);
                                    } catch (PDOException $e) {
                                        $partialFail = true;
                                    }
                                } elseif ($resultTags->rowCount() == 0) {
                                    try {
                                        $dataTag = array('tag' => trim($tag));
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
                    }
                    //Unlock module table
                    try {
                        $sql = 'UNLOCK TABLES';
                        $result = $connection2->query($sql);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Write to database
                    try {
                        $data = array('type' => $type, 'content' => $content, 'name' => $name, 'category' => $category, 'purpose' => $purpose, 'tags' => substr($tagList, 0, -1), 'gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'description' => $description, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonResourceID' => $gibbonResourceID);
                        $sql = 'UPDATE gibbonResource SET type=:type, content=:content, name=:name, category=:category, purpose=:purpose, tags=:tags, gibbonYearGroupIDList=:gibbonYearGroupIDList, description=:description, gibbonPersonID=:gibbonPersonID WHERE gibbonResourceID=:gibbonResourceID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
