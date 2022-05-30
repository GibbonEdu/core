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

namespace Gibbon\Forms\Builder;

use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\Exception\MissingFieldException;
use Gibbon\Forms\Builder\Exception\MissingValueException;

abstract class AbstractFormProcess 
{
    protected $verified = false;
    protected $processed = false;
    protected $result = null;

    abstract public function process(FormBuilderInterface $builder, FormDataInterface $data);

    abstract public function rollback(FormBuilderInterface $builder, FormDataInterface $data);

    abstract public function isEnabled(FormBuilderInterface $builder);

    public function isVerified()
    {
        return $this->verified;
    }

    public function setVerified($verified = true)
    {
        $this->verified = $verified;
    }

    public function isProcessed()
    {
        return $this->processed;
    }

    public function setProcessed($processed = true)
    {
        $this->processed = $processed;
    }

    public function setResult($result)
    {
        $this->result = $result;
    }

    public function getProcessName()
    {
        return str_replace(__NAMESPACE__ . '\\Process\\', '', get_called_class());
    }

    public function getRequiredFields() : array
    {
        return $this->requiredFields ?? [];
    }

    public function boot(FormDataInterface $formData)
    {
        $formData->setResult($this->getProcessName().'Result', false);
    }

    public function shutdown(FormDataInterface $formData)
    {
        $formData->setResult($this->getProcessName().'Result', $this->result ?? true);
    }

    public function verify(FormBuilderInterface $builder, FormDataInterface $formData = null)
    {
        foreach ($this->getRequiredFields() as $fieldName) {
            if (!$builder->hasField($fieldName)) {
                throw new MissingFieldException($fieldName);
            }

            if (!empty($formData) && !$formData->has($fieldName)) {
                throw new MissingValueException($fieldName);
            }
        }
    }
}
