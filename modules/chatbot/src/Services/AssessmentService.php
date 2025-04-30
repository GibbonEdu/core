<?php
namespace Gibbon\Module\ChatBot\Services;

use Gibbon\Domain\System\SettingGateway;

class AssessmentService
{
    private $container;
    private $deepSeekService;

    public function __construct($container)
    {
        $this->container = $container;
        $this->deepSeekService = new DeepSeekService($container);
    }

    public function analyzeStudentPerformance($studentID)
    {
        $db = $this->container->get('db');
        
        // Get student assessment data
        $sql = "SELECT 
                ia.name as assessment_name,
                iac.attainmentValue,
                iac.effortValue,
                iac.comment as teacher_feedback,
                c.name as course_name
                FROM gibbonInternalAssessmentEntry iac
                JOIN gibbonInternalAssessmentColumn ia ON (ia.gibbonInternalAssessmentColumnID=iac.gibbonInternalAssessmentColumnID)
                JOIN gibbonCourse c ON (c.gibbonCourseID=ia.gibbonCourseID)
                WHERE iac.gibbonPersonID=:studentID
                ORDER BY ia.timestampCreated DESC";
        
        $result = $db->prepare($sql);
        $result->execute(['studentID' => $studentID]);
        $assessmentData = $result->fetchAll(\PDO::FETCH_ASSOC);

        // Get student details
        $sql = "SELECT 
                gibbonPerson.firstName,
                gibbonPerson.surname,
                gibbonYearGroup.name as yearGroup
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                WHERE gibbonPerson.gibbonPersonID=:studentID
                AND gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current')
                LIMIT 1";
        
        $result = $db->prepare($sql);
        $result->execute(['studentID' => $studentID]);
        $studentData = $result->fetch(\PDO::FETCH_ASSOC);

        // Combine data for analysis
        $analysisData = [
            'student' => $studentData,
            'assessments' => $assessmentData
        ];

        // Get AI analysis
        return $this->deepSeekService->analyzeGrades($analysisData);
    }

    public function getImprovementSuggestions($studentID)
    {
        $analysis = $this->analyzeStudentPerformance($studentID);
        
        if (!isset($analysis['choices'][0]['message']['content'])) {
            return null;
        }

        return $analysis['choices'][0]['message']['content'];
    }
} 