<!-- default.form.editor.toolbar -->
<a name='<?php echo $el->id; ?>editor'></a>

<div id='editor-toolbar'>
    <a style='margin-top:-4px' id='<?php echo $el->id; ?>edButtonHTML' class='hide-if-no-js edButtonHTML'>HTML</a>
    <a style='margin-top:-4px' id='<?php echo $el->id; ?>edButtonPreview' class='active hide-if-no-js edButtonPreview'><?php echo $this->__('Visual'); ?></a>

    <div id='media-buttons'>
        <div style='padding-top: 2px; height: 15px'>
            <?php if ($el->showMedia) { ?>
                <div id='<?php echo $el->id; ?>mediaInner' style='text-align: left'>
                <?php $this->addScript('
<script type="text/javascript">
	$(document).ready(function(){
		$("#'.$el->id.'resourceSlider").hide();
		$("#'.$el->id.'resourceAddSlider").hide();
		$("#'.$el->id.'resourceQuickSlider").hide();
		$(".'.$el->id.'show_hide").show();
		$(".'.$el->id.'show_hide").unbind("click").click(function(){
			$("#'.$el->id.'resourceSlider").slideToggle();
			$("#'.$el->id.'resourceAddSlider").hide();
			$("#'.$el->id.'resourceQuickSlider").hide();
			if (tinyMCE.get("'.$el->id.'").selection.getRng().startOffset < 1) {
				tinyMCE.get("'.$el->id.'").focus();
			}
		});
		$(".'.$el->id.'show_hideAdd").show();
		$(".'.$el->id.'show_hideAdd").unbind("click").click(function(){
			$("#'.$el->id.'resourceAddSlider").slideToggle();
			$("#'.$el->id.'resourceSlider").hide();
			$("#'.$el->id.'resourceQuickSlider").hide();
			if (tinyMCE.get("'.$el->id.'").selection.getRng().startOffset < 1) {
				tinyMCE.get("'.$el->id.'").focus();
			}
		});
		$(".'.$el->id.'show_hideQuickAdd").show();
		$(".'.$el->id.'show_hideQuickAdd").unbind("click").click(function(){
			$("#'.$el->id.'resourceQuickSlider").slideToggle();
			$("#'.$el->id.'resourceSlider").hide();
			$("#'.$el->id.'resourceAddSlider").hide();
			if (tinyMCE.get("'.$el->id.'").selection.getRng().startOffset < 1) {
				tinyMCE.get("'.$el->id.'").focus();
			}
		});
	});
</script>
'); ?>
                  <div style='float: left; padding-top:1px; margin-right: 5px'><u><?php echo $this->__('Shared Resources'); ?></u>:</div> 
                    	<?php $this->getLink('Insert Existing Resource', array('href'=>'#', 'onclick'=>"$('#".$el->id."resourceSlider').load('".GIBBON_URL."index.php?q=/modules/Resources/resources_insert_ajax.php&alpha=".$el->resourceAlphaSort.$el->initialFilter."&id=".$el->id."&allowUpload=".$el->allowUpload."&divert=1');", 'class'=>$el->id.'show_hide', 'style'=>'float: left;')); ?>
                    <?php if ($el->allowUpload) { 
						$this->getLink('Create Insert New Resource', array('href'=>'#', 'onclick'=>"$('#".$el->id."resourceAddSlider').load('".GIBBON_URL."index.php?q=/modules/Resources/resources_add_ajax.php&alpha=".$el->resourceAlphaSort.$el->initialFilter."&id=".$el->id."&allowUpload=".$el->allowUpload."&divert=1');", 'class'=>$el->id.'show_hideAdd', 'style'=>'float: left; margin: 0 3px 0 3px;')); 
                    } ?>
                    <div style='float: left; padding-top:1px; margin-right: 5px'><u><?php echo $this->__('Quick File Upload'); ?></u>:</div> 
						<?php $this->getLink('Quick Add', array('href'=>'#', 'onclick'=>"$('#".$el->id."resourceQuickSlider').load('".GIBBON_URL."index.php?q=/modules/Resources/resources_addQuick_ajax.php&alpha=".$el->resourceAlphaSort.$el->initialFilter."&id=".$el->id."&allowUpload=".$el->allowUpload."&divert=1');", 'class'=>$el->id.'show_hideQuickAdd', 'style'=>'float: left; margin: 0 3px 0 3px;'));  ?>
                </div><?php
            } ?>
        </div>
    </div>
    <?php if ($el->showMedia) {
        //DEFINE MEDIA INPUT DISPLAY ?>
        <div id='<?php echo $el->id; ?>resourceSlider' style='display: none; width: 100%; min-height: 60px;'>
            <div style='text-align: center; width: 100%; margin-top: 5px'>
                <img style='margin: 10px 0 5px 0' src='<?php echo GIBBON_URL; ?>themes/<?php echo $this->session->get('theme.Name') ; ?>/img/loading.gif' alt='<?php echo $this->__('Loading'); ?>' onclick='return false;' /><br/>
                <?php echo $this->__('Loading') ; ?>
            </div>
        </div>

        <!-- DEFINE QUICK INSERT -->
        <div id='<?php echo $el->id; ?>resourceQuickSlider' style='display: none; width: 100%; min-height: 60px;'>
            <div style='text-align: center; width: 100%; margin-top: 5px'>
                <img style='margin: 10px 0 5px 0' src='<?php echo GIBBON_URL; ?>themes/<?php echo $this->session->get('theme.Name') ; ?>/img/loading.gif' alt='<?php echo $this->__('Loading'); ?>' onclick='return false;' /><br/>
                <?php echo $this->__('Loading') ; ?>
            </div>
        </div>
	<?php }

    if ($el->showMedia && $el->allowUpload) {
        //DEFINE MEDIA ADD DISPLAY ?>
        <div id='<?php echo $el->id; ?>resourceAddSlider' style='display: none; width: 100%; min-height: 60px;'>
            <div style='text-align: center; width: 100%; margin-top: 5px'>
                <img style='margin: 10px 0 5px 0' src='<?php echo GIBBON_URL; ?>themes/<?php echo $this->session->get('theme.Name') ; ?>/img/loading.gif' alt='<?php echo $this->__('Loading'); ?>' onclick='return false;' /><br/>
                <?php echo $this->__('Loading') ; ?>
            </div>
        </div>
    <?php } ?>

    <div id='editorcontainer' style='margin-top: 4px'>
        <textarea class='tinymce' name='<?php echo $el->id; ?>' id='<?php echo $el->id; ?>' style='height: <?php echo ($el->rows * 18); ?>px; margin-left: 0px'><?php echo $this->htmlPrep($el->value); ?></textarea><?php
			$this->render('form.editor.required', $el); ?>
    </div>
	<?php 
	$exec = '';
	if ($el->tinymceInit) { 
    	$exec = 'tinyMCE.execCommand("mceAddControl", false, "'.$el->id.'");'."\n";
    } 
	$required1 = '';
	$required2 = '';
	if ($el->required)
	{
		$required1 = '
                    '.$el->id.'.destroy();
                    $(".LV_validation_message").css("display","none");
                    '.$el->id.'=new LiveValidation("'.$el->id.'");
                    '.$el->id.'.add(Validate.Presence);
		
		';
		$required2 = '
		            '.$el->id.'.destroy();
                    $(".LV_validation_message").css("display","none");
                    '.$el->id.'=new LiveValidation("'.$el->id.'");
                    '.$el->id.'.add(Validate.Presence, { tinymce: true, tinymceField: "'.$el->id.'"});
';	
	}
	$this->addScript('
    <script type="text/javascript">
        $(document).ready(function(){
			'.$exec.'
            $("#'.$el->id.'edButtonPreview").addClass("active") ;
            $("#'.$el->id.'edButtonHTML").click(function(){
                tinyMCE.execCommand("mceRemoveEditor", false, "'.$el->id.'");
                $("#'.$el->id.'edButtonHTML").addClass("active") ;
                $("#'.$el->id.'edButtonPreview").removeClass("active") ;
                $("#'.$el->id.'resourceSlider").hide();
                $("#'.$el->id.'mediaInner").hide();
                '.$required1.'
             }) ;
             $("#'.$el->id.'edButtonPreview").click(function(){
                tinyMCE.execCommand("mceAddEditor", false, "'.$el->id.'");
                $("#'.$el->id.'edButtonPreview").addClass("active") ;
                $("#'.$el->id.'edButtonHTML").removeClass("active") ; 
                $("#'.$el->id.'mediaInner").show();
				'.$required2.'
             }) ;
        });
    </script>
	'); ?>
</div>
