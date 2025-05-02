<?php

declare(strict_types=1);

namespace CHHS\Modules\ChatBot\Services;

use CHHS\Modules\ChatBot\Domain\Traits\TableAwareTrait;
use CHHS\Modules\ChatBot\Domain\Traits\TableAwareInterface;

class AssessmentService implements TableAwareInterface
{
    use TableAwareTrait;

    /**
     * Fetch student assessment data from internal and external tables.
     *
     * @param int $studentID
     * @param int $courseClassID
     * @return array
     */
    public function fetchStudentData(int $studentID, int $courseClassID): array
    {
        $internalData = $this->getTable('gibbonInternalAssessmentEntry')
            ->select(['*'])
            ->where('gibbonPersonIDStudent = ?', $studentID)
            ->where('gibbonCourseClassID = ?', $courseClassID)
            ->fetchAll();

        $externalData = $this->getTable('gibbonExternalAssessmentEntry')
            ->select(['*'])
            ->where('gibbonPersonIDStudent = ?', $studentID)
            ->where('gibbonCourseClassID = ?', $courseClassID)
            ->fetchAll();

        return [
            'internal' => $internalData,
            'external' => $externalData,
        ];
    }

    /**
     * Generate intervention plans for at-risk students.
     *
     * @param array $studentData
     * @return array
     */
    public function generateIntervention(array $studentData): array
    {
        $interventions = [];
        $internalAvg = $this->calculateAverage($studentData['internal'] ?? []);
        $externalAvg = $this->calculateAverage($studentData['external'] ?? []);

        if ($internalAvg < 50 || $externalAvg < 50) {
            $interventions[] = 'Low performance detected. Recommend tutoring sessions.';
        }

        if ($this->hasSignificantDrop($studentData['internal'] ?? [])) {
            $interventions[] = 'Significant drop in performance detected. Schedule parent meeting.';
        }

        return [
            'averages' => [
                'internal' => $internalAvg,
                'external' => $externalAvg,
            ],
            'interventions' => $interventions,
        ];
    }

    /**
     * Calculate average score from assessment data.
     *
     * @param array $assessments
     * @return float
     */
    private function calculateAverage(array $assessments): float
    {
        if (empty($assessments)) {
            return 0.0;
        }

        $total = 0;
        foreach ($assessments as $assessment) {
            $total += $assessment['score'] ?? 0;
        }

        return $total / count($assessments);
    }

    /**
     * Check for significant drop in performance.
     *
     * @param array $assessments
     * @return bool
     */
    private function hasSignificantDrop(array $assessments): bool
    {
        if (count($assessments) < 2) {
            return false;
        }

        $scores = array_column($assessments, 'score');
        $latest = end($scores);
        $previous = prev($scores);

        return ($previous - $latest) >= ($previous * 0.10);
    }
}
