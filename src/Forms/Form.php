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

namespace Gibbon\Forms;

use Gibbon\Http\Url;
use Gibbon\Tables\Action;
use Gibbon\Forms\View\FormBlankView;
use Gibbon\Forms\View\FormTableView;
use Gibbon\Forms\FormFactoryInterface;
use Gibbon\Forms\View\FormRendererInterface;
use Gibbon\Forms\Traits\BasicAttributesTrait;
use League\Container\ContainerAwareTrait;

/**
 * Form
 *
 * @version v14
 * @since   v14
 */
class Form implements OutputableInterface
{
    use BasicAttributesTrait;
    use ContainerAwareTrait;

    protected $title;
    protected $description;
    protected $factory;
    protected $renderer;

    protected $meta;
    protected $metaData = [];

    protected $rows = [];
    protected $triggers = [];
    protected $values = [];
    protected $header = [];
    protected $steps = [];
    protected $step = null;

    /**
     * Create a form with a specific factory and renderer.
     * @param    FormFactoryInterface   $factory
     * @param    FormRendererInterface  $renderer
     * @param    string                 $action
     * @param    string                 $method
     */
    public function __construct(FormFactoryInterface $factory, FormRendererInterface $renderer, $action = '', $method = 'post')
    {
        $this->factory = $factory;
        $this->renderer = $renderer;
        $this->setAttribute('action', ltrim($action, '/'));
        $this->setAttribute('method', $method);
        $this->setAttribute('autocomplete', 'on');
        $this->setAttribute('enctype', 'multipart/form-data');
    }

    /**
     * Create a form with the default factory and renderer.
     * @param    string  $id
     * @param    string  $action
     * @param    string  $method
     * @param    string  $class
     * @return   object  Form object
     */
    public static function create($id, $action, $method = 'post', $class = 'standardForm')
    {
        global $container;

        $form = $container->get(Form::class)
            ->setID($id)
            ->setClass($class)
            ->setAction($action)
            ->setMethod($method);

        // Enable quick save by default on edit and settings pages
        if ($form->checkActionList($action, ['settingsProcess', 'editProcess'])) {
            $form->enableQuickSave();
        }

        // Add meta sidebar by default
        if ($form->checkActionList($action, ['addProcess', 'addMultiProcess', 'addMultipleProcess', 'editProcess', 'duplicate'])) {
            $form->addMeta()->addDefaultContent($action);
        }

        return $form;
    }

    public static function createSearch($id = 'search', $action = '', $method = 'get', $class = '')
    {
        $form = static::create($id, $action ?? Url::fromRoute(), $method, $class);
        $form->renderer->setTemplate('components/formSearch.twig.html');
        $form->addHiddenValue('q', $_GET['q']);

        $form->setAttribute('hx-get', Url::fromRoute())
            ->setAttribute('hx-trigger', 'submit')
            ->setAttribute('hx-select', '#content-inner')
            ->setAttribute('hx-target', '#content-inner')
            ->setAttribute('hx-swap', 'outerHTML show:none swap:0.2s')
            ->setAttribute('x-on:htmx:before-request', 'submitting = true')
            ->setAttribute('x-on:htmx:after-swap', 'submitting = false');

        return $form;
    }

    public static function createTable($id, $action, $method = 'post', $class = 'smallIntBorder w-full')
    {
        $form = static::create($id, $action, $method, $class);
        $form->renderer->setTemplate('components/formTable.twig.html');

        return $form;
    }

    public static function createBlank($id, $action = '', $method = 'post', $class = '')
    {
        $form = static::create($id, $action, $method, $class);
        $form->renderer->setTemplate('components/formBlank.twig.html');

        return $form;
    }

    protected function checkActionList($actionString, $validActions)
    {
        foreach ($validActions as $action) {
            if (stripos($actionString, $action) !== false) return true;
        }
        return false;
    }

    /**
     * Get the form title.
     * @return  string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the form title.
     * @param  string  $title
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the form description.
     * @return  string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the form description.
     * @param  string  $description
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the current factory.
     * @return  object FormFactoryInterface
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Set the factory.
     * @param  FormFactoryInterface  $factory
     */
    public function setFactory(FormFactoryInterface $factory)
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * Get the current renderer.
     * @return  object FormRendererInterface
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    /**
     * Set the renderer.
     * @param  FormRendererInterface  $renderer
     */
    public function setRenderer(FormRendererInterface $renderer)
    {
        $this->renderer = $renderer;

        return $this;
    }

