	</tbody>
</table>
<?php	
if (! isset($el->action) || $el->action) { 
	$token = new Gibbon\Form\token('/modules/School Admin/gradeScales_manage_editSortable.php', null, $this);
	$this->addScript('
<script language="javascript" type="text/javascript">
	$(document).ready(function() {
		var fixHelper = function(e, ui) {
			ui.children().each(function() {
				$(this).width($(this).width());
			});
			return ui;
		};
		
		$("#sortable").sortable({
			helper: fixHelper,
			opacity: 0.6,
			scroll: false,
			update: function(event, ui) {
				var newOrder = $(this).sortable("toArray").toString();
				$.ajax({
					data: {
						order: newOrder, 
						_token: "'.$token->generateToken('/modules/School Admin/gradeScales_manage_editSortable.php').'", 
						action: "'.$token->generateAction('/modules/School Admin/gradeScales_manage_editSortable.php').'", 
						divert: "true",
						gibbonScaleID: '.$el->getField('gibbonScaleID').'
					},
					type: "POST",
					url: "' . GIBBON_URL . 'index.php?q=/modules/School Admin/gradeScales_manage_editSortable.php",
					success: function(data){
						$("#sortable").html(data);
					},
					beforeSend: function() { $("#malcolm").addClass("loading"); },
					complete: function() { $("#malcolm").removeClass("loading"); }
				});
			}
		});
	});
</script>
');
}
