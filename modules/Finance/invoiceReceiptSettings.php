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

session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/Finance/invoiceReceiptSettings.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Invoice & Receipt Settings</div>" ;
	print "</div>" ;
	
	$updateReturn = $_GET["updateReturn"] ;
	$updateReturnMessage ="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage ="Update failed because you do not have access to this action." ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage ="Update failed because a required parameter was not set." ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage ="Update of one or more fields failed due to a database error." ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage ="Update failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage ="Update was successful." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	?>
	
	<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/invoiceReceiptSettingsProcess.php" ?>">
		<table style="width: 100%">	
			<tr><td style="width: 30%"></td><td></td></tr>
			<tr>
				<td colspan=2> 
					<h3 class='top'>General Settings</h3>
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Finance' AND name='email'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?> *</b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<input name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" maxlength=255 value="<? print $row["value"] ?>" type="text" style="width: 300px">
					<script type="text/javascript">
						var <? print $row["name"] ?> = new LiveValidation('<? print $row["name"] ?>');
						<? print $row["name"] ?>.add(Validate.Email);
						<? print $row["name"] ?>.add(Validate.Presence);
					 </script>
				</td>
			</tr>
			
			<tr>
				<td colspan=2> 
					<h3>Invoices</h3>
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Finance' AND name='invoiceText'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<textarea name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" type="text" style="width: 300px" rows=4><? print $row["value"] ?></textarea>
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Finance' AND name='invoiceNotes'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<textarea name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" type="text" style="width: 300px" rows=4><? print $row["value"] ?></textarea>
				</td>
			</tr>
			
			<tr>
				<td colspan=2> 
					<h3>Receipts</h3>
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Finance' AND name='receiptText'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<textarea name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" type="text" style="width: 300px" rows=4><? print $row["value"] ?></textarea>
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Finance' AND name='receiptNotes'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<textarea name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" type="text" style="width: 300px" rows=4><? print $row["value"] ?></textarea>
				</td>
			</tr>
			
			<tr>
				<td colspan=2> 
					<h3>Reminders</h3>
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Finance' AND name='reminder1Text'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<textarea name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" type="text" style="width: 300px" rows=4><? print $row["value"] ?></textarea>
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Finance' AND name='reminder2Text'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<textarea name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" type="text" style="width: 300px" rows=4><? print $row["value"] ?></textarea>
				</td>
			</tr>
			<tr>
				<?
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Finance' AND name='reminder3Text'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td> 
					<b><? print $row["nameDisplay"] ?></b><br/>
					<span style="font-size: 90%"><i><? print $row["description"] ?></i></span>
				</td>
				<td class="right">
					<textarea name="<? print $row["name"] ?>" id="<? print $row["name"] ?>" type="text" style="width: 300px" rows=4><? print $row["value"] ?></textarea>
				</td>
			</tr>
			
			<tr>
				<td class="right" colspan=2>
					<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
					<input type="reset" value="Reset"> <input type="submit" value="Submit">
				</td>
			</tr>
			<tr>
				<td class="right" colspan=2>
					<span style="font-size: 90%"><i>* denotes a required field</i></span>
				</td>
			</tr>
		</table>
	</form>
<?
}
?>