<?php
	$form = $el->form ;
	$x = '
			<tr>
				<td rowspan="3" class="multiRow"> 
					<strong>'.$this->__('Tutors').'</strong><br/>
					<span class="emphasis small">'.$this->__('Up to 3 per roll group. The first-listed will be marked as "Main Tutor".').'</span>
				</td>
				<td class="right">
';
	$form->addElement('raw', null, $x);

	$x = $form->addElement('select', 'gibbonPersonIDTutor', $el->getField("gibbonPersonIDTutor"));
	$x->addOption('');
	$x->setElementOnly();
	foreach ($el->people as $person)
		$x->addOption($person->formatName(true, true), $person->getField('gibbonPersonID'));
	
	$form->addElement('raw', null, '				</td>
			</tr>
			<tr>
				<td class="right">
');
	
	

	$x = $form->addElement('select', 'gibbonPersonIDTutor2', $el->getField("gibbonPersonIDTutor2"));
	$x->setElementOnly();
	$x->addOption('');
	foreach ($el->people as $person)
		$x->addOption($person->formatName(true, true), $person->getField('gibbonPersonID'));

	$form->addElement('raw', null, '				</td>
			</tr>
			<tr>
				<td class="right">
');


	$x = $form->addElement('select', 'gibbonPersonIDTutor3', $el->getField("gibbonPersonIDTutor3"));
	$x->setElementOnly();
	$x->addOption('');
	foreach ($el->people as $person)
		$x->addOption($person->formatName(true, true), $person->getField('gibbonPersonID'));
	$form->addElement('raw', null, '
        </td>
    </tr>
');
