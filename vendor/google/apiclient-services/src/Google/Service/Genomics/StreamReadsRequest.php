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

class Google_Service_Genomics_StreamReadsRequest extends Google_Model
{
  public $end;
  public $projectId;
  public $readGroupSetId;
  public $referenceName;
  public $shard;
  public $start;
  public $totalShards;

  public function setEnd($end)
  {
    $this->end = $end;
  }
  public function getEnd()
  {
    return $this->end;
  }
  public function setProjectId($projectId)
  {
    $this->projectId = $projectId;
  }
  public function getProjectId()
  {
    return $this->projectId;
  }
  public function setReadGroupSetId($readGroupSetId)
  {
    $this->readGroupSetId = $readGroupSetId;
  }
  public function getReadGroupSetId()
  {
    return $this->readGroupSetId;
  }
  public function setReferenceName($referenceName)
  {
    $this->referenceName = $referenceName;
  }
  public function getReferenceName()
  {
    return $this->referenceName;
  }
  public function setShard($shard)
  {
    $this->shard = $shard;
  }
  public function getShard()
  {
    return $this->shard;
  }
  public function setStart($start)
  {
    $this->start = $start;
  }
  public function getStart()
  {
    return $this->start;
  }
  public function setTotalShards($totalShards)
  {
    $this->totalShards = $totalShards;
  }
  public function getTotalShards()
  {
    return $this->totalShards;
  }
}
