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

print "<div class='trail'>" ;
print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > </div><div class='trailEnd'>" . __($guid, "Password Reset") . "</div>" ;
print "</div>" ;
?>
<p>
	<?php print sprintf(__($guid, 'Enter your %1$s username, or the email address you have listed in the system, and press submit: your password will be reset and emailed to you.'), $_SESSION[$guid]["systemName"]) ; ?>
</p>
<?php
if (isset($_GET["editReturn"])) { $editReturn=$_GET["editReturn"] ; } else { $editReturn="" ; }
$editReturnMessage="" ;
$class="error" ;
if (!($editReturn=="")) {
	if ($editReturn=="fail0") {
		$editReturnMessage="Email address not set." ;	
	}
	else if ($editReturn=="fail1") {
		$editReturnMessage=__($guid, "Your request failed due to a database error.") ;	
	}
	else if ($editReturn=="fail2") {
		$editReturnMessage="Your request failed due to incorrect or non-unique email address." ;	
	}
	else if ($editReturn=="fail3") {
		$editReturnMessage="Failed to send update email." ;	
	}
	else if ($editReturn=="success0") {
		$editReturnMessage="Password changed successfully, please check your email." ;	
		$class="success" ;
	}
	print "<div class='$class'>" ;
		print $editReturnMessage;
	print "</div>" ;
} 
?>

<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] ?>/passwordResetProcess.php">
	<table cellspacing='0' style="width: 100%">	
		<tr>
			<td class="right">
				<input name="email" id="email" type="text" style="width:100%">
			</td>
		</tr>
		<tr>
			<td class="right">
				<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
				<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
			</td>
		</tr>
	</table>
</form>