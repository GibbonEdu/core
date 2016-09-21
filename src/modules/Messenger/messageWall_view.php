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

namespace Module\Messenger ;

use Gibbon\core\view ;
use Gibbon\core\trans ;

if (! $this instanceof view) die();

if ($this->view->getSecurity()->isActionAccessible('/modules/Messenger/messageWall_view.php')) {
    $date = ! isset($_POST['date']) ? date($this->session->get('i18n.dateFormatPHP')) :  $_POST['date'] ;

    $extra = $date == date($this->session->get('i18n.dateFormatPHP')) ? array("Today's Messages (%s)", array($date)) : array('View Messages ($s)', array($date)) ;
	
	$trail = $this->initiateTrail();
	$trail->trailEnd = $extra;
	$trail->render($this);
	

    echo "<div class='linkTop' style='height: 27px'>";
    echo "<div style='text-align: left; width: 40%; float: left;'>";
    echo "<form method='post' action='".$this->session->get('absoluteURL')."/index.php?q=/modules/Messenger/messageWall_view.php'>";
    	echo "<input name='date' maxlength=10 value='".date($this->session->get('i18n.dateFormatPHP'), (dateConvertToTimestamp(dateConvert($guid, $date)) - (24 * 60 * 60)))."' type='hidden' style='width:100px; float: none; margin-right: 4px;'>"; ?>
		<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='Previous Day'>
		<?php	
	echo '</form>';
    echo "<form method='post' action='".$this->session->get('absoluteURL')."/index.php?q=/modules/Messenger/messageWall_view.php'>";
    	echo "<input name='date' maxlength=10 value='".date($this->session->get('i18n.dateFormatPHP'), (dateConvertToTimestamp(dateConvert($guid, $date)) + (24 * 60 * 60)))."' type='hidden' style='width:100px; float: none; margin-right: 4px;'>"; ?>
		<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='Next Day'>
		<?php	
	echo '</form>';
    echo '</div>';
    echo "<div style='width: 40%; float: right'>";
    echo "<form method='post' action='".$this->session->get('absoluteURL')."/index.php?q=/modules/Messenger/messageWall_view.php'>";
    echo "<input name='date' id='date' maxlength=10 value='".$date."' type='text' style='width:100px; float: none; margin-right: 4px;'>"; 
	$pattern = $this->session->isEmpty('i18n.dateFormatRegEx') ? "^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$" : $this->session->get('i18n.dateFormatRegEx');
	$format = $this->session->isEmpty('i18n.dateFormat') ? 'dd/mm/yyyy' : $this->session->get('i18n.dateFormat');
	$this->addScript('
		<script type="text/javascript">
			var date=new LiveValidation("date");
			date.add( Validate.Format, {pattern: '.$pattern.', failureMessage: "Use '.$format.'" } ); 
			date.add(Validate.Presence);
		</script>
		<script type="text/javascript">
			$(function() {
				$( "#date" ).datepicker();
			});
		</script>'); ?>
		<input style='min-width: 30px; margin-top: 0px; float: right' type='submit' value='<?php echo __($guid, 'Go') ?>'>
		<?php	
	echo '</form>';
    echo '</div>';
    echo '</div>';

    echo getMessages($guid, $connection2, 'print', dateConvert($guid, $date));
}
