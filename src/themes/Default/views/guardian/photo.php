<p class="right" style='margin-top: 20px; '>
	<?php echo $el->getUserPhoto('', 240) ;
	echo $this->getLink('delete', array('q'=>'/modules/User Admin/index_parentPhotoDeleteProcess.php', 'gibbonPersonID' => $this->session->get("gibbonPersonID"), 'divert' => true, 'onclick' => "return confirm('Are you sure you want to delete this record? Unsaved changes will be lost.')", 'title' => 'Delete Photo'));
	?>
</p><!-- default.guardian.photo -->
