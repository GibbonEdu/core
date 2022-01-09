<?php

namespace Gibbon;

trait MockGuidTrait {

    /**
     * Mock global gibbon object.
     *
     * @param object $mockGibbon Object to use for mocking global gibbon object.
     * @return function Function to restore the original global gibbon object.
     */
    private function mockGlobalGuid(?string $mockGuid = null)
    {
        global $guid;
        $mockGuid = $mockGuid ?: hash('sha256', time());
        $guidToRestore = $guid ?? false;
        $guid = $mockGuid; // replace global gibbon with the mock object.

        return [
            $mockGuid,
            function () use ($guidToRestore) {
                global $guid;
                // Unset global $guid if there was nothing to restore to.
                $guid = ($guidToRestore !== false) ? $guidToRestore : null;
            }
        ];
    }

}
