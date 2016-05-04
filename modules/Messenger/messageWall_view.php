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

//Module includes (not needed because already called by index page)
//include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, '/modules/Messenger/messageWall_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    if (isset($_POST['date']) == false) {
        $date = date($_SESSION[$guid]['i18n']['dateFormatPHP']);
    } else {
        $date = $_POST['date'];
    }

    $extra = '';
    if ($date == date($_SESSION[$guid]['i18n']['dateFormatPHP'])) {
        $extra = __($guid, "Today's Messages").' ('.$date.')';
    } else {
        $extra = __($guid, 'View Messages').' ('.$date.')';
    }
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>$extra</div>";
    echo '</div>';

    echo "<div class='linkTop' style='height: 27px'>";
    echo "<div style='text-align: left; width: 40%; float: left;'>";
    echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Messenger/messageWall_view.php'>";
    	echo "<input name='date' maxlength=10 value='".date($_SESSION[$guid]['i18n']['dateFormatPHP'], (dateConvertToTimestamp(dateConvert($guid, $date)) - (24 * 60 * 60)))."' type='hidden' style='width:100px; float: none; margin-right: 4px;'>"; ?>
		<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='Previous Day'>
		<?php	
	echo '</form>';
    echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Messenger/messageWall_view.php'>";
    	echo "<input name='date' maxlength=10 value='".date($_SESSION[$guid]['i18n']['dateFormatPHP'], (dateConvertToTimestamp(dateConvert($guid, $date)) + (24 * 60 * 60)))."' type='hidden' style='width:100px; float: none; margin-right: 4px;'>"; ?>
		<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='Next Day'>
		<?php	
	echo '</form>';
    echo '</div>';
    echo "<div style='width: 40%; float: right'>";
    echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Messenger/messageWall_view.php'>";
    echo "<input name='date' id='date' maxlength=10 value='".$date."' type='text' style='width:100px; float: none; margin-right: 4px;'>"; ?>
		<script type="text/javascript">
			var date=new LiveValidation('date');
			date.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
					echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
				} else {
					echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
				}
					?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
					echo 'dd/mm/yyyy';
				} else {
					echo $_SESSION[$guid]['i18n']['dateFormat'];
				}
				?>." } ); 
			date.add(Validate.Presence);
		</script>
		<script type="text/javascript">
			$(function() {
				$( "#date" ).datepicker();
			});
		</script>
		<input style='min-width: 30px; margin-top: 0px; float: right' type='submit' value='<?php echo __($guid, 'Go') ?>'>
		<?php	
	echo '</form>';
    echo '</div>';
    echo '</div>';

    echo getMessages($guid, $connection2, 'print', dateConvert($guid, $date));
}
?>