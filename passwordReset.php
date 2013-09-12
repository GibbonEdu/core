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

print "<div class='trail'>" ;
print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > </div><div class='trailEnd'>Password Reset</div>" ;
print "</div>" ;
?>
<p>
	Enter your <? print $_SESSION[$guid]["systemName"] ?> username, or the email address you have listed in <? print $_SESSION[$guid]["systemName"] ?>, and press submit: your password will be reset and emailed to you. 
</p>
<?
$eidtReturn = $_GET["editReturn"] ;
$editReturnMessage ="" ;
$class="error" ;
if (!($eidtReturn=="")) {
	if ($eidtReturn=="fail0") {
		$editReturnMessage ="Email address not set." ;	
	}
	else if ($eidtReturn=="fail1") {
		$editReturnMessage ="Update failed due to database error." ;	
	}
	else if ($eidtReturn=="fail2") {
		$editReturnMessage ="Update failed due to incorrect or non-unique email address." ;	
	}
	else if ($eidtReturn=="fail3") {
		$editReturnMessage ="Failed to send update email." ;	
	}
	else if ($eidtReturn=="success0") {
		$editReturnMessage ="Password changed successfully, please check your email." ;	
		$class="success" ;
	}
	print "<div class='$class'>" ;
		print $editReturnMessage;
	print "</div>" ;
} 
?>

<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] ?>/passwordResetProcess.php">
	<table style="width: 100%">	
		<tr>
			<td class="right">
				<input name="email" id="email" type="text" style="width:100%">
			</td>
		</tr>
		<tr>
			<td class="right">
				<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
				<input type="submit" value="Submit">
			</td>
		</tr>
	</table>
</form>