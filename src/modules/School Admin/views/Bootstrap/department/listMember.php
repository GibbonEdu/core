<tr>
    <td>
        <?php echo $el->name ; ?>
    </td>
    <td>
        <?php echo $el->type ; ?>
    </td>
    <td>
        <?php echo $el->nameShort ; ?>
    </td>
    <td>
        <?php 
			$pObj = new Gibbon\Record\person($this);
			$people = $pObj->findAll("SELECT preferredName, surname 
						FROM gibbonDepartmentStaff 
						JOIN gibbonPerson ON (gibbonDepartmentStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) 
						WHERE gibbonPerson.status='Full' 
							AND gibbonDepartmentID=:gibbonDepartmentID 
						ORDER BY surname, preferredName", array("gibbonDepartmentID" => $el->gibbonDepartmentID));
			if (count($people) > 0)
				foreach($people as $person)
					echo $person->formatName(false, true).', ';
			else
				echo $this->__("None"); ?>
    </td>
        <?php if (! isset($el->action)) { ?>
    <td>
	<?php  
        $this->getLink('edit', array('q'=>'/modules/School Admin/department_manage_edit.php', 'gibbonDepartmentID' => $el->gibbonDepartmentID));
        $this->getLink('delete', array('q'=>'/modules/School Admin/department_manage_delete.php', 'gibbonDepartmentID' => $el->gibbonDepartmentID));
    ?>
    </td>
	<?php } ?>
</tr>
