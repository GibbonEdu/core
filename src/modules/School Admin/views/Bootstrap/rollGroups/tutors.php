<?php
	$form = $el->form ;
	$x = '
<div>
	<div class="row form-element form-group">
    	<div class="col-md-5 col-lg-5" style="margin-top: 25px ; vertical-align: middle ; ">
			<label>'.$this->__('Tutors').'</label>
            <p class="help-block">'.$this->__('Up to 3 per roll group. The first-listed will be marked as "Main Tutor".').'</p>
        </div>
        <div class="col-md-offset-1 col-lg-offset-1 col-md-6 col-lg-6">
        	<div class="row-fluid">
            	<div class="col-md-12 col-lg-12">
';
	$form->addElement('raw', null, $x);

	$x = $form->addElement('select', 'gibbonPersonIDTutor', $el->getField("gibbonPersonIDTutor"));
	$x->addOption('');
	$x->setElementOnly();
	foreach ($el->people as $person)
		$x->addOption($person->formatName(true, true), $person->getField('gibbonPersonID'));
	
	$form->addElement('raw', null, '
				</div>
           	</div> 	
        	<div class="row-fluid">
            	<div class="col-md-12 col-lg-12">
');
	
	

	$x = $form->addElement('select', 'gibbonPersonIDTutor2', $el->getField("gibbonPersonIDTutor2"));
	$x->setElementOnly();
	$x->addOption('');
	foreach ($el->people as $person)
		$x->addOption($person->formatName(true, true), $person->getField('gibbonPersonID'));

	$form->addElement('raw', null, '
                </div>
           	</div> 	
        	<div class="row-fluid">
            	<div class="col-md-12 col-lg-12">
');


	$x = $form->addElement('select', 'gibbonPersonIDTutor3', $el->getField("gibbonPersonIDTutor3"));
	$x->setElementOnly();
	$x->addOption('');
	foreach ($el->people as $person)
		$x->addOption($person->formatName(true, true), $person->getField('gibbonPersonID'));
	$form->addElement('raw', null, '
                </div>
           	</div> 	
        </div>
    </div>
</div>
');
