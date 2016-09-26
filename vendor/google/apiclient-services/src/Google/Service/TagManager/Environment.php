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

class Google_Service_TagManager_Environment extends Google_Model
{
  public $accountId;
  public $authorizationCode;
  public $authorizationTimestampMs;
  public $containerId;
  public $containerVersionId;
  public $description;
  public $enableDebug;
  public $environmentId;
  public $fingerprint;
  public $name;
  public $type;
  public $url;

  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAuthorizationCode($authorizationCode)
  {
    $this->authorizationCode = $authorizationCode;
  }
  public function getAuthorizationCode()
  {
    return $this->authorizationCode;
  }
  public function setAuthorizationTimestampMs($authorizationTimestampMs)
  {
    $this->authorizationTimestampMs = $authorizationTimestampMs;
  }
  public function getAuthorizationTimestampMs()
  {
    return $this->authorizationTimestampMs;
  }
  public function setContainerId($containerId)
  {
    $this->containerId = $containerId;
  }
  public function getContainerId()
  {
    return $this->containerId;
  }
  public function setContainerVersionId($containerVersionId)
  {
    $this->containerVersionId = $containerVersionId;
  }
  public function getContainerVersionId()
  {
    return $this->containerVersionId;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setEnableDebug($enableDebug)
  {
    $this->enableDebug = $enableDebug;
  }
  public function getEnableDebug()
  {
    return $this->enableDebug;
  }
  public function setEnvironmentId($environmentId)
  {
    $this->environmentId = $environmentId;
  }
  public function getEnvironmentId()
  {
    return $this->environmentId;
  }
  public function setFingerprint($fingerprint)
  {
    $this->fingerprint = $fingerprint;
  }
  public function getFingerprint()
  {
    return $this->fingerprint;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setUrl($url)
  {
    $this->url = $url;
  }
  public function getUrl()
  {
    return $this->url;
  }
}
