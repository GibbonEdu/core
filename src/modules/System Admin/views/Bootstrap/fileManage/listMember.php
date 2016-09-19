        <div class="row <?php echo $params->rowNum; ?>">
            <div class="col-lg-3 col-md-3">
                 <?php echo $params->name.'.yml' ; ?>
            </div>      
            <div class="col-lg-7 col-md-7">
                 <?php echo $params->status  ; ?>
            </div>      
            <div class="col-lg-2 col-md-2 centre border">
                <?php 
				if (substr($params->status, 0, 6) == 'Update') new Gibbon\Form\checkbox('update-'.$params->name, $params->name, $this);
				elseif ($params->status == 'Unknown') echo '<span class="halflings halflings-question-sign"></span>';
				else echo '<span class="halflings halflings-tick"></span>'; ?>
            </div>
        </div>
