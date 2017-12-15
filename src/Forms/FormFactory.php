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
    /**
     * Create and return an instance of FormFactory.
     * @return  object FormFactory
     */
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

    public function createTable($id = '')
    {
        return new Layout\Table($this, $id);
    }

    public function createTableCell($content = '')
    {
        return new Layout\TableCell($content);
    }

    public function createGrid($id = '', $columns = 1)
    {
        return new Layout\Grid($this, $id, $columns);
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
        return new Layout\Element($content);
    }

    public function createWebLink($content = '')
    {
    	return new Layout\WebLink($content);
    }

    /* BASIC INPUT --------------------------- */

    public function createCustomField($name, $fields = array())
    {
        return new Input\CustomField($this, $name, $fields);
    }

    public function createTextArea($name)
    {
        return new Input\TextArea($name);
    }

    public function createTextField($name)
    {
        return new Input\TextField($name);
    }

    public function createFinder($name)
    {
        return new Input\Finder($name);
    }

    public function createEditor($name, $guid)
    {
        return new Input\Editor($name, $guid);
    }

    public function createEmail($name)
    {
        return (new Input\TextField($name))->addValidation('Validate.Email');
    }

    //A URL web link
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

    public function createCurrency($name)
    {
        return new Input\Currency($name);
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
        return new Input\Time($name);
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

    public function createMultiSelect($name)
    {
        return new Input\MultiSelect($this, $name);
    }

    public function createButton($label = 'Button', $onClick = '', $id = null)
    {
        $button = new Input\Button($label, $onClick);
        if(!empty($id)) {
            $button->setID($id)->setName($id);
        }
        
        return $button;
    }

    public function createCustomBlocks($name, OutputableInterface $block, \Gibbon\Session $session)
    {
        return new Input\CustomBlocks($this, $name, $block, $session);
    }

    /* PRE-DEFINED LAYOUT --------------------------- */

    public function createSubheading($content, $tag = 'h4')
    {
        $content = sprintf('<%1$s>%2$s</%1$s>', $tag, $content);
        return $this->createContent($content);
    }

    public function createAlert($content, $level = 'warning')
    {
        return $this->createContent($content)->wrap('<div class="'.$level.'">', '</div>');
    }

    public function createSubmit($label = 'Submit')
    {
        $content = sprintf('<input type="submit" value="%s">', __($label));
        return $this->createContent($content)->setClass('right');
    }

    public function createSearchSubmit($session, $clearLabel = 'Clear Filters', $passParams = array())
    {
        $passParams[] = 'q';
        $parameters = array_intersect_key($_GET, array_flip($passParams));
        $clearURL = $session->get('absoluteURL').'/index.php?'.http_build_query($parameters);
        $clearLink = sprintf('<a href="%s" class="right">%s</a> &nbsp;', $clearURL, __($clearLabel));

        return $this->createSubmit('Go')->prepend($clearLink);
    }

    public function createConfirmSubmit($label = 'Yes', $cancel = false)
    {
        $cancelLink = ($cancel)? sprintf('<a href="%s" class="right">%s</a> &nbsp;', $_SERVER['HTTP_REFERER'], __('Cancel')) : '';
        return $this->createSubmit($label)->prepend($cancelLink);
    }

    public function createFooter($required = true)
    {
        $content = '';
        if ($required) {
            $content = '<span class="emphasis small">* '.__('denotes a required field').'</span>';
        }
        return $this->createContent($content);
    }

    /* PRE-DEFINED INPUT --------------------------- */

    public function createYesNo($name)
    {
        return $this->createSelect($name)->fromArray(array( 'Y' => __('Yes'), 'N' => __('No') ));
    }

    public function createYesNoRadio($name)
    {
        return $this->createRadio($name)->fromArray(array('Y' => __('Yes'), 'N' => __('No') ))->inline(true);
    }

    public function createCheckAll($name = 'checkall')
    {
        return $this->createCheckbox($name)->setClass('floatNone textCenter checkall');
    }

    public function createSelectTitle($name)
    {
        return $this->createSelect($name)->fromArray(array(
            'Ms.'  => __('Ms.'),
            'Miss' => __('Miss'),
            'Mr.'  => __('Mr.'),
            'Mrs.' => __('Mrs.'),
            'Dr.'  => __('Dr.')
        ))->placeholder();
    }

    public function createSelectGender($name)
    {
        return $this->createSelect($name)->fromArray(array(
            'F'           => __('Female'),
            'M'           => __('Male'),
            'Other'       => __('Other'),
            'Unspecified' => __('Unspecified')
        ))->placeholder();
    }

    public function createSelectRelationship($name)
    {
        return $this->createSelect($name)->fromArray(array(
            'Mother'          => __('Mother'),
            'Father'          => __('Father'),
            'Step-Mother'     => __('Step-Mother'),
            'Step-Father'     => __('Step-Father'),
            'Adoptive Parent' => __('Adoptive Parent'),
            'Guardian'        => __('Guardian'),
            'Grandmother'     => __('Grandmother'),
            'Grandfather'     => __('Grandfather'),
            'Aunt'            => __('Aunt'),
            'Uncle'           => __('Uncle'),
            'Nanny/Helper'    => __('Nanny/Helper'),
            'Other'           => __('Other')
        ))->placeholder();
    }

    public function createSelectEmergencyRelationship($name)
    {
        return $this->createSelect($name)->fromArray(array(
            'Parent'         => __('Parent'),
            'Spouse'         => __('Spouse'),
            'Offspring'      => __('Offspring'),
            'Friend'         => __('Friend'),
            'Other Relation' => __('Other Relation'),
            'Doctor'         => __('Doctor'),
            'Other'          => __('Other')
        ))->placeholder();
    }

    public function createSelectMaritalStatus($name)
    {
        return $this->createSelect($name)->fromArray(array(
            'Married'         => __('Married'),
            'Separated'         => __('Separated'),
            'Divorced'      => __('Divorced'),
            'De Facto'         => __('De Facto'),
            'Other'          => __('Other')
        ))->placeholder();
    }
    public function createSelectBloodType($name)
    {
        return $this->createSelect($name)->fromArray(array(
            'O+' => 'O+',
            'A+' => 'A+',
            'B+' => 'B+',
            'AB+' => 'AB+',
            'O-' => 'O-',
            'A-' => 'A-',
            'B-' => 'B-',
            'AB-' => 'AB-'
        ))->placeholder();
    }

    public function createSelectCurrency($name)
    {
        // I hate doing this ... was there a YAML file at one point?
        $currencies = array(
            'PAYPAL SUPPORTED' => array(
                'AUD $' => 'Australian Dollar (A$)',
                'BRL R$' => 'Brazilian Real (R$)',
                'GBP £' => 'British Pound (£)',
                'CAD $' => 'Canadian Dollar (C$)',
                'CZK Kč' => 'Czech Koruna (Kč)',
                'DKK kr' => 'Danish Krone (kr)',
                'EUR €' => 'Euro (€)',
                'HKD $' => 'Hong Kong Dollar ($)',
                'HUF Ft' => 'Hungarian Forint (Ft)',
                'ILS ₪' => 'Israeli New Shekel (₪)',
                'JPY ¥' => 'Japanese Yen (¥)',
                'MYR RM' => 'Malaysian Ringgit (RM)',
                'MXN $' => 'Mexican Peso ($)',
                'TWD $' => 'New Taiwan Dollar ($)',
                'NZD $' => 'New Zealand Dollar ($)',
                'NOK kr' => 'Norwegian Krone (kr)',
                'PHP ₱' => 'Philippine Peso (₱)',
                'PLN zł' => 'Polish Zloty (zł)',
                'RUB ₽' => 'Russian Ruble (₽)',
                'SGD $' => 'Singapore Dollar ($)',
                'SEK kr‎' => 'Swedish Krona (kr)',
                'CHF' => 'Swiss Franc (CHF)',
                'THB ฿' => 'Thai Baht (฿)',
                'USD $' => 'U.S. Dollar ($)',
                ),
            'OTHERS' => array(
                'BDT ó' => 'Bangladeshi Taka (ó)',
                'BTC' => 'Bitcoin',
                'BGN лв.' => 'Bulgarian Lev (лв.)',
                'XAF FCFA' => 'Central African Francs (FCFA)',
                'CNY ¥' => 'Chinese Renminbi (¥)',
                'EGP £' => 'Egyptian Pound (£)',
                'GHS GH₵' => 'Ghanaian Cedi (GH₵)',
                'INR ₹' => 'Indian Rupee₹ (₹)',
                'IDR Rp' => 'Indonesian Rupiah (Rp)',
                'JMD $' => 'Jamaican Dollar ($)',
                'KES KSh' => 'Kenyan Shilling (KSh)',
                'MOP' => 'Macanese Pataca (MOP)',
                'MMK K' => 'Myanmar Kyat (K)',
                'MAD' => 'Moroccan Dirham (MAD)',
                'NAD N$' => 'Namibian Dollar (N$)',
                'NPR ₨' => 'Nepalese Rupee (₨)',
                'NGN ₦' => 'Nigerian Naira (₦)',
                'PKR ₨' => 'Pakistani Rupee (₨)',
                'SAR ﷼‎' => 'Saudi Riyal (﷼‎)',
                'ZAR R‎' => 'South African Rand (R‎)',
                'TZS TSh' => 'Tanzania Shilling (TSh)',
                'TTD $' => 'Trinidad & Tobago Dollar (TTD)',
                'TRY ₺' => 'Turkish Lira (₺)',
                'VND ₫‎' => 'Vietnamese Dong (₫‎)'
            ),
        );

        return $this->createSelect($name)->fromArray($currencies)->placeholder();
    }
}
