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

class Google_Service_Compute_BackendService extends Google_Collection
{
  protected $collection_key = 'healthChecks';
  public $affinityCookieTtlSec;
  protected $backendsType = 'Google_Service_Compute_Backend';
  protected $backendsDataType = 'array';
  protected $connectionDrainingType = 'Google_Service_Compute_ConnectionDraining';
  protected $connectionDrainingDataType = '';
  public $creationTimestamp;
  public $description;
  public $enableCDN;
  public $fingerprint;
  public $healthChecks;
  public $id;
  public $kind;
  public $name;
  public $port;
  public $portName;
  public $protocol;
  public $region;
  public $selfLink;
  public $sessionAffinity;
  public $timeoutSec;

  public function setAffinityCookieTtlSec($affinityCookieTtlSec)
  {
    $this->affinityCookieTtlSec = $affinityCookieTtlSec;
  }
  public function getAffinityCookieTtlSec()
  {
    return $this->affinityCookieTtlSec;
  }
  public function setBackends($backends)
  {
    $this->backends = $backends;
  }
  public function getBackends()
  {
    return $this->backends;
  }
  public function setConnectionDraining(Google_Service_Compute_ConnectionDraining $connectionDraining)
  {
    $this->connectionDraining = $connectionDraining;
  }
  public function getConnectionDraining()
  {
    return $this->connectionDraining;
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
  public function setEnableCDN($enableCDN)
  {
    $this->enableCDN = $enableCDN;
  }
  public function getEnableCDN()
  {
    return $this->enableCDN;
  }
  public function setFingerprint($fingerprint)
  {
    $this->fingerprint = $fingerprint;
  }
  public function getFingerprint()
  {
    return $this->fingerprint;
  }
  public function setHealthChecks($healthChecks)
  {
    $this->healthChecks = $healthChecks;
  }
  public function getHealthChecks()
  {
    return $this->healthChecks;
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
  public function setPort($port)
  {
    $this->port = $port;
  }
  public function getPort()
  {
    return $this->port;
  }
  public function setPortName($portName)
  {
    $this->portName = $portName;
  }
  public function getPortName()
  {
    return $this->portName;
  }
  public function setProtocol($protocol)
  {
    $this->protocol = $protocol;
  }
  public function getProtocol()
  {
    return $this->protocol;
  }
  public function setRegion($region)
  {
    $this->region = $region;
  }
  public function getRegion()
  {
    return $this->region;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  public function setSessionAffinity($sessionAffinity)
  {
    $this->sessionAffinity = $sessionAffinity;
  }
  public function getSessionAffinity()
  {
    return $this->sessionAffinity;
  }
  public function setTimeoutSec($timeoutSec)
  {
    $this->timeoutSec = $timeoutSec;
  }
  public function getTimeoutSec()
  {
    return $this->timeoutSec;
  }
}
