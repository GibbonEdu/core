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

namespace Gibbon\Session;

use Gibbon\Contracts\Services\Session as SessionInterface;

/**
 * tokenHandler Class
 *
 * @version v29
 * @since   v29
 */

class TokenHandler {

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;

        // Create a CSRF token
        if (!$session->exists('csrftoken')) {
            $session->set('csrftoken', bin2hex(random_bytes(16)));
        }

        // Create a nonce list 
        if (!$session->exists('nonceList')) {
            $session->set('nonceList', []);
        }
    }

    public function getCSRF()
    {
        return $this->session->get('csrftoken');
    }

    public function validateCsrfToken()
    {
        return !empty($this->session->get('csrftoken')) && $this->session->get('csrftoken') === $_POST['csrftoken'];
    }


    public function validateNonce()
    {
        $nonce = $_POST['nonce'] ?? '';

        if(empty($nonce))  {
            return false;
        }

        return $this->removeNonce($nonce);
    }

    /**
     * Create and add a nonce to the session's nonce list, then return it.
     */
    public function getNonce()
    {
        $nonce = bin2hex(random_bytes(16));

        $nonceList = $this->session->get('nonceList', []);
        $nonceList[] = $nonce;
        $this->session->set('nonceList', $nonceList);

        return $nonce;
    }

    /**
     * Check if a nonce exists when a form is submitted. If yes, then remove it from the list.
     * Prevent removing a nonce when using HX-Request to submit a form (via HX-QuickSave in POST).
     *
     * @param string $nonce
     * @return bool
     */
    public function removeNonce(string $nonce) {
        if (!empty($_POST['HX-QuickSave'])) {
            return true;
        }

        $nonceList = $this->session->get('nonceList', []);

        if (($key = array_search($nonce, $nonceList)) !== false) {
            unset($nonceList[$key]);
            $this->session->set('nonceList', array_values($nonceList));
            return true;
        }
        return false;
    }

}
