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

namespace Gibbon\Module\Reports\Renderer;

use Gibbon\Module\Reports\ReportTemplate;

interface ReportRendererInterface
{
    const OUTPUT_TWO_SIDED = 0b0001;
    const OUTPUT_CONTINUOUS = 0b0010;
    
    public function setMode(int $bitmask);
    public function hasMode(int $bitmask);

    public function addPreProcess(string $name, callable $callable);
    public function addPostProcess(string $name, callable $callable);

    public function render(ReportTemplate $template, array $input, string $output = '');
}