    /**
     * Get the current HTTP method for this form (default: post)
     * @return  string
     */
    public function getMethod()
    {
        return $this->getAttribute('method');
    }

    public function setMethod(string $method)
    {
        $this->setAttribute('method', $method);

        return $this;
    }

    /**
     * Get the current action URL for the form.
     * @return  string
     */
    public function getAction()
    {
        return $this->getAttribute('action');
    }

    public function setAction(string $action)
    {
        $this->setAttribute('action', $action);

        return $this;
    }

    /**
     * Adds a Row object to the form and returns it.
     * @param  string  $id
     * @return object Row
     */
    public function addRow($id = '')
    {
        $section = !empty($this->rows) ? end($this->rows)->getHeading() : '';
        $row = $this->factory->createRow($id)->setHeading($section);

        $this->rows[] = $row;

        return $row;
    }

    /**
     * Cet the last added Row object, null if none exist.
     * @return  object|null
     */
    public function getRow()
    {
        return (!empty($this->rows))? end($this->rows) : null;
    }

    /**
     * Get an array of all Row objects in the form.
     * @return  array
     */
    public function getRows()
    {
        return array_filter($this->rows, function ($item) {
            return !empty($item->getElements());
        });
    }

    /**
     * Get the total number of rows in a form.
     * @return  array
     */
    public function getRowCount()
    {
        return count($this->rows);
    }

    /**
     * Gets the rows in the form, grouped by heading.
     *
     * @return array
     */
    public function getRowsByHeading()
    {
        return array_reduce($this->rows, function ($group, $row) {
            // if ($row->getElementCount() == 0) return $group;
            $group[$row->getHeading()][] = $row;
            return $group;
        }, []);
    }

    /**
     * Checks whether a given heading exists in the form. Used by the form builder.
     *
     * @param string $heading
     * @return bool
     */
    public function hasHeading(string $heading)
    {
        return count(array_filter($this->rows, function ($row) use ($heading) {
            return $row->getHeading() == $heading;
        }));
    }

    /**
     * Adds a Meta object to the form and returns it.
     * @param  string  $id
     * @return object Meta
     */
    public function addMeta()
    {
        if (empty($this->meta)) {
            $this->meta = $this->factory->createMeta();
        }

        return $this->meta;
    }

    /**
     * Get the Meta object, if it exists.
     * @return  object|null
     */
    public function getMeta()
    {
        return !empty($this->meta)? $this->meta : null;
    }

    /**
     * Gets whether the Meta object exists.
     * @return  object|null
     */
    public function hasMeta()
    {
        return !empty($this->meta);
    }

    /**
     * Removes the Meta object from this form.
     * @return  self
     */
    public function removeMeta()
    {
        $this->meta = null;

        return $this;
    }


    /**
     * Adds an input type=hidden value to the form.
     * @param  string  $name
     * @param  string  $value
     */
    public function addHiddenValue($name, $value)
    {
        $this->values[] = array('name' => $name, 'value' => $value);

        return $this;
    }

    /**
     * Adds a key => value array of input type=hidden values.
     * @param  array  $array
     */
    public function addHiddenValues(array $array)
    {
        foreach ($array as $name => $value) {
            $this->addHiddenValue($name, $value);
        }

        return $this;
    }

    /**
     * Get an array of all hidden values.
     * @return  array
     */
    public function getHiddenValues()
    {
        return $this->values;
    }

    /**
     * Get the value of the autocomplete HTML form attribute.
     * @return  string
     */
    public function getAutocomplete()
    {
        return $this->getAttribute('autocomplete');
    }

    /**
     * Turn autocomplete for the form On or Off.
     * @param  string  $value
     * @return self
     */
    public function setAutocomplete($value)
    {
        if (is_bool($value)) {
            $value = $value? 'on' : 'off';
        }

        $this->setAttribute('autocomplete', $value);

        return $this;
    }

    /**
     * Add a confirmation message to display before form submission.
     * @param string $message
     * @return self
     */
    public function addConfirmation($message)
    {
        $this->setAttribute('onsubmit', "return confirm(\"".__($message)."\")");

        return $this;
    }

