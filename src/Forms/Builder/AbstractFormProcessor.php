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

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\Exception\NotFoundException;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Process\ViewableProcess;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\Exception\MissingFieldException;
use Gibbon\Forms\Builder\Exception\MissingValueException;
use Gibbon\Forms\Builder\Exception\FormProcessException;

abstract class AbstractFormProcessor implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var bool
     */
    private $mode = 'run';

    /**
     * @var FormBuilderInterface
     */
    private $builder;

    /**
     * @var FormDataInterface
     */
    private $data;

    /**
     * @var AbstractFormProcess[]
     */
    private $processes = [];

    /**
     * @var string[]
     */
    private $errors = [];

    abstract protected function submitProcess();

    abstract protected function editProcess();

    abstract protected function acceptProcess();

    public function submitForm(FormBuilderInterface $builder, FormDataInterface $data, bool $dryRun = false)
    {
        $this->boot($builder, $data, $dryRun ? 'verify' : 'run');
        
        $this->submitProcess();

        $this->shutdown();
    }

    public function editForm(FormBuilderInterface $builder, FormDataInterface $data, bool $dryRun = false)
    {
        $this->boot($builder, $data, $dryRun ? 'verify' : 'run');

        $this->editProcess();

        $this->shutdown();
    }

    public function acceptForm(FormBuilderInterface $builder, FormDataInterface $data, bool $dryRun = false)
    {
        $this->boot($builder, $data, $dryRun ? 'verify' : 'run');

        $this->acceptProcess();

        $this->shutdown();
    }

    public function verifyForm(FormBuilderInterface $builder, bool $dryRun = false)
    {
        $this->boot($builder, null, $dryRun ? 'preflight' : 'verify');

        $this->submitProcess();
        $this->editProcess();
        $this->acceptProcess();

        return $this->errors;
    }

    public function getProcesses()
    {
        return $this->processes;
    }

    public function getViewableProcesses()
    {
        return array_filter($this->processes, function ($process) {
            return $process instanceof ViewableProcess;
        });
    }

    public function hasErrors()
    {
        return !empty($this->errors);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getMode()
    {
        return $this->mode;
    }

    protected function boot(FormBuilderInterface $builder, ?FormDataInterface $data, string $mode)
    {
        $this->builder = $builder;
        $this->data = $data;
        $this->mode = $mode;
    }

    protected function run(string $processClass)
    {
        if ($this->mode == 'rollback') return;

        try {
            $process = $this->getProcess($processClass);

            if ($this->mode == 'preflight') {
                $process->verify($this->builder);
                $process->setVerified();
                return;
            }
            
            if ($this->mode != 'boot' && !$process->isEnabled($this->builder)) {
                return;
            }

            if ($this->mode == 'verify') {
                $process->verify($this->builder, $this->data);
                $process->setVerified();
                return;
            }
            
            if ($this->mode == 'run') {
                $process->boot($this->data);
                $process->verify($this->builder, $this->data);
                $process->process($this->builder, $this->data);
                $process->shutdown($this->data);
                $process->setProcessed();
                return;
            }

        } catch (NotFoundException $e) {
            $this->errors[] = __('Invalid process class: {className}', ['className' => $processClass]);
        } catch (MissingFieldException $e) {
            $this->errors[] = __('Missing required field: {fieldName}', ['fieldName' => $e->getMessage()]);
        } catch (MissingValueException $e) {
            $this->errors[] = __('Missing value for required field: {fieldName}', ['fieldName' => $e->getMessage()]);
        } catch (FormProcessException | \Error | \Exception $e) {
            $this->errors[] = __('Fatal error during {className}: {message}', [
                'className' => trim(strrchr($processClass, '\\'), '\\'),
                'message'   => $e->getMessage(),
            ]);
            error_log($e);
            $this->mode = 'rollback';
        }
    }

    protected function shutdown()
    {
        if ($this->mode != 'rollback') return;

        // Run through the processes in reverse order
        $this->processes = array_reverse($this->processes);

        // Rollback each process that had previously run
        foreach ($this->processes as $process) {
            if (!$process->isProcessed()) continue;

            $process->rollback($this->builder, $this->data);
            $process->boot($this->data);

            $this->errors[] = __('Process {className} was rolled back', ['className' => $process->getProcessName()]);
        }
    }

    private function getProcess(string $processClass)
    {
        if (isset($this->processes[$processClass])) {
            return $this->processes[$processClass];
        }

        $this->processes[$processClass] = $this->getContainer()->get($processClass);

        return $this->processes[$processClass];
    }
}
