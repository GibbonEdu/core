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

    public function getRequiredFieldLogic() : string
    {
        return strtoupper($this->requiredFieldLogic ?? 'ALL');
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
        $fields = $this->getRequiredFields();
        $logic = $this->getRequiredFieldLogic();

        $missingFields = $missingValues = [];

        foreach ($fields as $fieldName) {
            if (!$builder->hasField($fieldName)) {
                $missingFields[] = $fieldName;
            }

            if (!empty($formData) && !$formData->has($fieldName)) {
                $missingValues[] = $fieldName;
            }
        }

        if (($logic == 'ALL' && !empty($missingFields)) || ($logic == 'ANY' && count($missingFields) >= count($fields))) {
            throw new MissingFieldException(implode(',', $missingFields));
        }

        if (($logic == 'ALL' && !empty($missingValues)) || ($logic == 'ANY' && count($missingValues) >= count($fields))) {
            throw new MissingValueException(implode(',', $missingValues));
        }
    }
}
