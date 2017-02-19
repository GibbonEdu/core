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

namespace Gibbon\Forms;

use Gibbon\Forms\FormFactoryInterface;

/**
 * FormFactory
 *
 * Handles Form object creation, including pre-defined elements. Replaceable component. Default factory can be extended to add types.
 *
 * @version v14
 * @since   v14
 */
class FormFactory implements FormFactoryInterface
{
    public static function create()
    {
        return new FormFactory();
    }

    /* LAYOUT TYPES --------------------------- */

    public function createRow($id = '')
    {
        return new Layout\Row($this, $id);
    }

    public function createColumn($id = '')
    {
        return new Layout\Column($this, $id);
    }

    public function createTrigger($selector = '')
    {
        return new Layout\Trigger($selector);
    }

    public function createLabel($for, $label)
    {
        return new Layout\Label($for, $label);
    }

    public function createHeading($content = '')
    {
        return new Layout\Heading($content);
    }

    public function createContent($content = '')
    {
        return (new Layout\Element())->setContent($content);
    }

    /* BASIC INPUT --------------------------- */

    public function createTextArea($name)
    {
        return new Input\TextArea($name);
    }

    public function createTextField($name)
    {
        return new Input\TextField($name);
    }

    public function createEditor($name, $guid)
    {
        return new Input\Editor($name, $guid);
    }

    public function createEmail($name)
    {
        return (new Input\TextField($name))->addValidation('Validate.Email');
    }

    public function createURL($name)
    {
        return (new Input\TextField($name) )
            ->placeholder('http://')
            ->addValidation(
                'Validate.Format',
                'pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http:// or https://"'
            );
    }

    public function createNumber($name)
    {
        return new Input\Number($name);
    }

    public function createPassword($name)
    {
        return new Input\Password($name);
    }

    public function createFileUpload($name)
    {
        return new Input\FileUpload($name);
    }

    public function createDate($name)
    {
        return new Input\Date($name);
    }

    public function createTime($name)
    {
        return (new Input\TextField($name) )
            ->placeholder('00:00')
            ->addValidation(
                'Validate.Format',
                'pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm"'
            );
    }

    public function createCheckbox($name)
    {
        return (new Input\Checkbox($name))->setClass('right');
    }

    public function createRadio($name)
    {
        return (new Input\Radio($name))->setClass('right');
    }

    public function createSelect($name)
    {
        return new Input\Select($name);
    }

    /* PRE-DEFINED LAYOUT --------------------------- */

    public function createSubheading($label)
    {
        $content = sprintf('<h4>%s</h4>', __($label));
        return $this->createContent($content);
    }

    public function createAlert($content, $level = 'warning')
    {
        $content = sprintf('<div class="%s">%s</div>', $level, __($content));
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

    public function createFooter()
    {
        $content = '<span class="emphasis small">* '.__('denotes a required field').'</span>';
        return $this->createContent($content);
    }

    /* PRE-DEFINED INPUT --------------------------- */

    public function createYesNo($name)
    {
        return $this->createSelect($name)->fromArray(array( 'Y' => __('Yes'), 'N' => __('No') ));
    }

    public function createSelectSchoolYear(\Gibbon\sqlConnection $pdo, $name)
    {
        $sql = 'SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear ORDER BY sequenceNumber';
        $results = $pdo->executeQuery(array(), $sql);

        return $this->createSelect($name)->fromResults($results)->placeholder('Please select...');
    }

    public function createSelectLanguage(\Gibbon\sqlConnection $pdo, $name)
    {
        $sql = 'SELECT name as value, name FROM gibbonLanguage ORDER BY name';
        $results = $pdo->executeQuery(array(), $sql);

        return $this->createSelect($name)->fromResults($results)->placeholder('Please select...');
    }

    public function createSelectCountry(\Gibbon\sqlConnection $pdo, $name)
    {
        $sql = 'SELECT printable_name as value, printable_name as name FROM gibbonCountry ORDER BY printable_name';
        $results = $pdo->executeQuery(array(), $sql);

        return $this->createSelect($name)->fromResults($results)->placeholder('Please select...');
    }

    public function createSelectStaff(\Gibbon\sqlConnection $pdo, $name)
    {
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName
                FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID)
                WHERE status='Full' ORDER BY surname, preferredName";

        $results = $pdo->executeQuery(array(), $sql);

        $values = array();
        if ($results && $results->rowCount() > 0) {
            while ($row = $results->fetch()) {
                $values[$row['gibbonPersonID']] = formatName(htmlPrep($row['title']), ($row['preferredName']), htmlPrep($row['surname']), 'Staff', true, true);
            }
        }

        return $this->createSelect($name)->fromArray($values);
    }

    public function createSelectCurrency($name)
    {
        // I hate doing this ... was there a YAML file at one point?
        $currencies = array(
            'PAYPAL SUPPORTED' => array(
                'AUD $' => 'Australian Dollar (A$)',
                'BRL R$' => 'Brazilian Real',
                'GBP £' => 'British Pound (£)',
                'CAD $' => 'Canadian Dollar (C$)',
                'CZK Kč' => 'Czech Koruna',
                'DKK kr' => 'Danish Krone',
                'EUR €' => 'Euro (€)',
                'HKD $' => 'Hong Kong Dollar ($)',
                'HUF Ft' => 'Hungarian Forint',
                'ILS ₪' => 'Israeli New Shekel',
                'JPY ¥' => 'Japanese Yen (¥)',
                'MYR RM' => 'Malaysian Ringgit',
                'MXN $' => 'Mexican Peso',
                'TWD $' => 'New Taiwan Dollar',
                'NZD $' => 'New Zealand Dollar ($)',
                'NOK kr' => 'Norwegian Krone',
                'PHP ₱' => 'Philippine Peso',
                'PLN zł' => 'Polish Zloty',
                'SGD $' => 'Singapore Dollar ($)',
                'CHF' => 'Swiss Franc',
                'THB ฿' => 'Thai Baht',
                'TRY' => 'Turkish Lira',
                'USD $' => 'U.S. Dollar ($)',
                ),
            'OTHERS' => array(
                'BDT ó' => 'Bangladeshi Taka (ó)',
                'BTC' => 'Bitcoin',
                'BGN лв.' => 'Bulgarian Lev (лв.)',
                'XAF FCFA' => 'Central African Francs (FCFA)',
                'EGP £' => 'Egyptian Pound (£)',
                'GHS GH₵' => 'Ghanaian Cedi (GH₵)',
                'INR ₹' => 'Indian Rupee₹ (₹)',
                'IDR Rp' => 'Indonesian Rupiah (Rp)',
                'JMD $' => 'Jamaican Dollar ($)',
                'KES KSh' => 'Kenyan Shilling (KSh)',
                'MOP MOP$' => 'Macanese Pataca (MOP$)',
                'MMK K' => 'Myanmar Kyat (K)',
                'NAD N$' => 'Namibian Dollar (N$)',
                'NPR ₨' => 'Nepalese Rupee (₨)',
                'NGN ₦' => 'Nigerian Naira (₦)',
                'PKR ₨' => 'Pakistani Rupee (₨)',
                'SAR ﷼‎' => 'Saudi Riyal (﷼‎)',
                'TZS TSh' => 'Tanzania Shilling (TSh)',
                'VND ₫‎' => 'Vietnamese Dong (₫‎)',
            ),
        );

        return $this->createSelect($name)->fromArray($currencies)->placeholder('Please select...');
    }
}
