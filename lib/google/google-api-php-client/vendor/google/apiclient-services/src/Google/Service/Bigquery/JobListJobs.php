<?php
/*
 * Copyright 2016 Google Inc.
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

class Google_Service_Bigquery_JobListJobs extends Google_Model
{
  protected $internal_gapi_mappings = array(
        "userEmail" => "user_email",
  );
  protected $configurationType = 'Google_Service_Bigquery_JobConfiguration';
  protected $configurationDataType = '';
  protected $errorResultType = 'Google_Service_Bigquery_ErrorProto';
  protected $errorResultDataType = '';
  public $id;
  protected $jobReferenceType = 'Google_Service_Bigquery_JobReference';
  protected $jobReferenceDataType = '';
  public $kind;
  public $state;
  protected $statisticsType = 'Google_Service_Bigquery_JobStatistics';
  protected $statisticsDataType = '';
  protected $statusType = 'Google_Service_Bigquery_JobStatus';
  protected $statusDataType = '';
  public $userEmail;

  public function setConfiguration(Google_Service_Bigquery_JobConfiguration $configuration)
  {
    $this->configuration = $configuration;
  }
  public function getConfiguration()
  {
    return $this->configuration;
  }
  public function setErrorResult(Google_Service_Bigquery_ErrorProto $errorResult)
  {
    $this->errorResult = $errorResult;
  }
  public function getErrorResult()
  {
    return $this->errorResult;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setJobReference(Google_Service_Bigquery_JobReference $jobReference)
  {
    $this->jobReference = $jobReference;
  }
  public function getJobReference()
  {
    return $this->jobReference;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setState($state)
  {
    $this->state = $state;
  }
  public function getState()
  {
    return $this->state;
  }
  public function setStatistics(Google_Service_Bigquery_JobStatistics $statistics)
  {
    $this->statistics = $statistics;
  }
  public function getStatistics()
  {
    return $this->statistics;
  }
  public function setStatus(Google_Service_Bigquery_JobStatus $status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setUserEmail($userEmail)
  {
    $this->userEmail = $userEmail;
  }
  public function getUserEmail()
  {
    return $this->userEmail;
  }
}
