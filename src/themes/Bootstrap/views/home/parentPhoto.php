<?php
use Gibbon\core\trans ;

$output = false ;

$category = $this->getSecurity()->getRoleCategory($this->session->get("gibbonRoleIDCurrent")) ;
if ($category=="Parent") {
	$output.="<h2 style='margin-bottom: 10px'>" ;
		$output.="Profile Photo" ;
	$output.="</h2>" ;

	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="fail1") {
			$deleteReturnMessage=trans::__( "Your request failed because your inputs were invalid.") ;
		}
		else if ($deleteReturn=="fail2") {
			$deleteReturnMessage=trans::__( "Your request failed due to a database error.") ;
		}
		else if ($deleteReturn=="success0") {
			$deleteReturnMessage=trans::__( "Your request was completed successfully.") ;
			$class="success" ;
		}
		$output.="<div class='$class'>" ;
			$output.=$deleteReturnMessage;
		$output.="</div>" ;
	}

	if (isset($_GET["uploadReturn"])) { $uploadReturn=$_GET["uploadReturn"] ; } else { $uploadReturn="" ; }
	$uploadReturnMessage="" ;
	$class="error" ;
	if (!($uploadReturn=="")) {
		if ($uploadReturn=="fail1") {
			$uploadReturnMessage=trans::__( "Your request failed because your inputs were invalid.") ;
		}
		else if ($uploadReturn=="fail2") {
			$uploadReturnMessage=trans::__( "Your request failed due to a database error.") ;
		}
		else if ($uploadReturn=="success0") {
			$uploadReturnMessage=trans::__( "Your request was completed successfully.") ;
			$class="success" ;
		}
		$output.="<div class='$class'>" ;
			$output.=$uploadReturnMessage;
		$output.="</div>" ;
	}

	if ($this->session->get("image_240")=="") { //No photo, so show uploader
		$output.="<p>" ;
			$output.=trans::__( "Please upload a passport size photo to use as a profile picture.") . " " . trans::__( '240px by 320px') . "." ;
		$output.="</p>" ;
		$output.="<form method='post' action='" . $this->session->get("absoluteURL") . "/index_parentPhotoUploadProcess.php?gibbonPersonID=" . $this->session->get("gibbonPersonID") . "' enctype='multipart/form-data'>" ;
			$output.="<table class='smallIntBorder' cellspacing='0' style='width: 100%; margin: 0px 0px'>" ;
				$output.="<tr>" ;
					$output.="<td style='vertical-align: top'>" ;
						$output.="<input type=\"file\" name=\"file1\" id=\"file1\" style='width: 165px'><br/><br/>" ;
						$output.="<script type=\"text/javascript\">" ;
							$output.="var file1=new LiveValidation('file1');" ;
							$output.="file1.add( Validate.Inclusion, { within: ['gif','jpg','jpeg','png'], failureMessage: \"Illegal file type!\", partialMatch: true, caseSensitive: false } );" ;
						$output.="</script>" ;
					$output.="</td>" ;
					$output.="<td class='right' style='vertical-align: top'>" ;
						$output.="<input style='height: 27px; width: 20px!important; margin-top: 0px;' type='submit' value='" . trans::__( 'Go') . "'>" ;
					$output.="</td>" ;
				$output.="</tr>" ;
			$output.="</table>" ;
		$output.="</form>" ;
	}
	else { //Photo, so show image and removal link
		$output.="<p>" ;
			$output.= $this->getPerson($this->session->get("gibbonPersonID"))->getUserPhoto($this->session->get("image_240"), 240) ;
			$output.="<div style='margin-left: 220px; margin-top: -50px'>" ;
				$output.="<a href='" . $this->session->get("absoluteURL") . "/index_parentPhotoDeleteProcess.php?gibbonPersonID=" . $this->session->get("gibbonPersonID") . "' onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img style='margin-bottom: -8px' id='image_240_delete' title='" . trans::__( 'Delete') . "' src='./themes/" . $this->session->get("gibbonThemeName") . "/img/garbage.png'/></a><br/><br/>" ;
			$output.="</div>" ;
		$output.="</p>" ;
	}
}

if ($output !== false) echo $output; 
$this->session->set("sidebar", $output) ;
