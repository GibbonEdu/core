<?php
namespace Gibbon\Module\ChatBot\Domain\ChatBot;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class ChatBotGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonChatBotCourseMaterials';
    private static $primaryKey = 'gibbonChatBotCourseMaterialsID';
    private static $searchableColumns = ['title', 'description'];
    
    public function selectCourseMaterials($criteria = [])
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonChatBotCourseMaterialsID',
                'title',
                'type',
                'description',
                'filePath',
                'gibbonCourseID',
                'gibbonSchoolYearID',
                'dateAdded',
                'gibbonPersonIDCreator'
            ]);

        return $this->runSelect($query);
    }

    public function selectStudentProgress($criteria = [])
    {
        $query = $this
            ->newSelect()
            ->from('gibbonChatBotStudentProgress')
            ->cols([
                'gibbonChatBotStudentProgressID',
                'gibbonPersonID',
                'gibbonCourseID',
                'gibbonSchoolYearID',
                'progress',
                'lastActivity'
            ]);

        return $this->runSelect($query);
    }
} 