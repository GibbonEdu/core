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

class Google_Service_Compute_HealthCheck extends Google_Model
{
  public $checkIntervalSec;
  public $creationTimestamp;
  public $description;
  public $healthyThreshold;
  protected $http2HealthCheckType = 'Google_Service_Compute_HTTP2HealthCheck';
  protected $http2HealthCheckDataType = '';
  protected $httpHealthCheckType = 'Google_Service_Compute_HTTPHealthCheck';
  protected $httpHealthCheckDataType = '';
  protected $httpsHealthCheckType = 'Google_Service_Compute_HTTPSHealthCheck';
  protected $httpsHealthCheckDataType = '';
  public $id;
  public $kind;
  public $name;
  public $selfLink;
  protected $sslHealthCheckType = 'Google_Service_Compute_SSLHealthCheck';
  protected $sslHealthCheckDataType = '';
  protected $tcpHealthCheckType = 'Google_Service_Compute_TCPHealthCheck';
  protected $tcpHealthCheckDataType = '';
  public $timeoutSec;
  public $type;
  public $unhealthyThreshold;

  public function setCheckIntervalSec($checkIntervalSec)
  {
    $this->checkIntervalSec = $checkIntervalSec;
  }
  public function getCheckIntervalSec()
  {
    return $this->checkIntervalSec;
  }
  public function setCreationTimestamp($creationTimestamp)
  {
    $this->creationTimestamp = $creationTimestamp;
  }
  public function getCreationTimestamp()
  {
    return $this->creationTimestamp;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setHealthyThreshold($healthyThreshold)
  {
    $this->healthyThreshold = $healthyThreshold;
  }
  public function getHealthyThreshold()
  {
    return $this->healthyThreshold;
  }
  public function setHttp2HealthCheck(Google_Service_Compute_HTTP2HealthCheck $http2HealthCheck)
  {
    $this->http2HealthCheck = $http2HealthCheck;
  }
  public function getHttp2HealthCheck()
  {
    return $this->http2HealthCheck;
  }
  public function setHttpHealthCheck(Google_Service_Compute_HTTPHealthCheck $httpHealthCheck)
  {
    $this->httpHealthCheck = $httpHealthCheck;
  }
  public function getHttpHealthCheck()
  {
    return $this->httpHealthCheck;
  }
  public function setHttpsHealthCheck(Google_Service_Compute_HTTPSHealthCheck $httpsHealthCheck)
  {
    $this->httpsHealthCheck = $httpsHealthCheck;
  }
  public function getHttpsHealthCheck()
  {
    return $this->httpsHealthCheck;
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
  public function setSslHealthCheck(Google_Service_Compute_SSLHealthCheck $sslHealthCheck)
  {
    $this->sslHealthCheck = $sslHealthCheck;
  }
  public function getSslHealthCheck()
  {
    return $this->sslHealthCheck;
  }
  public function setTcpHealthCheck(Google_Service_Compute_TCPHealthCheck $tcpHealthCheck)
  {
    $this->tcpHealthCheck = $tcpHealthCheck;
  }
  public function getTcpHealthCheck()
  {
    return $this->tcpHealthCheck;
  }
  public function setTimeoutSec($timeoutSec)
  {
    $this->timeoutSec = $timeoutSec;
  }
  public function getTimeoutSec()
  {
    return $this->timeoutSec;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setUnhealthyThreshold($unhealthyThreshold)
  {
    $this->unhealthyThreshold = $unhealthyThreshold;
  }
  public function getUnhealthyThreshold()
  {
    return $this->unhealthyThreshold;
  }
}
