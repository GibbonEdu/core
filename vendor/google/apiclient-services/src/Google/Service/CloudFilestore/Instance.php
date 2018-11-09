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

class Google_Service_CloudFilestore_Instance extends Google_Collection
{
  protected $collection_key = 'networks';
  public $createTime;
  public $description;
  public $etag;
  protected $fileSharesType = 'Google_Service_CloudFilestore_FileShareConfig';
  protected $fileSharesDataType = 'array';
  public $labels;
  public $name;
  protected $networksType = 'Google_Service_CloudFilestore_NetworkConfig';
  protected $networksDataType = 'array';
  public $state;
  public $statusMessage;
  public $tier;

  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  public function getCreateTime()
  {
    return $this->createTime;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  /**
   * @param Google_Service_CloudFilestore_FileShareConfig
   */
  public function setFileShares($fileShares)
  {
    $this->fileShares = $fileShares;
  }
  /**
   * @return Google_Service_CloudFilestore_FileShareConfig
   */
  public function getFileShares()
  {
    return $this->fileShares;
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
   * @param Google_Service_CloudFilestore_NetworkConfig
   */
  public function setNetworks($networks)
  {
    $this->networks = $networks;
  }
  /**
   * @return Google_Service_CloudFilestore_NetworkConfig
   */
  public function getNetworks()
  {
    return $this->networks;
  }
  public function setState($state)
  {
    $this->state = $state;
  }
  public function getState()
  {
    return $this->state;
  }
  public function setStatusMessage($statusMessage)
  {
    $this->statusMessage = $statusMessage;
  }
  public function getStatusMessage()
  {
    return $this->statusMessage;
  }
  public function setTier($tier)
  {
    $this->tier = $tier;
  }
  public function getTier()
  {
    return $this->tier;
  }
}
