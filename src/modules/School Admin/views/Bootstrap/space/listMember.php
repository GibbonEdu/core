                <tr>
					<td>
						<?php echo $el->getField('name') ; ?>
					</td>
					<td>
						<?php echo $el->getField('type'); ?>
					</td>
					<td>
						<?php foreach($el->getStaff() as $person)
						 	echo $person->formatName(true, true).'<br/>'; ?>
					</td>
					<td>
						<?php echo $el->getField('capacity'); ?>
					</td>
					<td>
						<?php
						echo $el->getField('computer') == 'Y' ? $this->__('Teaching Computer').'<br/>' : '';
						echo $el->getField('computerStudent') > 0 ? $this->__('student computer', array($el->getField('computerStudent')), $el->getField('computerStudent')).'<br/>' : '';
						echo $el->getField('projector') == 'Y' ? $this->__('Projector').'<br/>' : '';
						echo $el->getField('tv') == 'Y' ? $this->__('Television').'<br/>' : '';
						echo $el->getField('dvd') == 'Y' ? $this->__('DVD Player').'<br/>' : '';
						echo $el->getField('hifi') == 'Y' ? $this->__('HiFi').'<br/>' : '';
						echo $el->getField('speakers') == 'Y' ? $this->__('Speakers').'<br/>' : '';
						echo $el->getField('iwb') == 'Y' ? $this->__('Interactive White Board').'<br/>' : '';
						echo ! empty($el->getField('phoneInternal')) ? $this->__('Extension #', array($el->getField('phoneInternal'))).'<br/>' : '';
						echo ! empty($el->getField('phoneExternal')) ? $this->__('Phone #', array($el->getField('phoneExternal'))).'<br/>' : '';
						?>
					</td>
				<?php if ((bool) $el->action) { ?>
					<td>
					<?php  
                        $this->getLink('edit', array('q'=>'/modules/School Admin/space_manage_edit.php', 'gibbonSpaceID'=>$el->getField('gibbonSpaceID')));
                        $this->getLink('delete', array('q'=>'/modules/School Admin/space_manage_delete.php', 'gibbonSpaceID'=>$el->getField('gibbonSpaceID')));
                    ?>
					</td>
				<?php } ?>
				</tr>
