<table class='noIntBorder' cellspacing='0' style='width: 100%'>
	<tbody>
        <tr>
            <td>
                <span style='font-size: 115%; font-weight: bold'><?php echo $this->__('Timetable Chooser');?></span>: <?php
                foreach($el->result as $el->row) 
					$this->render('Timetable.chooser', $el);
                $result = $this->getRecord('TT')->findAll($el->sql, $el->data);
                if (! $this->getRecord('TT')->getSuccess())
                    $this->displayMessage($this->getRecord('TT')->getError()); ?>
            </td>
        </tr>
	</tbody>
</table>
