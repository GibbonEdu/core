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

namespace Gibbon\Module\Reports\Contexts;

use Gibbon\Module\Reports\Contexts\NullContext;
use Gibbon\Module\Reports\Contexts\StudentEnrolmentContext;
use Gibbon\Module\Reports\Contexts\ReportingCycleContext;
use Gibbon\Module\Reports\Contexts\CustomQueryContext;

class ContextFactory
{
    public static function create(string $context)
    {
        switch ($context) {
            case 'Student Enrolment':
                return new StudentEnrolmentContext();

            case 'Reporting Cycle':
                return new ReportingCycleContext();

            case 'Custom Query':
                return new CustomQueryContext();
        }

        return new NullContext();
    }
}
