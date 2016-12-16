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

class Google_Service_Pubsub_ModifyAckDeadlineRequest extends Google_Collection
{
  protected $collection_key = 'ackIds';
  public $ackDeadlineSeconds;
  public $ackIds;

  public function setAckDeadlineSeconds($ackDeadlineSeconds)
  {
    $this->ackDeadlineSeconds = $ackDeadlineSeconds;
  }
  public function getAckDeadlineSeconds()
  {
    return $this->ackDeadlineSeconds;
  }
  public function setAckIds($ackIds)
  {
    $this->ackIds = $ackIds;
  }
  public function getAckIds()
  {
    return $this->ackIds;
  }
}
