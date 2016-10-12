<?php
use Gibbon\trans ;
use Gibbon\helper ;
?>
<tr>
    <td>
		<?php echo helper::dateConvertBack(substr($el->getField('timestamp'), 0, 10)) ?><br/>
        <span style='font-size: 75%; font-style: italic'><?php echo substr($el->getField('timestamp'), 11, 5); ?></span>
    </td>
    <td>
    	<?php echo $el->categories[$el->getField('category')]; ?>
    </td>
    <td>
		<?php if (empty($el->getField('title'))) {
			echo '<em>'.trans::__('NA').'</em><br/>';
		} else {
			echo $el->getField('title').'<br/>';
		}?>
        <span style='font-size: 75%; font-style: italic'> <?php echo substr(strip_tags($el->getField('note')), 0, 60); ?></span>
    </td>
    <td>
    <?php echo $el->getCreator()->formatName(false, true); ?>
    </td>
    <td>
		<?php
		if ($el->getField('gibbonPersonIDCreator') == $this->session->get('gibbonPersonID')) {
	        $this->getLink('Edit', array('q' => '/modules/Students/student_view_details_notes_edit.php', 'gibbonPersonID' => $el->getField('gibbonPersonID') 'search'=>$el->search, 'gibbonStudentNoteID'=>$el->getField('gibbonStudentNoteID'), 'allStudents'=>$el->allStudents, 'subpage'=>'Notes', 'category'=>$el->getField('gibbonStudentNoteCategoryID')));
	        $this->getLink('Delete', array('q' => '/modules/Students/student_view_details_notes_delete.php', 'gibbonPersonID' => $el->getField('gibbonPersonID') 'search'=>$el->search, 'gibbonStudentNoteID'=>$el->getField('gibbonStudentNoteID'), 'allStudents'=>$el->allStudents, 'subpage'=>'Notes', 'category'=>$el->getField('gibbonStudentNoteCategoryID')));
		}
		$id = $el->getField('gibbonStudentNoteID');
        ?>
<script type='text/javascript'>
	$(document).ready(function(){
		$(".note-<?php echo $id ;?>").hide();
		$(".show_hide-<?php echo $id ;?>").fadeIn(1000);
		$(".show_hide-<?php echo $id ;?>").click(function(){
			$(".note-<?php echo $id ;?>").fadeToggle(1000);
		});
	});
</script>';
		<a title='<?php trans::__('View Description'); ?>' class='show_hide-<?php echo $id ;?>' onclick='return false;' href='#'><img title='".trans::__('View Details')."' src='./themes/Default/img/page_down.png'/></a></span><br/>
    </td>
</tr>
<tr class='note-<?php echo $id ;?>' id='note-<?php echo $id ;?>'>'
    <td colspan='6'>
    <?php echo $el->getField('note'); ?>
    </td>
</tr>
