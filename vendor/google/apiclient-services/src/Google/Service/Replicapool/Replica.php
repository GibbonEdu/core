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

class Google_Service_Replicapool_Replica extends Google_Model
{
  public $name;
  public $selfLink;
  protected $statusType = 'Google_Service_Replicapool_ReplicaStatus';
  protected $statusDataType = '';

  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  /**
   * @param Google_Service_Replicapool_ReplicaStatus
   */
  public function setStatus(Google_Service_Replicapool_ReplicaStatus $status)
  {
    $this->status = $status;
  }
  /**
   * @return Google_Service_Replicapool_ReplicaStatus
   */
  public function getStatus()
  {
    return $this->status;
  }
}
