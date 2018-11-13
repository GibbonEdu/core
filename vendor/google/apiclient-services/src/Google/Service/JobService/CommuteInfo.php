<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

class Google_Service_JobService_CommuteInfo extends Google_Model
{
  protected $jobLocationType = 'Google_Service_JobService_JobLocation';
  protected $jobLocationDataType = '';
  public $travelDuration;

  /**
   * @param Google_Service_JobService_JobLocation
   */
  public function setJobLocation(Google_Service_JobService_JobLocation $jobLocation)
  {
    $this->jobLocation = $jobLocation;
  }
  /**
   * @return Google_Service_JobService_JobLocation
   */
  public function getJobLocation()
  {
    return $this->jobLocation;
  }
  public function setTravelDuration($travelDuration)
  {
    $this->travelDuration = $travelDuration;
  }
  public function getTravelDuration()
  {
    return $this->travelDuration;
  }
}
