<div class="row alternate">
    <div class="col-lg-2 col-md-2">
        <?php echo $this->__($el->getField('name')); 
		$w = new Gibbon\Form\hidden('gibbonYearGroupID[]', $el->getField('gibbonYearGroupID'), $this);
		$this->render('form.hidden', $w); ?>
    </div>
    <div class="col-lg-5 col-md-5">
		<?php $w = new Gibbon\Form\select('gibbonExternalAssessmentID['.$el->count.']', $el->eaValue, $this);
        $w->setID('gibbonExternalAssessmentID'.$el->count);
		$w->element->class = 'form-control';
		$w->addOption('');
        foreach($el->eaList as $ea)
            $w->addOption($this->__($ea->getField('name')), $ea->getField('gibbonExternalAssessmentID'));  
        $this->render('form.select', $w); ?>
    </div>
    <div class="col-lg-5 col-md-5">
		<?php $w = new Gibbon\Form\select('category'.$el->count, $el->catValue, $this); 
        $w->setID('category'.$el->count);
		$w->element->class = 'form-control';
		$w->addOption('');
        foreach($el->catList as $ec)
            $w->addOption($this->htmlPrep($this->__(substr($ec->getField('category'), (strpos($ec->getField('category'), '_') + 1)))), $ec->getField('category'), $ec->getField('gibbonExternalAssessmentID'));  
        $this->render('form.select', $w); 
		$this->addScript('
        <script type="text/javascript">
            $("#_category'.$el->count.'").chainedTo("#_gibbonExternalAssessmentID'.$el->count.'");
        </script>
		'); ?>
    </div>
</div>
