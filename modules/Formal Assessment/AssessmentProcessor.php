<?php
namespace Gibbon\Module\FormalAssessment;

abstract class AssessmentProcessor
{
    protected $db;
    protected $session;
    protected $validator;

    public function __construct($db, $session, $validator)
    {
        $this->db = $db;
        $this->session = $session;
        $this->validator = $validator;
    }

    // Common validation logic
    public function validateRequiredParams($action, array $requiredIDs): bool
    {
        if (!isActionAccessible($this->session->get('guid'), $this->db, '/modules/Formal Assessment/')) {
            return false;
        }

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

    // Common redirect methods
    public function redirectWithError($URL, $error)
    {
        header("Location: {$URL}&return=$error");
        exit();
    }

    public function redirectWithSuccess($URL, $success)
    {
        header("Location: {$URL}&return=$success");
        exit();
    }

    // Abstract methods for child-specific logic
    abstract protected function handleAdd();
    abstract protected function handleEdit();
    abstract protected function handleDelete();
}