<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Forms\Input;

use Gibbon\Forms\OutputableInterface;
use Gibbon\Forms\FormFactoryInterface;

/**
 * Custom Blocks
 *
 * @version v15
 * @since   v15
 */
class CustomBlocks implements OutputableInterface
{
    protected $factory;
    protected $session;

    protected $name;
    protected $settings;
    protected $placeholder;

    protected $blockTemplate;
    protected $toolsTable;
    protected $blockButtons;

    /**
     * Create a Blocks input with a given template.
     * @param  FormFactoryInterface $factory
     * @param  string               $name
     * @param  OutputableInterface  $form
     * @param  Session              $session
     */
    public function __construct(FormFactoryInterface &$factory, $name, OutputableInterface $block, \Gibbon\Session $session)
    {
        $this->factory = $factory;
        $this->session = $session;

        $this->name = $name;
        $this->placeholder = __('Blocks will appear here...'); 

        $this->blockTemplate = $block->setClass('blank fullWidth');
        $this->toolsTable = $factory->createTable()->setClass('inputTools blank fullWidth');
        $this->blockButtons = $factory->createGrid()->setClass('blockButtons blank fullWidth')->setColumns(2);
        $this->addBlockButton(__('Delete'), 'garbage.png', '', 'deleteButton');

        $this->settings = array(
            'deleteConfirm' => __('Are you sure you want to delete this record?'),
        );
    }

    /**
     * Changes the placeholder string when no blocks are present.
     * @param  string  $value
     * @return self
     */
    public function placeholder($value)
    {
        $this->placeholder = $value;
        return $this;
    }

    /**
     * Updates the settings array which is passed as json params to JS.
     * @param  string  $value
     * @return self
     */
    public function settings($value)
    {
        $this->settings = array_replace($this->settings, $value);
        return $this;
    }

    /**
     * Adds the given input into the tool bar.
     * @param  OutputableInterface  $value
     * @return self
     */
    public function addToolInput(OutputableInterface $input)
    {
        $this->toolsTable->addRow()->addElement($input)->addClass('floatNone');
        return $this;
    }

    /**
     * Adds the given button to each block. 
     * Note: $function must be the name of the function (i.e. "func" not "func()"). The function must only take in one input (the id of the block).
     * @param  string  $name
     * @param  string  $icon
     * @param  string  $function
     * @return self
     */
    public function addBlockButton($name, $icon, $function = '', $class = '')
    {
        $iconSrc = stripos($icon, '/') === false? './themes/'.$this->session->get("gibbonThemeName").'/img/'.$icon : $icon;
        $button = $this->factory->createWebLink('<img title="'.$name.'" src="'.$iconSrc.'" style="margin-right:4px;" />')
            ->setURL('#')
            ->addClass('blockButton');

        if (!empty($function)) $button->addData('function', $function);
        if (!empty($class)) $button->addClass($class);

        $this->blockButtons->addCell()->addElement($button);
        return $this;
    }

    public function getClass()
    {
        return '';
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    public function getOutput()
    {
        $output = '';

        $output .= '<style>
                #' . $this->name . ' { list-style-type: none; margin: 0; padding: 0; width: 100%; }
                #' . $this->name . ' div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
                div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
                #' . $this->name . ' li { min-height: 58px; line-height: 1.2em; }
                .' . $this->name . '-ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
                .' . $this->name . '-ui-state-highlight {border: 1px solid #fcd3a1; background: #fbf8ee url(images/ui-bg_glass_55_fbf8ee_1x400.png) 50% 50% repeat-x; color: #444444; }
            </style>';

        $output .= '<div class="customBlocks" id="' . $this->name. '" style="width: 100%; padding: 5px 0px 0px 0px; min-height: 66px">';

            $output .= '<input type="hidden" class="blockCount" name="'.$this->name.'Count" value="0" />';
            $output .= '<div class="blockPlaceholder" style="color: #ddd; font-size: 230%; padding: 15px 0 15px 6px">'.$this->placeholder.'</div>';

            $output .= '<div class="blockTemplate displayNone hiddenReveal" style="overflow:hidden; border: 1px solid #d8dcdf; margin: 0 0 5px">';
                $output .= '<div class="blockInputs" style="float:left; width:92%; padding: 5px; box-sizing: border-box;">';
                $output .= $this->blockTemplate->getOutput();
                $output .= '</div>';

                $output .= '<div class="blockSidebar" style="float:right; width: 8%; ">';
                    $output .= $this->blockButtons->getOutput();
                $output .= '</div>';
            $output .= '</div>';
            
            $output .= $this->toolsTable->getOutput();
        $output .= '</div>';

        $output .= '<script type="text/javascript">
            $(function(){
                $("#'.$this->name.'").gibbonCustomBlocks('.json_encode($this->settings).');
            });
        </script>';

        return $output;
    }
}
