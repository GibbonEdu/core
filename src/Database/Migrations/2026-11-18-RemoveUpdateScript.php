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

use Gibbon\Contracts\Services\Session;
use Gibbon\Database\Migrations\Migration;

/**
 * Remove Update Script - removes the outdated Softaculous update script from the system.
 */
class RemoveUpdateScript extends Migration
{
    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function migrate()
    {
        $path = $this->session->get('absolutePath') . '/update.php';

        if (file_exists($path)) {
            return unlink($path);
        }

        return true;
    }
}
