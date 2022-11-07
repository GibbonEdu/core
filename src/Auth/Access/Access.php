<?php

namespace Gibbon\Auth\Access;

class Access
{
    /**
     * Resource this access is concerning.
     *
     * @var Resource
     */
    protected $resource;

    /**
     * Actions allowed for the resource.
     *
     * @var array
     */
    protected $actions = [];

    /**
     * Constructor
     *
     * @param Resource $resource  The resource this access instance is concerning.
     * @param array    $actions   An array of strings.
     */
    public function __construct(Resource $resource, array $actions = [])
    {
        $this->resource = $resource;
        $this->actions = $actions;
    }

    /**
     * Check if an the resource access allow certain action.
     *
     * If the action is not provided, then simply check if any action is allowed
     * for the resource access.
     *
     * @param string|null $action  The action string to check. Null if no specific action.
     *                             Default: null.
     *
     * @return bool
     */
    public function allow(?string $action = null): bool
    {
        if ($action === null) {
            return !empty($this->actions);
        }
        return in_array($action, $this->actions);
    }

    /**
     * Check if any of the given actions are allowed.
     *
     * @param string ...$actions
     *
     * @return bool
     */
    public function allowAny(string ...$actions): bool
    {
        if (empty($actions)) {
            throw new \InvalidArgumentException('Must at least provide 1 action');
        }
        foreach ($actions as $action) {
            if (in_array($action, $this->actions)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if all of the given actions are allowed.
     *
     * @param string ...$actions
     *
     * @return bool
     */
    public function allowAll(string ...$actions): bool
    {
        if (empty($actions)) {
            throw new \InvalidArgumentException('Must at least provide 1 action');
        }
        foreach ($actions as $action) {
            if (!in_array($action, $this->actions)) {
                return false;
            }
        }
        return true;
    }
}