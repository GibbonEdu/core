<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Contracts\Services\Session;
use Gibbon\Forms\OutputableInterface;
use Gibbon\Forms\FormFactoryInterface;
use Gibbon\Forms\Traits\BasicAttributesTrait;

/**
 * Custom Blocks
 *
 * @version v15
 * @since   v15
 */
class CustomBlocks implements OutputableInterface
{
    use BasicAttributesTrait;
    
    protected $factory;
    protected $session;

    protected $name;
    protected $settings;
    protected $placeholder;
    protected $compact;

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
    public function __construct(FormFactoryInterface &$factory, $name, Session $session, bool $canDelete = true)
    {
        $this->factory = $factory;
        $this->session = $session;
        $this->name = $name;

        $this->toolsTable = $factory->createTable()->setClass('inputTools w-full');
        $this->blockButtons = $factory->createGrid()->setClass('blockButtons blank w-full');

        $this->settings = [
            'placeholder'      => __('Blocks will appear here...'),
            'deleteMessage'    => __('Are you sure you want to delete this record?'),
            'duplicateMessage' => __('This element has already been selected!'),
            'currentBlocks'    => [],
        ];

        if ($canDelete) {
            $this->addBlockButton('delete', __('Delete'), 'garbage.png');
        }
    }

    /**
     * Set a predefined layout using OutputableInterface which will be cloned for each new block.
     * TODO: add fromAjax option for loading in templates dynamically?
     * @param OutputableInterface $block
     * @return void
     */
    public function fromTemplate(OutputableInterface $block, $compact = false)
    {
        $this->blockTemplate = $block->addClass('blank w-full');
        $this->compact = $compact;
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
     * @param  array  $value
     * @return self
     */
    public function settings($value)
    {
        $this->settings = array_replace($this->settings, $value);
        return $this;
    }

    /**
     * Adds the given input into the tool bar at the bottom.
     * @param  OutputableInterface  $value
     * @return self
     */
    public function addToolInput(OutputableInterface $input)
    {
        $this->toolsTable->addRow()->addElement($input)->addClass('');
        return $this;
    }

    /**
     * Adds the given button to the sidebar of each block.
     * Note: The name of the button is triggered as an event on the Block element when clicked, as function(event, block, button)
     * @param  string  $name
     * @param  string  $title
     * @param  string  $icon
     * @param  string  $function
     * @return self
     */
    public function addBlockButton($name, $title, $icon, $class = '')
    {
        $iconPath = './themes/'.$this->session->get("gibbonThemeName").'/img/';
        $iconSrc = stripos($icon, '/') === false? $iconPath.$icon : $icon;
        
        $button = $this->factory->createWebLink(sprintf('<img title=%1$s src="%2$s" style="margin-right:4px;" />', $title, $iconSrc))
            ->setURL('#')
            ->addClass('blockButton');

        if (!empty($name)) $button->addData('event', $name);
        if (!empty($class)) $button->addClass($class);

        if ($name == 'showHide') {
            $button->addData('on', $iconPath.'minus.png');
            $button->addData('off', $iconPath.'plus.png');
        }

        $this->blockButtons->addCell()->addElement($button);
        return $this;
    }

    public function removeBlockButton($name)
    {

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
     * Add a set of data that a new block can be created from via an identifier + add block trigger.
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

        $output .= '<div class="customBlocks '.($this->compact ? 'compact' : '').'" id="' . $this->name. '">';

            $output .= '<input type="hidden" class="blockCount" name="'.$this->name.'Count" value="0" />';
            $output .= '<div class="blockPlaceholder" style="'.(count($this->settings['currentBlocks']) > 0 ? 'display: none;' : '').'">'.$this->settings['placeholder'].'</div>';
   
            $output .= '<div class="blockTemplate relative '.($this->compact ? 'compact h-min' : '').'" style="display: none;">';
                $output .= '<div class="blockInputs flex py-2 pr-10">';
                $output .= $this->getTemplateOutput($this->blockTemplate);
                $output .= '</div>';

                $output .= '<div class="blockSidebar absolute top-0 right-0 mt-2">';
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
        // Look for and jsonify all nested validations recursivly
        $addValidation = function($element) use (&$addValidation) {
            if (method_exists($element, 'getElements')) {
                foreach ($element->getElements() as $innerElement) {
                    $addValidation($innerElement);
                }
            }

            if ($element instanceof Input && $element->hasValidation()) {
                // Trigger the output before getting validations: some Inputs add these on getOutput();
                $elementOutput = $element->getOutput();
                $element->addData('validation', $element->getValidationAsJSON());
            }
        };

        $addValidation($template);

        return $template->getOutput();
    }
}
