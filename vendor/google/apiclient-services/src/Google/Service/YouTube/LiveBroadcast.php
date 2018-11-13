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

class Google_Service_YouTube_LiveBroadcast extends Google_Model
{
  protected $contentDetailsType = 'Google_Service_YouTube_LiveBroadcastContentDetails';
  protected $contentDetailsDataType = '';
  public $etag;
  public $id;
  public $kind;
  protected $snippetType = 'Google_Service_YouTube_LiveBroadcastSnippet';
  protected $snippetDataType = '';
  protected $statisticsType = 'Google_Service_YouTube_LiveBroadcastStatistics';
  protected $statisticsDataType = '';
  protected $statusType = 'Google_Service_YouTube_LiveBroadcastStatus';
  protected $statusDataType = '';

  /**
   * @param Google_Service_YouTube_LiveBroadcastContentDetails
   */
  public function setContentDetails(Google_Service_YouTube_LiveBroadcastContentDetails $contentDetails)
  {
    $this->contentDetails = $contentDetails;
  }
  /**
   * @return Google_Service_YouTube_LiveBroadcastContentDetails
   */
  public function getContentDetails()
  {
    return $this->contentDetails;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
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
  /**
   * @param Google_Service_YouTube_LiveBroadcastSnippet
   */
  public function setSnippet(Google_Service_YouTube_LiveBroadcastSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  /**
   * @return Google_Service_YouTube_LiveBroadcastSnippet
   */
  public function getSnippet()
  {
    return $this->snippet;
  }
  /**
   * @param Google_Service_YouTube_LiveBroadcastStatistics
   */
  public function setStatistics(Google_Service_YouTube_LiveBroadcastStatistics $statistics)
  {
    $this->statistics = $statistics;
  }
  /**
   * @return Google_Service_YouTube_LiveBroadcastStatistics
   */
  public function getStatistics()
  {
    return $this->statistics;
  }
  /**
   * @param Google_Service_YouTube_LiveBroadcastStatus
   */
  public function setStatus(Google_Service_YouTube_LiveBroadcastStatus $status)
  {
    $this->status = $status;
  }
  /**
   * @return Google_Service_YouTube_LiveBroadcastStatus
   */
  public function getStatus()
  {
    return $this->status;
  }
}
