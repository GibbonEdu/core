<?php
/**
 * Gibbon, Flexible & Open School System
 * Copyright (C) 2010, Ross Parker
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Gibbon\Contracts\Comms;

/**
 * An interface for PHPMailer, used by the Mailer wrapper class.
 */
interface Mailer
{
    public function isHTML($isHtml = true);
    public function isSMTP();
    public function isMail();
    public function isSendmail();
    public function isQmail();

    public function setFrom($address, $name = '', $auto = true);

    public function addAddress($address, $name = '');
    public function addCC($address, $name = '');
    public function addBCC($address, $name = '');
    public function addReplyTo($address, $name = '');
    public function addAttachment($path, $name = '', $encoding = 'base64', $type = '', $disposition = 'attachment');

    public function clearAddresses();
    public function clearCCs();
    public function clearBCCs();
    public function clearReplyTos();
    public function clearAllRecipients();
    public function clearAttachments();

    public function getToAddresses();
    public function getCcAddresses();
    public function getBccAddresses();
    public function getReplyToAddresses();
    public function getAllRecipientAddresses();
    public function getAttachments();

    public function send();
    public function smtpConnect($options = null);
    public function smtpClose();
}
