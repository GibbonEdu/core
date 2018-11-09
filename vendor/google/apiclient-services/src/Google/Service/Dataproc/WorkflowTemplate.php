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

class Google_Service_Dataproc_WorkflowTemplate extends Google_Collection
{
  protected $collection_key = 'parameters';
  public $createTime;
  public $id;
  protected $jobsType = 'Google_Service_Dataproc_OrderedJob';
  protected $jobsDataType = 'array';
  public $labels;
  public $name;
  protected $parametersType = 'Google_Service_Dataproc_TemplateParameter';
  protected $parametersDataType = 'array';
  protected $placementType = 'Google_Service_Dataproc_WorkflowTemplatePlacement';
  protected $placementDataType = '';
  public $updateTime;
  public $version;

  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  public function getCreateTime()
  {
    return $this->createTime;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  /**
   * @param Google_Service_Dataproc_OrderedJob
   */
  public function setJobs($jobs)
  {
    $this->jobs = $jobs;
  }
  /**
   * @return Google_Service_Dataproc_OrderedJob
   */
  public function getJobs()
  {
    return $this->jobs;
  }
  public function setLabels($labels)
  {
    $this->labels = $labels;
  }
  public function getLabels()
  {
    return $this->labels;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  /**
   * @param Google_Service_Dataproc_TemplateParameter
   */
  public function setParameters($parameters)
  {
    $this->parameters = $parameters;
  }
  /**
   * @return Google_Service_Dataproc_TemplateParameter
   */
  public function getParameters()
  {
    return $this->parameters;
  }
  /**
   * @param Google_Service_Dataproc_WorkflowTemplatePlacement
   */
  public function setPlacement(Google_Service_Dataproc_WorkflowTemplatePlacement $placement)
  {
    $this->placement = $placement;
  }
  /**
   * @return Google_Service_Dataproc_WorkflowTemplatePlacement
   */
  public function getPlacement()
  {
    return $this->placement;
  }
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
  public function setVersion($version)
  {
    $this->version = $version;
  }
  public function getVersion()
  {
    return $this->version;
  }
}
