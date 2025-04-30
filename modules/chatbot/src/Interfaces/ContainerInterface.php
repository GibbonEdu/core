<?php
namespace Gibbon\Module\ChatBot\Interfaces;

interface ContainerInterface
{
    /**
     * Get a service from the container
     *
     * @param string $id The service identifier
     * @return mixed
     */
    public function get($id);
} 