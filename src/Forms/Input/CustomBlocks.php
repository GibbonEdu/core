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
use Gibbon\View\Component;
use Gibbon\Forms\Input\Editor;
use Gibbon\Forms\Layout\NullElement;

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
    public function __construct(FormFactoryInterface &$factory, $name, ?Session $session = null, bool $canDelete = true, bool $canCopy = true, bool $canAdd = false)
    {
        $this->factory = $factory;
        $this->session = $session;
        $this->name = $name;
        $this->setID($name);
        $this->setClass('my-4');

        $this->toolsTable = $factory->createRow()->setClass('flex w-full items-center justify-start gap-2');
        $this->blockButtons = $factory->createGrid()->setClass('flex flex-row-reverse items-center pr-1')->setAttribute('x-sort:ignore');
        $this->blockTemplate = $factory->createRow()->setClass('w-full px-2 sm:px-3');

        $this->settings = [
            'placeholder'      => __('Blocks will appear here...'),
            'deleteMessage'    => __('Are you sure you want to delete this record?'),
            'duplicateMessage' => __('This element has already been selected!'),
            'currentBlocks'    => [],
            'addOnEvent'       => 'click',
        ];

        if ($canDelete) $this->addBlockButton('delete', __('Delete'));
        if ($canCopy) $this->addBlockButton('copy', __('Duplicate'));
        if ($canAdd) $this->addToolButton(__('Add'))->addClass('addBlock')->setIcon('solid', 'add');
        
    }

    /**
     * Set a predefined layout using OutputableInterface which will be cloned for each new block.
     * @param OutputableInterface $block
     * @return self
     */
    public function fromTemplate(OutputableInterface $block, $compact = false)
    {
        $this->blockTemplate = $block->setClass('w-full noBorder');
        $this->compact = $compact;
        return $this;
    }

    public function getTemplate()
    {
        return $this->blockTemplate;
    }

    public function addTemplateRow()
    {
        return $this->blockTemplate->addRow()->setClass('w-full py-3 flex flex-col sm:flex-row content-center p-0 gap-2 sm:gap-4 justify-between sm:items-start');
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
        if ($input instanceof Button || $this->settings['addOnEvent'] == 'click') {
            $input->setAttribute('@click', 'handleToolClick($el)');
        } elseif ($input instanceof Input) {
            $input->setAttribute('@change', 'handleToolChange($el)');
        }
        
        $this->toolsTable->addElement($input);
        return $this;
    }

    /**
     * Adds a pre-made button and returns the resulting element.
     *
     * @param string $label
     * @param string $class
     * @return Button
     */
    public function addToolButton(string $label, string $class = '')
    {
        $button = $this->factory->createButton($label)->addClass($class);
        $this->addToolInput($button);

        return $button;
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
    public function addBlockButton($name, $title, $icon = '', $class = '')
    {
        $button = $this->factory->createAction($name, $title)
            ->modalWindow(false)
            ->setURL('#')
            ->addClass('blockButton')
            ->displayLabel(false)
            ->setType('interface')
            ->setAttribute('@click', 'handleButtonClick($el, index)');

        if (!empty($name)) $button->addData('event', $name);
        if (!empty($class)) $button->addClass($class);
        if ($name == 'showHide') $button->setIcon('view');

        $this->blockButtons->addCell()->addElement($button);
        return $this;
    }

    /**
     * Adds a block from an array of data.
     * @param  string  $id
     * @param  array   $data
     * @return self
     */
    public function addBlock($id, array $data = [])
    {
        $this->settings['currentBlocks'][$id] = $data;

        return $this;
    }

    /**
     * Adds multiple blocks from an array.
     * @param  array   $blocks
     * @return self
     */
    public function addBlocks(array $blocks = [])
    {
        foreach ($blocks as $id => $data) {
            $this->settings['currentBlocks'][$id] = $data;
        }

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
        // TODO: FL copy blocks
        // TODO: internal toggle states

        $index = $this->settings['indexStart'] ?? 0;
        $blocks = [];
        foreach ($this->settings['currentBlocks'] as $key => $block) {
            $block['id'] = $this->getID().$index;
            $block['index'] = $index;
            $blocks[] = $block;
            $index++;
        }

        return Component::render(CustomBlocks::class, [
            'indexNext'        => $this->settings['indexNext'] ?? $index,
            'name'             => $this->name,
            'compact'          => $this->compact,
            'currentBlocks'    => $blocks,
            'blockCount'       => count($blocks),
            'predefinedBlocks' => $this->settings['predefinedBlocks'] ?? [],
            'sortable'         => $this->settings['sortable'] ?? true,
            'sortGroup'        => $this->settings['sortGroup'] ?? $this->name,
            'placeholder'      => $this->settings['placeholder'] ?? '',
            'deleteMessage'    => $this->settings['deleteMessage'],
            'orderName'        => $this->settings['orderName'] ?? 'order',
            'blockTemplate'    => $this->getTemplateOutput($this->blockTemplate),
            'blockButtons'     => $this->blockButtons->getOutput(),
            'editors'          => array_unique($this->settings['editors'] ?? []),
            'uniqueID'         => $this->settings['uniqueID'] ?? 'unique',
            'hiddenInputs'     => $this->settings['hiddenInputs'] ?? [],
            'primaryInput'     => $this->settings['primaryInput'] ?? 'title',
            'toolsTable'       => $this->toolsTable->getOutput(),
        ] + $this->getAttributeArray());
    }

    /**
     * @param  OutputableInterface $template
     * @return string 
     */
    protected function getTemplateOutput(OutputableInterface $template)
    {
        $blockInputs = ['orderName'];
        $strategy = $this->settings['inputNameStrategy'] ?? 'object';

        $addValidation = function($element) use (&$addValidation, &$blockInputs, &$strategy) {
            if (method_exists($element, 'getElements')) {
                foreach ($element->getElements() as $innerElement) {
                    $addValidation($innerElement);
                }
            }

            $class = $element->getClass();
            if (!empty($class) && stripos($class, 'showHide') !== false) {
                $element->setAttribute('x-show', 'block.show');
                $element->setAttribute('x-transition.opacity');
            }

            if (!empty($element->getID())) {
                $element->setAttribute('x-bind:id', "'".$element->getID()."' + block.index");
                $element->setPrepended('');
                $element->setAppended('');
            }

            if ($element instanceof Input) {
                $blockInputs[] = $element->getName();
                $id = !empty($element->getID()) ? $element->getID() : $element->getName();
                $name = $element->getName();

                if (empty($this->settings['primaryInput']) && $element instanceof TextField) {
                    $element->setAttribute('x-model', 'block.'.$name);
                    $element->setAttribute('value', 'block.'.$name);
                    $this->settings['primaryInput'] = $name;
                } elseif ($element->getData('tinymce') !== null) {
                    $element->setAttribute('x-text', 'block.'.$name);
                } else {
                    $element->setAttribute('x-bind:value', 'block.'.$name);
                }

                if ($strategy == 'string') {
                    $element->setAttribute('x-bind:name', "'".$id."' + block.index");
                } else {
                    $element->setAttribute('x-bind:name', "'".$this->name."[' + block.index + '][".$id."]'");
                }
                
                if ($element instanceof Checkbox && $element->getOptionCount() > 0) {
                    $element->setAttribute('x-bind:name', $strategy == 'string'
                        ? "'".$id."' + block.index + '[]'"
                        : "'".$this->name."[' + block.index + '][".$id."][]'"
                    );
                }

                if ($element instanceof Radio) {
                    $element->setAttribute('x-bind:checked', 'block.'.$name.' == $el.value');
                }

                if ($element instanceof Person || $element instanceof SearchSelect) {
                    $element->setAttribute('x-init', '$data.setSelectedOption($el.selectedOptions[0])');
                }

                if ($element instanceof Color) {
                    $element->setAttribute('x-init', '$data.colorSelected = $el.value');
                }

                if ($element instanceof Time) {
                    $element->setAttribute('x-init', "$(\$el).timepicker({
                    'scrollDefault': 'now',
                    'timeFormat': 'H:i',
                    })");
                }

                if ($element instanceof Editor || $element->getData('tinymce') !== null) {
                    $this->settings['editors'][] = $name;
                    $element->setClass('tinymce');
                    $element->setOuterClass('editor-full');
                    $element->setAttribute('data-name', $name);
                    $element->setAttribute('data-rows', $element->getAttribute('rows'));
                }
            }
        };

        $addValidation($template);


        $blockFields = !empty($this->settings['currentBlocks']) ? current($this->settings['currentBlocks']) : [];
        $hiddenInputList = !empty($this->settings['hiddenInputs']) ? explode(',', $this->settings['hiddenInputs']) : [];
        $hiddenInputs = array_merge(array_diff(array_keys($blockFields), $blockInputs), $hiddenInputList);
        $indexStart = $this->settings['indexStart'] ?? 0;

        $this->settings['hiddenInputs'] = [];
        foreach ($hiddenInputs as $inputName) {
            if (empty($inputName)) continue;
            $this->settings['hiddenInputs'][$inputName] = $strategy == 'string' 
                ? "'".$inputName."' + block.index "
                : "'".$this->name."[' + block.index + '][".$inputName."]'";
        }
        
    

        return $template->getOutput();
    }
}
