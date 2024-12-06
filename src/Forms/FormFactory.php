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
use Gibbon\Forms\Layout\Row;
use Gibbon\Forms\Layout\Column;
use Gibbon\Forms\Layout\Meta;
use Gibbon\Forms\Layout\Element;
use Gibbon\Forms\Layout\Trigger;
use Gibbon\Forms\FormFactoryInterface;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\Action;
use Gibbon\Contracts\Services\Session;

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

    /**
     * {@inheritDoc}
     */
    public function createRow($id = ''): Row
    {
        return new Layout\Row($this, $id);
    }

    /**
     * {@inheritDoc}
     */
    public function createColumn($id = ''): Column
    {
        return new Layout\Column($this, $id);
    }

    /**
     * {@inheritDoc}
     */
    public function createMeta(): Meta
    {
        return new Layout\Meta($this);
    }

    public function createTable($id = '')
    {
        return new Layout\Table($this, $id);
    }

    public function createDataTable($id, $criteria = null)
    {
        return !empty($criteria)
            ? DataTable::createPaginated($id, $criteria)
            : DataTable::create($id);
    }

    public function createTableCell($content = '')
    {
        return new Layout\TableCell($content);
    }

    public function createGrid($id = '', $columns = 1)
    {
        return new Layout\Grid($this, $id, $columns);
    }

    public function createDetails($id = '')
    {
        return new Layout\Details($this, $id);
    }

    /**
     * {@inheritDoc}
     */
    public function createTrigger($selector = ''): Trigger
    {
        return new Layout\Trigger($selector);
    }

    public function createLabel($for, $label)
    {
        return new Layout\Label($for, $label);
    }

    public function createHeading($id = '', $content = null, $tag = null)
    {
        $content = is_null($content) || $content == 'h3' || $content == 'h4' ? $id : $content;
        return new Layout\Heading($id, $content, $tag);
    }

    public function createSubheading($id = '', $content = null, $tag = 'h4')
    {
        $content = is_null($content) || $content == 'h3' || $content == 'h4' ? $id : $content;
        return new Layout\Heading($id, $content, $tag = 'h4');
    }

    /**
     * {@inheritDoc}
     */
    public function createContent($content = ''): Element
    {
        return new Layout\Element($content);
    }

    public function createWebLink($content = '')
    {
    	return new Layout\WebLink($content);
    }

    public function createAction($name, $label = '')
    {
    	return new Action($name, $label);
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

    public function createRange($name, $min, $max, $step = null)
    {
        return new Input\Range($name, $min, $max, $step);
    }

    public function createColor($name)
    {
        return (new Input\Color($name));
    }

    public function createFinder($name)
    {
        return new Input\Finder($name);
    }

    public function createEditor(string $name)
    {
        return new Input\Editor($name);
    }

    public function createCodeEditor($name)
    {
        return new Input\CodeEditor($name);
    }

    public function createCommentEditor($name)
    {
        return new Input\CommentEditor($name);
    }

    public function createEmail($name)
    {
        return (new Input\TextField($name))
            ->addValidation('Validate.Email')
            ->maxLength(75);
    }

    //A URL web link
    public function createURL($name)
    {
        return (new Input\TextField($name) )
            ->setType('url')
            ->placeholder('http://')
            ->addValidation('Validate.URL');
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
        return (new Input\Checkbox($name));
    }

    public function createRadio($name)
    {
        return (new Input\Radio($name));
    }

    public function createToggle($name)
    {
        return (new Input\Toggle($name));
    }

    /**
     * {@inheritDoc}
     */
    public function createSelect($name)
    {
        return new Input\Select($name);
    }

    public function createMultiSelect($name)
    {
        return new Input\MultiSelect($this, $name);
    }

    public function createButton($label = 'Button', $onClick = null, $id = null)
    {
        return new Input\Button($label, 'button', $onClick, $id);
    }

    public function createCustomBlocks($name, Session $session, bool $canDelete = true)
    {
        return new Input\CustomBlocks($this, $name, $session, $canDelete);
    }

    public function createDocuments($name, $documents, $view, $absoluteURL, $mode = '')
    {
        return new Input\Documents($this, $name, $documents, $view, $absoluteURL, $mode);
    }

    public function createPersonalDocuments($name, $documents, $view, $settingGateway)
    {
        return new Input\PersonalDocuments($this, $name, $documents, $view, $settingGateway);
    }

    public function createUsername($name)
    {
        return new Input\Username($name);
    }

    public function createSelectPerson($name)
    {
        return new Input\Person($name);
    }

    public function createScanner($name)
    {
        return new Input\Scanner($name);
    }

    /* PRE-DEFINED LAYOUT --------------------------- */

    public function createAlert($content, $level = 'warning')
    {
        return $this->createContent($content)->wrap('<div class="'.$level.'">', '</div>');
    }

    public function createSubmit($label = 'Submit', $id = null)
    {
        return $this->createButton($label, null, $id)->setType('submit')->addClass('text-right');
    }

    public function createSearchSubmit($session, $clearLabel = 'Clear Filters', $passParams = array())
    {
        $passParams[] = 'q';
        $parameters = array_intersect_key($_GET, array_flip($passParams));
        $clearURL = Url::fromRoute()->withQueryParams($parameters);
        $clearLink = sprintf('<a href="%s" class="right px-3 py-2 text-xs font-medium text-gray-600">%s</a> &nbsp;', $clearURL, __($clearLabel));

        return $this->createSubmit(__('Go'))->prepend($clearLink);
    }

    public function createConfirmSubmit($label = 'Yes', $cancel = false)
    {
        $cancelLink = ($cancel)? sprintf('<a href="%s" class="right px-3 py-2 text-xs font-medium text-gray-600">%s</a> &nbsp;', $_SERVER['HTTP_REFERER'], __('Cancel')) : '';
        return $this->createSubmit($label)->prepend($cancelLink);
    }

    public function createAdvancedOptionsToggle()
    {
        return $this->createButton(__('Advanced Options'))
            ->setAttribute('@click', 'advancedOptions = !advancedOptions')
            ->setClass('text-xs bg-transparent');
    }

    public function createFooter($required = true)
    {
        return $this->createContent('');
    }

    /* PRE-DEFINED INPUT --------------------------- */

    public function createYesNo($name)
    {
        return $this->createToggle($name)->setYesNo();
    }

    public function createYesNoRadio($name)
    {
        return $this->createToggle($name)->setYesNo();
    }

    public function createCheckAll($name = 'checkall')
    {
        return $this->createCheckbox($name)->setClass('floatNone checkall')->alignCenter();
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
            'Guardian'       => __('Guardian'),
            'Grandmother'    => __('Grandmother'),
            'Grandfather'    => __('Grandfather'),
            'Friend'         => __('Friend'),
            'Other Relation' => __('Other Relation'),
            'Doctor'         => __('Doctor'),
            'Other'          => __('Other')
        ))->placeholder();
    }

    public function createSelectMaritalStatus($name)
    {
        return $this->createSelect($name)->fromArray(array(
            'Married'   => __('Married'),
            'Separated' => __('Separated'),
            'Divorced'  => __('Divorced'),
            'De Facto'  => __('De Facto'),
            'Single'    => __('Single'),
            'Other'     => __('Other'),
        ))->placeholder();
    }

    public function createSelectSystemLanguage($name)
    {
        $languages = array(
            'af_ZA' => 'Afrikaans - Suid-Afrika',
            'nl_NL' => 'Dutch - Nederland',
            'en_GB' => 'English - United Kingdom',
            'en_US' => 'English - United States',
            'es_ES' => 'Español',
            'fr_FR' => 'Français - France',
            'he_IL' => 'עברית - ישראל',
            'hr_HR' => 'Hrvatski - Hrvatska',
            'it_IT' => 'Italiano - Italia',
            'pl_PL' => 'Język polski - Polska',
            'pt_BR' => 'Português - Brasil',
            'ro_RO' => 'Română',
            'sq_AL' => 'Shqip - Shqipëri',
            'vi_VN' => 'Tiếng Việt - Việt Nam',
            'tr_TR' => 'Türkçe - Türkiye',
            'ar_SA' => 'العربية - المملكة العربية السعودية',
            'th_TH' => 'ภาษาไทย - ประเทศไทย',
            'uk_UA' => 'українська мова - Україна',
            'ur_PK' => 'پاکستان - اُردُو',
            'zh_CN' => '汉语 - 中国',
            'zh_HK' => '繁體字 - 香港',
        );

        return $this->createSelect($name)->fromArray($languages);
    }

    public function createSelectCurrency($name)
    {
        // I hate doing this ... was there a YAML file at one point?
        $currencies = array(
            'PAYPAL SUPPORTED' => array(
                'ARS $' => 'Argentine Peso (ARS$)',
                'AUD $' => 'Australian Dollar (A$)',
                'BRL R$' => 'Brazilian Real (R$)',
                'GBP £' => 'British Pound (£)',
                'CAD $' => 'Canadian Dollar (C$)',
                'CZK Kč' => 'Czech Koruna (Kč)',
                'DKK kr' => 'Danish Krone (kr)',
                'DOP $' => 'Dominican Peso ($)',
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
                'SGD $' => 'Singapore Dollar ($)',
                'SEK kr' => 'Swedish Krona (kr)',
                'CHF' => 'Swiss Franc (CHF)',
                'THB ฿' => 'Thai Baht (฿)',
                'USD $' => 'U.S. Dollar ($)',
                ),
            'OTHERS' => array(
                'ALL L' => 'Albanian Lek (L)',
                'DZD دج' => 'Algerian Dinar (دج)',
                'BDT ó' => 'Bangladeshi Taka (ó)',
                'BTC' => 'Bitcoin',
                'BWP P' => 'Botswana Pula (P)',
                'BGN лв.' => 'Bulgarian Lev (лв.)',
                'XAF FCFA' => 'Central African Francs (FCFA)',
                'CLP $' => 'Chilean Peso ($)',
                'CNY ¥' => 'Chinese Renminbi (¥)',
                'COP $' => 'Colombian Peso ($)',
                'EGP £' => 'Egyptian Pound (£)',
                'FJD $' => 'Fijian Dollar ($)',
                'GHS GH₵' => 'Ghanaian Cedi (GH₵)',
                'GTQ Q' => 'Guatemalan Quetzal (Q)',
                'INR ₹' => 'Indian Rupee₹ (₹)',
                'IDR Rp' => 'Indonesian Rupiah (Rp)',
                'JMD $' => 'Jamaican Dollar ($)',
                'KES KSh' => 'Kenyan Shilling (KSh)',
                'LYD د.ل' => 'Libyan Dinar (د.ل)',
                'MOP' => 'Macanese Pataca (MOP)',
                'MGA' => 'Malagasy Ariary (Ar)',
                'MWK' => 'Malawian Kwacha (MWK)',
                'MVR Rf' => 'Maldivian Rufiyaa (Rf)',
                'MMK K' => 'Myanmar Kyat (K)',
                'MZN MT' => 'Mozambique Metical (MT)',
                'MAD' => 'Moroccan Dirham (MAD)',
                'NAD N$' => 'Namibian Dollar (N$)',
                'NPR ₨' => 'Nepalese Rupee (₨)',
                'NIO C$' => 'Nicaraguan Córdoba (C$)',
                'NGN ₦' => 'Nigerian Naira (₦)',
                'OMR ر.ع.' => 'Omani Rial (ر.ع.)',
                'PKR ₨' => 'Pakistani Rupee (₨)',
                'RON L' => 'Romanian Leu (L)',
                'RUB ₽' => 'Russian Ruble (₽)',
                'SAR ﷼‎' => 'Saudi Riyal (﷼‎)',
                'ZAR R‎' => 'South African Rand (R‎)',
                'LKR Rs' => 'Sri Lankan Rupee (Rs)',
                'TZS TSh' => 'Tanzania Shilling (TSh)',
                'TTD $' => 'Trinidad & Tobago Dollar (TTD)',
                'TRY ₺' => 'Turkish Lira (₺)',
                'UAH ₴' => 'Українська гривня (₴)',
                'UGX USh' => 'Uganda Shilling (USh)',
                'AED د.إ' => 'United Arab Emirates Dirham (د.إ)',
                'VND ₫‎' => 'Vietnamese Dong (₫‎)',
                'XCD $' => 'Eastern Caribbean Dollars ($)',
                'XOF FCFA' => 'West African Francs (FCFA)',
                'ZMW ZK' => 'Zambian Kwacha (ZMW)',
                'ZWL $' => 'Zimbabwean Dollar (ZWL $)',
            ),
        );

        return $this->createSelect($name)->fromArray($currencies)->placeholder();
    }
}
