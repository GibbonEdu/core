<?php $this->dump($el, true); ?>
    <tr>
        <td>
        <b>'.formatName('', $rowBehaviour['preferredNameStudent'], $rowBehaviour['surnameStudent'], 'Student', false).'</b><br/>
                                if (substr($rowBehaviour['timestamp'], 0, 10) > $rowBehaviour['date']) {
        $this->view->__('Date Updated').': '.dateConvertBack($guid, substr($rowBehaviour['timestamp'], 0, 10)).'<br/>
        $this->view->__('Incident Date').': '.dateConvertBack($guid, $rowBehaviour['date']).'<br/>
                                } else {
        dateConvertBack($guid, $rowBehaviour['date']).'<br/>
                                }
        </td>
        <td style='text-align: center'>
                                if ($rowBehaviour['type'] == 'Negative') {
            <img title='".$this->view->__('Negative')."' src='./themes/".$this->session->get('theme.Name')."/img/iconCross.png'/>
                                } elseif ($rowBehaviour['type'] == 'Positive') {
            <img title='".$this->view->__('Position')."' src='./themes/".$this->session->get('theme.Name')."/img/iconTick.png'/>
                                }
        </td>
        <td>
        trim($rowBehaviour['descriptor']);
        </td>
        <td>
        trim($rowBehaviour['level']);
        </td>
        <td>
        formatName($rowBehaviour['title'], $rowBehaviour['preferredNameCreator'], $rowBehaviour['surnameCreator'], 'Staff', false).'<br/>
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
                                if ($rowBehaviour['comment'] != '') {
            <a title='".$this->view->__('View Description')."' class='show_hide-$count2' onclick='false' href='#'><img style='padding-right: 5px' src='".GIBBON_URL."themes/Default/img/page_down.png' alt='".$this->view->__('Show Comment')."' onclick='return false;' /></a>
                                }
        </td>
        </tr>
                                if ($rowBehaviour['comment'] != '') {
                                    if ($rowBehaviour['type'] == 'Positive') {
                                        $bg = 'background-color: #D4F6DC;
                                    } else {
                                        $bg = 'background-color: #F6CECB;
                                    }
            <tr class='comment-$count2' id='comment-$count2'>
            <td style='$bg' colspan=6>
        $rowBehaviour['comment'];
            </td>
            </tr>
                                }
    </tr>
</tr>
