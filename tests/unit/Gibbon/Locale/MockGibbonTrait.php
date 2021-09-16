<?php

namespace Gibbon;

trait MockGibbonTrait {

    /**
     * Mock global gibbon object.
     *
     * @param object $mockGibbon Object to use for mocking global gibbon object.
     * @return function Function to restore the original global gibbon object.
     */
    private function mockGlobalGibbon(object $mockGibbon)
    {
        global $gibbon;
        $gibbonToRestore = $gibbon ?? false;
        $gibbon = $mockGibbon; // replace global gibbon with the mock object.
        return function () use ($gibbonToRestore) {
            global $gibbon;
            // Unset global $gibbon if there is nothing to restore to.
            $gibbon = ($gibbonToRestore !== false) ? $gibbonToRestore : null;
        };
    }

}
