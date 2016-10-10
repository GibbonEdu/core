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

class Google_Service_Bigquery_JobConfiguration extends Google_Model
{
  protected $copyType = 'Google_Service_Bigquery_JobConfigurationTableCopy';
  protected $copyDataType = '';
  public $dryRun;
  protected $extractType = 'Google_Service_Bigquery_JobConfigurationExtract';
  protected $extractDataType = '';
  protected $loadType = 'Google_Service_Bigquery_JobConfigurationLoad';
  protected $loadDataType = '';
  protected $queryType = 'Google_Service_Bigquery_JobConfigurationQuery';
  protected $queryDataType = '';

  public function setCopy(Google_Service_Bigquery_JobConfigurationTableCopy $copy)
  {
    $this->copy = $copy;
  }
  public function getCopy()
  {
    return $this->copy;
  }
  public function setDryRun($dryRun)
  {
    $this->dryRun = $dryRun;
  }
  public function getDryRun()
  {
    return $this->dryRun;
  }
  public function setExtract(Google_Service_Bigquery_JobConfigurationExtract $extract)
  {
    $this->extract = $extract;
  }
  public function getExtract()
  {
    return $this->extract;
  }
  public function setLoad(Google_Service_Bigquery_JobConfigurationLoad $load)
  {
    $this->load = $load;
  }
  public function getLoad()
  {
    return $this->load;
  }
  public function setQuery(Google_Service_Bigquery_JobConfigurationQuery $query)
  {
    $this->query = $query;
  }
  public function getQuery()
  {
    return $this->query;
  }
}
