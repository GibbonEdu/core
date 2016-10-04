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

class Google_Service_YouTube_InvideoTiming extends Google_Model
{
  public $durationMs;
  public $offsetMs;
  public $type;

  public function setDurationMs($durationMs)
  {
    $this->durationMs = $durationMs;
  }
  public function getDurationMs()
  {
    return $this->durationMs;
  }
  public function setOffsetMs($offsetMs)
  {
    $this->offsetMs = $offsetMs;
  }
  public function getOffsetMs()
  {
    return $this->offsetMs;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
}