    /**
     * setOnKeyDown
     * Add a javascript function to the form's onkeydown event.
     * @param string $function
     * @return self
     */
    public function enableAutoSave(string $formId, string $autoSaveUrl)
    {
        $keydownJS = "gibbonFormSubmitQuiet($('#$formId'), '$autoSaveUrl')";
        $this->setAttribute('onkeydown', $keydownJS);
        return $this;
    }

    /**
     * Enables submitting the form and reloading without a page refresh.
     * @return self
     */
    public function enableQuickSave()
    {
        $this->renderer->addData('quickSave', true);
        return $this;
    }

    /**
     * Enables submitting the form and reloading without a page refresh.
     * @return self
     */
    public function enableQuickSubmit()
    {     
        return $this->setAttribute($this->getMethod() == 'post' ? 'hx-post' : 'hx-get', $this->getAction())
            ->setAttribute('hx-trigger', 'submit')
            ->setAttribute('hx-select', '#content-wrap')
            ->setAttribute('hx-target', '#content-wrap')
            ->setAttribute('hx-replace-url', 'true')
            ->setAttribute('hx-swap', 'outerHTML show:none swap:0.2s')
            ->setAttribute('x-on:htmx:before-request', 'submitting = true')
            ->setAttribute('x-on:htmx:after-swap', 'submitting = false');
    }

    /**
     * Adds a Trigger object that injects javascript to respond to form events.
     * @param  string  $selector
     * @param  object  $trigger
     */
    public function addTrigger($selector, $trigger)
    {
        $this->triggers[$selector] = $trigger;

        return $trigger;
    }

    /**
     * Get an array of all Trigger objects.
     * @return  array
     */
    public function getTriggers()
    {
        return $this->triggers;
    }

    /**
     * Clear all Trigger objects.
     * @return  static
     */
    public function clearTriggers()
    {
        $this->triggers = [];
        return $this;
    }

    /**
     * Adds a visibility trigger to the form by class name.
     * @param   string  $class Element name
     * @return  object Trigger
     */
    public function toggleVisibilityByClass($class)
    {
        $selector = '.'.$class;

        return $this->addTrigger($selector, $this->factory->createTrigger($selector));
    }

    /**
     * Adds a visibility trigger to the form by element ID.
     * @param   string  $id CSS Element ID
     * @return  object Trigger
     */
    public function toggleVisibilityByID($id)
    {
        $selector = '#'.$id;

        return $this->addTrigger($selector, $this->factory->createTrigger($selector));
    }

    /**
     * Enables displaying a multi-part form progress indicator.
     *
     * @param array $steps
     * @param int $currentStep
     * @return self
     */
    public function setMultiPartForm(array $steps, int $currentStep = 1)
    {
        $this->steps = $steps;
        $this->step = $currentStep;

        return $this;
    }

    public function getMultiPartSteps()
    {
        return $this->steps;
    }

    public function getCurrentStep()
    {
        return $this->step;
    }

    /**
     * Loads an array of $key => $value pairs into any form elements with a matching name.
     * @param   array  $data
     * @return  self
     */
    public function loadAllValuesFrom($data)
    {
        foreach ($this->getRows() as $row) {
            $row->loadFrom($data);
        }

        if ($this->hasMeta()) {
            $this->getMeta()->loadFrom($data);
        }

        return $this;
    }

    /**
     * Loads the state for several form elements by calling $method with values from $data.
     * @param string $method
     * @param array $data
     * @return void
     */
    public function loadStateFrom($method, $data)
    {
        foreach ($this->getRows() as $row) {
            $row->loadState($method, $data);
        }

        return $this;
    }

    /**
     * Add an action to the form, generally displayed in the header right-hand side.
     *
     * @param string $name
     * @param string $label
     * @return Action
     */
    public function addHeaderAction($name, $label = '')
    {
        $this->header[$name] = (new Action($name, $label))->displayLabel(true);

        return $this->header[$name];
    }

    /**
     * Get all header content in the table.
     *
     * @return array
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Renders the form to HTML.
     * @return  string
     */
    public function getOutput()
    {
        return $this->renderer->renderForm($this);
    }
}
