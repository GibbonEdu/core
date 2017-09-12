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

class Google_Service_Logging_ListSinksResponse extends Google_Collection
{
  protected $collection_key = 'sinks';
  public $nextPageToken;
  protected $sinksType = 'Google_Service_Logging_LogSink';
  protected $sinksDataType = 'array';

  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  /**
   * @param Google_Service_Logging_LogSink
   */
  public function setSinks($sinks)
  {
    $this->sinks = $sinks;
  }
  /**
   * @return Google_Service_Logging_LogSink
   */
  public function getSinks()
  {
    return $this->sinks;
  }
}
