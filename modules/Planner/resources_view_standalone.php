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

//Gibbon system-wide includes
include '../../gibbon.php';

echo "<link rel='stylesheet' type='text/css' href='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/css/main.css' />";
?>

<div id="wrap">
	<div id="header">
		<div id="header-left">
			<a href='<?php echo $_SESSION[$guid]['absoluteURL'] ?>'><img height='100px' width='400px' class="logo" alt="Logo" title="Logo" src="<?php echo $_SESSION[$guid]['absoluteURL'].'/'.$_SESSION[$guid]['organisationLogo']; ?>"/></a>
		</div>
		<div id="header-right">

		</div>
	</div>
	<div id="content-wrap">
		<div id="content">
			<?php
            if (isActionAccessible($guid, $connection2, '/modules/Planner/resources_view_full.php') == false) {
                //Acess denied
                echo "<div class='error'>";
                echo __($guid, 'Your request failed because you do not have access to this action.');
                echo '</div>';
            } else {
                //Proceed!
                //Get class variable
                $gibbonResourceID = $_GET['gibbonResourceID'];
                if ($gibbonResourceID == '') {
                    echo "<div class='warning'>";
                    echo 'Resource has not been specified .';
                    echo '</div>';
                }
                //Check existence of and access to this resource.
                else {
                    try {
                        $data = array('gibbonResourceID' => $gibbonResourceID);
                        $sql = 'SELECT * FROM gibbonResource WHERE gibbonResourceID=:gibbonResourceID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($result->rowCount() != 1) {
                        echo "<div class='warning'>";
                        echo __($guid, 'The specified record does not exist.');
                        echo '</div>';
                    } else {
                        $row = $result->fetch();

                        echo '<h1>';
                        echo $row['name'];
                        echo '</h1>';

                        echo $row['content'];
                    }
                }
            }
            ?>
		</div>
		<div id="sidebar">
		</div>
	</div>
	<div id="footer">
		<a href="https://gibbonedu.org">Gibbon</a> v<?php echo $version ?> | &#169; 2011, <a href="http://rossparker.org">Ross Parker</a> at <a href="http://www.ichk.edu.hk">International College Hong Kong</a> | Created under the <a href="https://www.gnu.org/licenses/gpl.html">GNU General Public License</a>
	</div>
</div>
