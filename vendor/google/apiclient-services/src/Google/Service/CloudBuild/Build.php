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

class Google_Service_CloudBuild_Build extends Google_Collection
{
  protected $collection_key = 'steps';
  public $createTime;
  public $finishTime;
  public $id;
  public $images;
  public $logUrl;
  public $logsBucket;
  protected $optionsType = 'Google_Service_CloudBuild_BuildOptions';
  protected $optionsDataType = '';
  public $projectId;
  protected $resultsType = 'Google_Service_CloudBuild_Results';
  protected $resultsDataType = '';
  protected $sourceType = 'Google_Service_CloudBuild_Source';
  protected $sourceDataType = '';
  protected $sourceProvenanceType = 'Google_Service_CloudBuild_SourceProvenance';
  protected $sourceProvenanceDataType = '';
  public $startTime;
  public $status;
  public $statusDetail;
  protected $stepsType = 'Google_Service_CloudBuild_BuildStep';
  protected $stepsDataType = 'array';
  public $timeout;

  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  public function getCreateTime()
  {
    return $this->createTime;
  }
  public function setFinishTime($finishTime)
  {
    $this->finishTime = $finishTime;
  }
  public function getFinishTime()
  {
    return $this->finishTime;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setImages($images)
  {
    $this->images = $images;
  }
  public function getImages()
  {
    return $this->images;
  }
  public function setLogUrl($logUrl)
  {
    $this->logUrl = $logUrl;
  }
  public function getLogUrl()
  {
    return $this->logUrl;
  }
  public function setLogsBucket($logsBucket)
  {
    $this->logsBucket = $logsBucket;
  }
  public function getLogsBucket()
  {
    return $this->logsBucket;
  }
  public function setOptions(Google_Service_CloudBuild_BuildOptions $options)
  {
    $this->options = $options;
  }
  public function getOptions()
  {
    return $this->options;
  }
  public function setProjectId($projectId)
  {
    $this->projectId = $projectId;
  }
  public function getProjectId()
  {
    return $this->projectId;
  }
  public function setResults(Google_Service_CloudBuild_Results $results)
  {
    $this->results = $results;
  }
  public function getResults()
  {
    return $this->results;
  }
  public function setSource(Google_Service_CloudBuild_Source $source)
  {
    $this->source = $source;
  }
  public function getSource()
  {
    return $this->source;
  }
  public function setSourceProvenance(Google_Service_CloudBuild_SourceProvenance $sourceProvenance)
  {
    $this->sourceProvenance = $sourceProvenance;
  }
  public function getSourceProvenance()
  {
    return $this->sourceProvenance;
  }
  public function setStartTime($startTime)
  {
    $this->startTime = $startTime;
  }
  public function getStartTime()
  {
    return $this->startTime;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setStatusDetail($statusDetail)
  {
    $this->statusDetail = $statusDetail;
  }
  public function getStatusDetail()
  {
    return $this->statusDetail;
  }
  public function setSteps($steps)
  {
    $this->steps = $steps;
  }
  public function getSteps()
  {
    return $this->steps;
  }
  public function setTimeout($timeout)
  {
    $this->timeout = $timeout;
  }
  public function getTimeout()
  {
    return $this->timeout;
  }
}
