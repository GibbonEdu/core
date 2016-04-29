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
$returns=array() ;
$returns["error0"] = __($guid, "Email address not set.") ;
$returns["error4"] = __($guid, "Your request failed due to incorrect or non-existent or non-unique email address.") ;
$returns["error3"] = __($guid, "Failed to send update email.") ;
$returns["success0"] = __($guid, "Password changed successfully, please check your email.") ;
if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, $returns); }

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