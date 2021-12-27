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

use Gibbon\Domain\Forms\FormFieldGateway;
use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\Storage\FormStorageInterface;
use Gibbon\Forms\Builder\Exception\MissingFieldException;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\Exception\NotFoundException;

abstract class AbstractFormProcessor implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var FormStorageInterface
     */
    protected $storage;

    /**
     * @var FormFieldGateway
     */
    protected $fieldGateway;

    /**
     * @var AbstractFormProcess
     */
    protected $processes = [];

    protected $fields = [];
    protected $data = [];
    protected $errors = [];

    public function __construct(FormStorageInterface $storage, FormFieldGateway $fieldGateway)
    {
        $this->storage = $storage;
        $this->fieldGateway = $fieldGateway;
    }

    public function submitProcess()
    {
        $this->boot();
    }

    public function editProcess()
    {
        $this->boot();
    }

    public function acceptProcess()
    {
        $this->boot();
    }

    public function setForm(string $gibbonFormID, string $identifier)
    {
        $this->gibbonFormID = $gibbonFormID;
        $this->identifier = $identifier;
    }

    public function boot()
    {
        $this->fields = $this->fieldGateway->selectFieldsByForm($this->gibbonFormID)->fetchGroupedUnique();
        $this->data = $this->loadData();
    }

    public function run(string $processClass)
    {
        try {
            $process = $this->getContainer()->get($processClass);
            $this->data += $process->process($this->fields, $this->data);

        } catch (NotFoundException $e) {
            $this->errors[] = __('Invalid process class: {className}', ['className' => $processClass]);
        } catch (MissingFieldException $e) {
            $this->errors[] = __('Missing required field: {fieldName}', ['fieldName' => $e->getMessage()]);
        }
    }

    public function validate()
    {
        $this->boot();

        foreach ($this->processes as $processClass) {
            try {
                $process = $this->getContainer()->get($processClass);
                $process->validate($this->fields);

            } catch (NotFoundException $e) {
                $this->errors[] = __('Invalid process class: {className}', ['className' => $processClass]);
            } catch (MissingFieldException $e) {
                $this->errors[] = __('Missing required field: {fieldName}', ['fieldName' => $e->getMessage()]);
            }
        }

        return $this->errors;
    }

    public function saveData(array $data)
    {
        $this->storage->saveData($this->identifier, $data);
    }

    public function loadData()
    {
        return $this->storage->loadData($this->identifier);
    }
}
