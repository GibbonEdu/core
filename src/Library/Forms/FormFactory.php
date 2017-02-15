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

namespace Library\Forms;

/**
 * FormFactory
 *
 * @version v14
 * @since   v14
 */
class FormFactory
{
    public function __construct()
    {
    }

    public function createRow($id)
    {
        return new \Library\Forms\Row($this, $id);
    }

    public function createContent($content)
    {
        return new \Library\Forms\Layout\Content($content);
    }

    public function createLabel($row, $for, $label)
    {
        return new \Library\Forms\Layout\Label($row, $for, $label);
    }

    public function createTextArea($name)
    {
        return new \Library\Forms\Input\TextArea($name);
    }

    public function createTextField($name)
    {
        return new \Library\Forms\Input\TextField($name);
    }

    public function createEmail($name)
    {
        return (new \Library\Forms\Input\TextField($name))->addValidation('Validate.Email');
    }

    public function createURL($name)
    {
        return (new \Library\Forms\Input\TextField($name) )
            ->placeholder('http://')
            ->addValidation(
                'Validate.Format',
                'pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://"'
            );
    }

    public function createNumber($name)
    {
        return new \Library\Forms\Input\Number($name);
    }

    public function createPassword($name)
    {
        return new \Library\Forms\Input\Password($name);
    }

    public function createFileUpload($name)
    {
        return new \Library\Forms\Input\FileUpload($name);
    }

    public function createDate($name)
    {
        return new \Library\Forms\Input\Date($name);
    }

    public function createCheckbox($name)
    {
        return (new \Library\Forms\Input\Checkbox($name))->setClass('right');
    }

    public function createRadio($name)
    {
        return (new \Library\Forms\Input\Radio($name))->setClass('right');
    }

    public function createSelect($name)
    {
        return new \Library\Forms\Input\Select($name);
    }

    public function createSelectSchoolYear(\Gibbon\sqlConnection $pdo, $name)
    {
        $sql = 'SELECT gibbonSchoolYearID as `value`, name FROM gibbonSchoolYear ORDER BY sequenceNumber';
        $results = $pdo->executeQuery(array(), $sql);

        return $this->createSelect($name)->fromResults($results)->placeholder('Please select...');
    }

    public function createSelectLanguage(\Gibbon\sqlConnection $pdo, $name)
    {
        $sql = 'SELECT name as `value`, name FROM gibbonLanguage ORDER BY name';
        $results = $pdo->executeQuery(array(), $sql);

        return $this->createSelect($name)->fromResults($results)->placeholder('Please select...');
    }

    public function createSelectStaff(\Gibbon\sqlConnection $pdo, $name)
    {

        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName";

        $results = $pdo->executeQuery(array(), $sql);

        $values = array();
        if ($results && $results->rowCount() > 0) {
            while ($row = $results->fetch()) {
                $values[$row['gibbonPersonID']] = formatName(htmlPrep($row['title']), ($row['preferredName']), htmlPrep($row['surname']), 'Staff', true, true);
            }
        }

        return $this->createSelect($name)->fromArray($values);
    }

    public function createYesNo($name)
    {
        return $this->createSelect($name)->fromArray(array( 'Y' => 'Yes', 'N' => 'No'));
    }

    public function createHeading($label)
    {
        //$this->setClass('break');
        $content = sprintf('<h3>%s</h3>', __($label));
        return $this->createContent($content);
    }

    public function createSubheading($label)
    {
        $content = sprintf('<h4>%s</h4>', __($label));
        return $this->createContent($content);
    }

    public function createAlert($content, $level = 'warning')
    {
        $content = sprintf('<div class="%s">%s</div>', $level, $content);
        return $this->createContent($content);
    }

    public function createSubmit($label = 'Submit')
    {
        $content = sprintf('<input type="submit" value="%s">', __($label));
        return $this->createContent($content)->setClass('right');
    }

    public function createButton($label = 'Button', $onClick = '')
    {
        $content = sprintf('<input type="button" value="%s" onClick="%s">', __($label), $onClick);
        return $this->createContent($content)->setClass('right');
    }
}
