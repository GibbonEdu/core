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

class Google_Service_Fitness_Dataset extends Google_Collection
{
  protected $collection_key = 'point';
  public $dataSourceId;
  public $maxEndTimeNs;
  public $minStartTimeNs;
  public $nextPageToken;
  protected $pointType = 'Google_Service_Fitness_DataPoint';
  protected $pointDataType = 'array';

  public function setDataSourceId($dataSourceId)
  {
    $this->dataSourceId = $dataSourceId;
  }
  public function getDataSourceId()
  {
    return $this->dataSourceId;
  }
  public function setMaxEndTimeNs($maxEndTimeNs)
  {
    $this->maxEndTimeNs = $maxEndTimeNs;
  }
  public function getMaxEndTimeNs()
  {
    return $this->maxEndTimeNs;
  }
  public function setMinStartTimeNs($minStartTimeNs)
  {
    $this->minStartTimeNs = $minStartTimeNs;
  }
  public function getMinStartTimeNs()
  {
    return $this->minStartTimeNs;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setPoint($point)
  {
    $this->point = $point;
  }
  public function getPoint()
  {
    return $this->point;
  }
}
