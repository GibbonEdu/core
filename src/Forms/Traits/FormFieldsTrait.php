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

namespace Gibbon\Forms\Traits;

use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\Input\Input;
use Gibbon\Forms\OutputableInterface;
use Gibbon\Forms\RowDependancyInterface;

/**
 * Dynamically add form fields via the factory.
 *
 * @version v30
 * @since   v30
 * 
 * @method \Gibbon\Forms\Layout\Row addRow($id = '') {@see \Gibbon\Forms\Layout\Row}
 * @method \Gibbon\Forms\Layout\Column addColumn($id = '') {@see \Gibbon\Forms\Layout\Column}
 * @method \Gibbon\Forms\Layout\Meta addMeta() {@see \Gibbon\Forms\Layout\Meta}
 * @method \Gibbon\Forms\Layout\Table addTable($id = '') {@see \Gibbon\Forms\Layout\Table}
 * @method \Gibbon\Forms\Layout\DataTable addDataTable($id, $criteria = null) {@see \Gibbon\Forms\Layout\DataTable}
 * @method \Gibbon\Forms\Layout\TableCell addTableCell($content = '') {@see \Gibbon\Forms\Layout\TableCell}
 * @method \Gibbon\Forms\Layout\Grid addGrid($id = '', $columns = 1) {@see \Gibbon\Forms\Layout\Grid}
 * @method \Gibbon\Forms\Layout\Details addDetails($id = '') {@see \Gibbon\Forms\Layout\Details}
 * @method \Gibbon\Forms\Layout\Trigger addTrigger($selector = '') {@see \Gibbon\Forms\Layout\Trigger}
 * @method \Gibbon\Forms\Layout\Label addLabel($for, $label) {@see \Gibbon\Forms\Layout\Label}
 * @method \Gibbon\Forms\Layout\Heading addHeading($id = '', $content = null) {@see \Gibbon\Forms\Layout\Heading}
 * @method \Gibbon\Forms\Layout\Heading addSubheading($id = '', $content = null) {@see \Gibbon\Forms\Layout\Heading}
 * @method \Gibbon\Forms\Layout\Element addContent($content = '') {@see \Gibbon\Forms\Layout\Element}
 * @method \Gibbon\Forms\Layout\WebLink addWebLink($content = '') {@see \Gibbon\Forms\Layout\WebLink}
 * @method \Gibbon\Forms\Layout\Action addAction($name, $label = '') {@see \Gibbon\Forms\Layout\Action}
 * 
 * @method \Gibbon\Forms\Input\CustomField addCustomField(string $name, array $fields = []) {@see \Gibbon\Forms\Input\CustomField}
 * @method \Gibbon\Forms\Input\TextArea addTextArea(string $name) {@see \Gibbon\Forms\Input\TextArea}
 * @method \Gibbon\Forms\Input\TextField addTextField(string $name, string $label) {@see \Gibbon\Forms\Input\TextField}
 * @method \Gibbon\Forms\Input\Range addRange() {@see \Gibbon\Forms\Input\Range}
 * @method \Gibbon\Forms\Input\Color addColor(string $name) {@see \Gibbon\Forms\Input\Color}
 * @method \Gibbon\Forms\Input\Finder addFinder(string $name) {@see \Gibbon\Forms\Input\Finder}
 * @method \Gibbon\Forms\Input\Editor addEditor(string $name) {@see \Gibbon\Forms\Input\Editor}
 * @method \Gibbon\Forms\Input\CodeEditor addCodeEditor(string $name) {@see \Gibbon\Forms\Input\CodeEditor}
 * @method \Gibbon\Forms\Input\CommentEditor addCommentEditor(string $name) {@see \Gibbon\Forms\Input\CommentEditor}
 * @method \Gibbon\Forms\Input\TextField addEmail(string $name) {@see \Gibbon\Forms\Input\TextField}
 * @method \Gibbon\Forms\Input\TextField addURL(string $name) {@see \Gibbon\Forms\Input\TextField}
 * @method \Gibbon\Forms\Input\Number addNumber(string $name) {@see \Gibbon\Forms\Input\Number}
 * @method \Gibbon\Forms\Input\Currency addCurrency(string $name) {@see \Gibbon\Forms\Input\Currency}
 * @method \Gibbon\Forms\Input\Password addPassword(string $name) {@see \Gibbon\Forms\Input\Password}
 * @method \Gibbon\Forms\Input\FileUpload addFileUpload(string $name) {@see \Gibbon\Forms\Input\FileUpload}
 * @method \Gibbon\Forms\Input\Date addDate(string $name) {@see \Gibbon\Forms\Input\Date}
 * @method \Gibbon\Forms\Input\Time addTime(string $name) {@see \Gibbon\Forms\Input\Time}
 * @method \Gibbon\Forms\Input\Checkbox addCheckbox(string $name) {@see \Gibbon\Forms\Input\Checkbox}
 * @method \Gibbon\Forms\Input\Radio addRadio(string $name) {@see \Gibbon\Forms\Input\Radio}
 * @method \Gibbon\Forms\Input\Toggle addToggle(string $name) {@see \Gibbon\Forms\Input\Toggle}
 * @method \Gibbon\Forms\Input\Select addSelect(string $name) {@see \Gibbon\Forms\Input\Select}
 * @method \Gibbon\Forms\Input\MultiSelect addMultiSelect(string $name) {@see \Gibbon\Forms\Input\MultiSelect}
 * @method \Gibbon\Forms\Input\Button addButton(string $label = 'Button', $onClick = null, $id = null) {@see \Gibbon\Forms\Input\Button}
 * @method \Gibbon\Forms\Input\CustomBlocks addCustomBlocks($name, Session $session, bool $canDelete = true) {@see \Gibbon\Forms\Input\CustomBlocks}
 * @method \Gibbon\Forms\Input\Documents addDocuments($name, $documents, $view, $absoluteURL, $mode = '') {@see \Gibbon\Forms\Input\Documents}
 * @method \Gibbon\Forms\Input\PersonalDocuments addPersonalDocuments($name, $documents, $view, $settingGateway) {@see \Gibbon\Forms\Input\PersonalDocuments}
 * @method \Gibbon\Forms\Input\Username addUsername(string $name) {@see \Gibbon\Forms\Input\Username}
 * @method \Gibbon\Forms\Input\Person addSelectPerson(string $name) {@see \Gibbon\Forms\Input\Person}
 * @method \Gibbon\Forms\Input\Scanner addScanner(string $name) {@see \Gibbon\Forms\Input\Scanner}
 * 
 * @method \Gibbon\Forms\Layout\Element createAlert(string $content, $level = 'warning') {@see \Gibbon\Forms\FormFactory::createAlert() }
 * @method \Gibbon\Forms\Layout\Button createSubmit($label = 'Submit', $id = null) {@see \Gibbon\Forms\FormFactory::createSubmit() }
 * @method \Gibbon\Forms\Layout\Button createSearchSubmit($session, $clearLabel = 'Clear Filters', $passParams = []) {@see \Gibbon\Forms\FormFactory::createSearchSubmit() }
 * @method \Gibbon\Forms\Layout\Button createConfirmSubmit($label = 'Yes', $cancel = false) {@see \Gibbon\Forms\FormFactory::createConfirmSubmit() }
 * @method \Gibbon\Forms\Layout\Button createAdvancedOptionsToggle() {@see \Gibbon\Forms\FormFactory::createAdvancedOptionsToggle() }
 * @method \Gibbon\Forms\Layout\Button createFooter($required = true) {@see \Gibbon\Forms\FormFactory::createFooter() }

 * @method \Gibbon\Forms\Input\Toggle addYesNo(string $name) {@see \Gibbon\Forms\FormFactory::createYesNo() }
 * @method \Gibbon\Forms\Input\Toggle addYesNoRadio(string $name) {@see \Gibbon\Forms\FormFactory::createYesNoRadio() }
 * @method \Gibbon\Forms\Input\Checkbox addCheckAll(string $name) {@see \Gibbon\Forms\FormFactory::createCheckAll() }
 * @method \Gibbon\Forms\Input\Select addSelectTitle(string $name) {@see \Gibbon\Forms\FormFactory::createSelectTitle() }
 * @method \Gibbon\Forms\Input\Select addSelectGender(string $name) {@see \Gibbon\Forms\FormFactory::createSelectGender() }
 * @method \Gibbon\Forms\Input\Select addSelectRelationship(string $name) {@see \Gibbon\Forms\FormFactory::createSelectRelationship() }
 * @method \Gibbon\Forms\Input\Select addSelectEmergencyRelationship(string $name) {@see \Gibbon\Forms\FormFactory::createSelectEmergencyRelationship() }
 * @method \Gibbon\Forms\Input\Select addSelectMaritalStatus(string $name) {@see \Gibbon\Forms\FormFactory::createSelectMaritalStatus() }
 * @method \Gibbon\Forms\Input\Select addSelectSystemLanguage(string $name) {@see \Gibbon\Forms\FormFactory::createSelectSystemLanguage() }
 * @method \Gibbon\Forms\Input\Select addSelectCurrency(string $name) {@see \Gibbon\Forms\FormFactory::createSelectCurrency() }
 */
