<?php
/*
Gibbon: the flexible, open school platform
Copyright © 2010, Gibbon Foundation
*/

namespace Gibbon\Module\FormalAssessment;

class AssessmentValidator
{
    /**
     * Common parameter validation for assessment actions.
     */
    public static function validateRequiredParams($guid, $connection2, $action, $requiredIDs = []) : bool
    {
        // Basic access check (could be made more generic)
        if (!isActionAccessible($guid, $connection2, '/modules/Formal Assessment/')) {
            return false;
        }

        // Action-specific checks
        if ($action == 'add' && empty($_POST)) {
            return false;
        }

        foreach ($requiredIDs as $id) {
            if (empty($id)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Standard error redirect.
     */
    public static function redirectWithError($URL, $error)
    {
        header("Location: {$URL}&return=$error");
        exit();
    }

    /**
     * Standard success redirect.
     */
    public static function redirectWithSuccess($URL, $success)
    {
        header("Location: {$URL}&return=$success");
        exit();
    }
}