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

class Google_Service_Dns_Change extends Google_Collection
{
  protected $collection_key = 'deletions';
  protected $additionsType = 'Google_Service_Dns_ResourceRecordSet';
  protected $additionsDataType = 'array';
  protected $deletionsType = 'Google_Service_Dns_ResourceRecordSet';
  protected $deletionsDataType = 'array';
  public $id;
  public $kind;
  public $startTime;
  public $status;

  public function setAdditions($additions)
  {
    $this->additions = $additions;
  }
  public function getAdditions()
  {
    return $this->additions;
  }
  public function setDeletions($deletions)
  {
    $this->deletions = $deletions;
  }
  public function getDeletions()
  {
    return $this->deletions;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
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
}