trait FormFieldsTrait
{
    /**
     * Invoke factory method for creating elements when an "add" method is called on this row.
     * @param   string  $function
     * @param   array   $args
     * @return  object  Element
     */
    public function __call(string $function, array $args)
    {
        if (substr($function, 0, 3) != 'add') {
            return;
        }

        try {
            $function = substr_replace($function, 'create', 0, 3);
            
            $reflectionMethod = new \ReflectionMethod($this->factory, $function);

            $element = $reflectionMethod->invokeArgs($this->factory, $args);

            if ($this instanceof Row && $function == 'createSubmit') {
                $this->setHeading('submit');
            }

        } catch (\ReflectionException $e) {
            $element = $this->factory->createContent(strtr('Cannot {function}. This form element does not exist in the current FormFactory: {message}', [
                '{function}' => $function,
                '{message}' => $e->getMessage(),
            ]));
        } catch (\Exception $e) {
            $element = $this->factory->createContent(strtr('Cannot {function}. Error creating form element: {message}', [
                '{function}' => $function,
                '{message}' => $e->getMessage(),
            ]));
        } finally {
            if (!($element instanceof OutputableInterface)) {
                if (($element_type = gettype($element)) === 'object') $element_type = get_class($element);
                $element = $this->factory->createContent(strtr('{function} returned {type} instead of an outputable form element.', [
                    '{type}' => $element_type,
                    '{function}' => $function,
                ]));
            }
           
        }

        return $this->addElement($element);
    }
}
