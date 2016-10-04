    <tr>
        <td>
        <strong><?php echo $el->getPerson()->formatName(false)?></strong><br/><?php
		if (substr($el->getField('timestamp'), 0, 10) > $el->getField('date')) {
			echo $this->view->__('Date Updated').': '.$el->dateConvertBack(substr($el->getField('timestamp'), 0, 10)).'<br/>';
			echo $this->view->__('Incident Date').': '.$el->dateConvertBack($el->getField('date')).'<br/>';
		} else {
			echo  $el->dateConvertBack($el->getField('date')).'<br/>';
		} /* 
        </td>
        <td style='text-align: center'>
                                if ($el->getField('type'] == 'Negative') {
            <img title='".$this->view->__('Negative')."' src='./themes/".$this->session->get('theme.Name')."/img/iconCross.png'/>
                                } elseif ($el->getField('type'] == 'Positive') {
            <img title='".$this->view->__('Position')."' src='./themes/".$this->session->get('theme.Name')."/img/iconTick.png'/>
                                }
        </td>
        <td>
        trim($el->getField('descriptor']);
        </td>
        <td>
        trim($el->getField('level']);
        </td>
        <td>
        formatName($el->getField('title'], $el->getField('preferredNameCreator'], $el->getField('surnameCreator'], 'Staff', false).'<br/>
        </td>
        <td>
        <script type="text/javascript">
        $(document).ready(function(){
        \$(\".comment-$count2\").hide();
        \$(\".show_hide-$count2\").fadeIn(1000);
        \$(\".show_hide-$count2\").click(function(){
        \$(\".comment-$count2\").fadeToggle(1000);
        });
        });
        </script>
                                if ($el->getField('comment'] != '') {
            <a title='".$this->view->__('View Description')."' class='show_hide-$count2' onclick='false' href='#'><img style='padding-right: 5px' src='".GIBBON_URL."themes/Default/img/page_down.png' alt='".$this->view->__('Show Comment')."' onclick='return false;' /></a>
                                }
        </td>
        </tr>
                                if ($el->getField('comment'] != '') {
                                    if ($el->getField('type'] == 'Positive') {
                                        $bg = 'background-color: #D4F6DC;
                                    } else {
                                        $bg = 'background-color: #F6CECB;
                                    }
            <tr class='comment-$count2' id='comment-$count2'>
            <td style='$bg' colspan=6>
        $el->getField('comment'];
            </td>
            </tr>
                                }
								*/ ?>
    </tr>
</tr>
