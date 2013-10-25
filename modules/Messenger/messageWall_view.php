<?
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

@session_start() ;

//Module includes (not needed because already called by index page)
//include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messageWall_view.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this page." ;
	print "</div>" ;
}
else {
	if ($_POST["date"]=="") {
		$date=date("d/m/Y") ;
	}
	else {
		$date=$_POST["date"] ;
	}
	
	$extra="" ;
	if ($date==date("d/m/Y")) {
		$extra="Today's Messages (" . $date . ")" ;
	}
	else {
		$extra="View Messages (" . $date . ")" ;
	}
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>$extra</div>" ;
	print "</div>" ;
	
	print "<div class='linkTop' style='height: 27px'>" ;
		 print "<div style='text-align: left; width: 40%; float: left;'>" ;
			print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Messenger/messageWall_view.php'>" ;
				print "<input name='date' maxlength=10 value='" . date("d/m/Y", (dateConvertToTimestamp(dateConvert($date))-(24*60*60))) . "' type='hidden' style='width:100px; float: none; margin-right: 4px;'>" ;
				?>
				<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='Previous Day'>
				<?	
			print "</form>" ;
			print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Messenger/messageWall_view.php'>" ;
				print "<input name='date' maxlength=10 value='" . date("d/m/Y", (dateConvertToTimestamp(dateConvert($date))+(24*60*60))) . "' type='hidden' style='width:100px; float: none; margin-right: 4px;'>" ;
				?>
				<input class='buttonLink' style='min-width: 30px; margin-top: 0px; float: left' type='submit' value='Next Day'>
				<?	
			print "</form>" ;
		print "</div>" ;
		print "<div style='width: 40%; float: right'>" ;
			print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Messenger/messageWall_view.php'>" ;
				print "<input name='date' id='date' maxlength=10 value='" . $date . "' type='text' style='width:100px; float: none; margin-right: 4px;'>" ;
				?>
				<script type="text/javascript">
					var date=new LiveValidation('date');
					date.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
					date.add(Validate.Presence);
				 </script>
				 <script type="text/javascript">
					$(function() {
						$( "#date" ).datepicker();
					});
				</script>
				<input style='min-width: 30px; margin-top: 0px; float: right' type='submit' value='Go'>
				<?	
			print "</form>" ;
		print "</div>" ;
	print "</div>" ;
	
	print getMessages($guid, $connection2, "print", dateConvert($date)) ;
}		
?>