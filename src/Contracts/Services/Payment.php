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

namespace Gibbon\Contracts\Services;

/**
 * Payment Interface
 *
 * @version	v23
 * @since	v23
 */
interface Payment
{
    const RETURN_SUCCESS = 'success1';
    const RETURN_SUCCESS_WARNING = 'warning2';
    const RETURN_CANCEL = 'warning3';
    const RETURN_INCOMPLETE = 'warning4';
    const RETURN_ERROR_NOT_ENABLED = 'error1';
    const RETURN_ERROR_CURRENCY = 'error3';
    const RETURN_ERROR_CONFIG = 'error4';
    const RETURN_ERROR_AMOUNT = 'error5';
    const RETURN_ERROR_GENERAL = 'error6';
    const RETURN_ERROR_CONNECT = 'error7';
    
    public function isEnabled();

    public function setReturnURL($url);

    public function setCancelURL($url);

    public function setForeignTable($foreignTable, $foreignTableID);
    
    public function incomingPayment() : bool;

    public function requestPayment($amount, $name = '') : string;

    public function confirmPayment() : string;

    public function getPaymentResult() : array;

    public function getReturnMessages() : array;
}
