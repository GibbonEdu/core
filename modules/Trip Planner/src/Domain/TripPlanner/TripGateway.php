<?php

namespace Gibbon\Domain\TripPlanner;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class TripGateway extends QueryableGateway {
  use TableAware;

  private static $tableName = 'tripPlannerRequests';
  private static $primaryKey = 'gibbonTripPlannerID';
  private static $searchableColumns = [];

  public function queryTrips(QueryCriteria $criteria)
  {
    $query = $this
      ->newQuery()
      ->from('tripPlannerRequests')
      ->innerJoin('gibbonPerson','gibbonPerson.gibbonPersonID = tripPlannerRequests.creatorPersonID')
      ->cols([
        'tripPlannerRequests.tripPlannerRequestID',
        'tripPlannerRequests.creatorPersonID',
        'tripPlannerRequests.timestampCreation',
        'tripPlannerRequests.title as tripTitle',
        'tripPlannerRequests.description',
        'tripPlannerRequests.location',
        'tripPlannerRequests.date',
        'tripPlannerRequests.startTime',
        'tripPlannerRequests.endTime',
        'tripPlannerRequests.status',
        'tripPlannerRequests.endDate',
        'gibbonPerson.title',
        'gibbonPerson.preferredName',
        'gibbonPerson.surname',
        '(SELECT startDate FROM tripPlannerRequestDays WHERE tripPlannerRequestID = tripPlannerRequests.tripPlannerRequestID ORDER BY startDate ASC LIMIT 1) as firstDayOfTrip'
      ]);

    $criteria->addFilterRules([
      'status' => function($query,$status) {
        if($status != 'All')
        {
          return $query
            ->where('tripPlannerRequests.status = :status')
            ->bindValue('status',$status);
        }
        else return $query;
      },
      'eutfilter' => function($query,$eutfilter) use ($criteria) {
        //expiredUnapprovedFilter, set in the module settings
        $query
          ->where('tripPlannerRequestDays.startDate IS NULL')
          ->where("tripPlannerRequests.status != 'Approved'");
      },

      'schoolYearID' => function($query,$gibbonYearGroupID) {
        return $query
          ->where('tripPlannerRequests.gibbonYearGroupID = :gibbonYearGroupID')
          ->bindValue('gibbonYearGroupID',$gibbonYearGroupID);
      },
      'relation' => function($query,$relation) {
        $relarr = explode(':',$relation);
        switch($relation[0])
        {
          case 'MR':
            //My requests option
            //Only show requests owned by
            return $query
              ->where('tripPlannerRequests.creatorPersonID = :personID')
              ->bindValue('personID',$relation[1]);

          case 'I':
            //Involved option
            return $query
              ->innerJoin('tripPlannerRequestPerson','tripPlannerRequestPerson.tripPlannerRequestID = tripPlannerRequests.tripPlannerRequestID')
              ->where("tripPlannerRequestPerson.role = 'Teacher'")
              ->where("(tripPlannerRequestPerson.gibbonPersonID = :personID OR triipPlannerRequests.teacherPersonIDs LIKE CONCAT('%',:personID,'%'))")
              ->bindValues($relation[1]);

          case 'AMA':
            //Awaiting my approval
            return $query
              ->where('EXISTS (SELECT tripPlannerApprovers.gibbonPersonID FROM tripPlannerApprovers WHERE tripPlannerApprovers.gibbonPersonID = :personID)')
              ->bindValue('personID',$relation[1]);

          default:
            if(substr($relation[0],0,2) == "DR")
            {
              //Department Requests
              return $query
                ->innerJoin('gibbonDepartmentStaff','gibbonDepartmentStaff.gibbonPersonID = tripPlannerRequests.creatorPersonID')
                ->where('gibbonDepartmentStaff.gibbonDepartmentID = :departmentID')
                ->bindValue('departmentID',substr($relation[0],2));
            }
            //Don't filter requests, do nothing
            return $query;
        }
      }
    ]);
    return $this->runQuery($query,$criteria);
  }

}

?>
