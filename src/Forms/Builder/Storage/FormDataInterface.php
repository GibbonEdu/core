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

namespace Gibbon\Forms\Builder\Storage;

interface FormDataInterface
{
    public function exists(string $fieldName) : bool;

    public function has(string $fieldName) : bool;

    public function hasAll(array $fieldNames) : bool;

    public function hasAny(array $fieldNames) : bool;

    public function get(string $fieldName, $default = null);

    public function getAny(string $fieldName, $default = null);

    public function getOrNull(string $fieldName);

    public function set(string $fieldName, $value);

    public function hasData(string $fieldName) : bool;

    public function getData() : array;

    public function setData(array $data);

    public function hasResult(string $fieldName) : bool;

    public function getResult(string $fieldName, $default = null);

    public function setResult(string $fieldName, $value);

    public function getResults() : array;

    public function setResults(array $results);

    public function getStatus() : string;

    public function setStatus(string $status);

}
