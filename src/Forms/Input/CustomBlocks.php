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
    public function __construct(FormFactoryInterface &$factory, $name, \Gibbon\Session $session)
    {
        $this->factory = $factory;
        $this->session = $session;
        $this->name = $name;

        $this->toolsTable = $factory->createTable()->setClass('inputTools fullWidth');
        $this->blockButtons = $factory->createGrid()->setClass('blockButtons blank fullWidth')->setColumns(2);
        $this->addBlockButton('delete', __('Delete'), 'garbage.png');

        $this->settings = array(
            'placeholder' => __('Blocks will appear here...'),
            'deleteMessage' => __('Are you sure you want to delete this record?'),
            'currentBlocks' => array(),
        );
    }

    /**
     * Set a predefined layout using OutputableInterface which will be cloned for each new block.
     * TODO: add fromAjax option for loading in templates dynamically?
     * @param OutputableInterface $block
     * @return void
     */
    public function fromTemplate(OutputableInterface $block)
    {
        $this->blockTemplate = $block->setClass('blank fullWidth');
        return $this;
    }

    /**
     * Changes the placeholder string when no blocks are present.
     * @param  string  $value
     * @return self
     */
    public function placeholder($value)
    {
        $this->settings['placeholder'] = $value;
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
    public function addBlockButton($name, $title, $icon, $class = '')
    {
        $iconImg = '<img title=%1$s src="%2$s" style="margin-right:4px;" />';
        $iconPath = './themes/'.$this->session->get("gibbonThemeName").'/img/';
        $iconSrc = stripos($icon, '/') === false? $iconPath.$icon : $icon;
        
        $button = $this->factory->createWebLink(sprintf($iconImg, $title, $iconSrc))
            ->setURL('#')
            ->addClass('blockButton');

        if (!empty($name)) $button->addData('event', $name."Clicked");
        if (!empty($class)) $button->addClass($class);

        if ($name == 'showHide') {
            $button->addData('on', $iconPath.'minus.png');
            $button->addData('off', $iconPath.'plus.png');
        }

        $this->blockButtons->addCell()->addElement($button);
        return $this;
    }

    /**
     * Adds a block from an array of data.
     * @param  string  $id
     * @param  array   $data
     * @return self
     */
    public function addBlock($id, array $data = array())
    {
        $this->settings['currentBlocks'][$id] = $data;

        return $this;
    }

    /**
     * Add a set of data that a new block can be created from via identifier.
     * @param string  $id
     * @param array   $data
     * @return self
     */
    public function addPredefinedBlock($id, array $data = array())
    {
        $this->settings['predefinedBlocks'][$id] = $data;

        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    public function getOutput()
    {
        $output = '';

        // $output .= '<style>
        //         #' . $this->name . ' { list-style-type: none; margin: 0; padding: 0; width: 100%; }
        //         #' . $this->name . ' div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
        //         div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
        //         #' . $this->name . ' li { min-height: 58px; line-height: 1.2em; }
        //     </style>';

        $output .= '<div class="customBlocks" id="' . $this->name. '">';

            $output .= '<input type="hidden" class="blockCount" name="'.$this->name.'Count" value="0" />';
            $output .= '<div class="blockPlaceholder '.(count($this->settings['currentBlocks']) > 0 ? 'displayNone' : '').'">'.$this->settings['placeholder'].'</div>';
   
            $output .= '<div class="blockTemplate displayNone">';
                $output .= '<div class="blockInputs floatLeft">';
                $output .= $this->getTemplateOutput($this->blockTemplate);
                $output .= '</div>';

                $output .= '<div class="blockSidebar floatRight">';
                    $output .= $this->blockButtons->getOutput();
                $output .= '</div>';
            $output .= '</div>';

            $output .= '<div class="blocks">';
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

    /**
     * Adds the validation settings for each input as JSON data attributes so they can be added dynamically for each block.
     * @param  OutputableInterface $template
     * @return string 
     */
    protected function getTemplateOutput(OutputableInterface $template)
    {
        // Trigger the output before adding validations: some Inputs add these on getOutput();
        $output = 

        // Look for and jsonify the validations recursivly
        $addValidation = function($element) use (&$addValidation) {
            if (method_exists($element, 'getElements')) {
                foreach ($element->getElements() as $innerElement) {
                    $addValidation($innerElement);
                }
            }

            if ($element instanceof Input && $element->hasValidation()) {
                $elementOutput = $element->getOutput();
                $element->addData('validation', $element->getValidationAsJSON());
            }
        };

        $addValidation($template);

        return $template->getOutput();
    }
}
