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

class Google_Service_Books_ConcurrentAccessRestriction extends Google_Model
{
  public $deviceAllowed;
  public $kind;
  public $maxConcurrentDevices;
  public $message;
  public $nonce;
  public $reasonCode;
  public $restricted;
  public $signature;
  public $source;
  public $timeWindowSeconds;
  public $volumeId;

  public function setDeviceAllowed($deviceAllowed)
  {
    $this->deviceAllowed = $deviceAllowed;
  }
  public function getDeviceAllowed()
  {
    return $this->deviceAllowed;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMaxConcurrentDevices($maxConcurrentDevices)
  {
    $this->maxConcurrentDevices = $maxConcurrentDevices;
  }
  public function getMaxConcurrentDevices()
  {
    return $this->maxConcurrentDevices;
  }
  public function setMessage($message)
  {
    $this->message = $message;
  }
  public function getMessage()
  {
    return $this->message;
  }
  public function setNonce($nonce)
  {
    $this->nonce = $nonce;
  }
  public function getNonce()
  {
    return $this->nonce;
  }
  public function setReasonCode($reasonCode)
  {
    $this->reasonCode = $reasonCode;
  }
  public function getReasonCode()
  {
    return $this->reasonCode;
  }
  public function setRestricted($restricted)
  {
    $this->restricted = $restricted;
  }
  public function getRestricted()
  {
    return $this->restricted;
  }
  public function setSignature($signature)
  {
    $this->signature = $signature;
  }
  public function getSignature()
  {
    return $this->signature;
  }
  public function setSource($source)
  {
    $this->source = $source;
  }
  public function getSource()
  {
    return $this->source;
  }
  public function setTimeWindowSeconds($timeWindowSeconds)
  {
    $this->timeWindowSeconds = $timeWindowSeconds;
  }
  public function getTimeWindowSeconds()
  {
    return $this->timeWindowSeconds;
  }
  public function setVolumeId($volumeId)
  {
    $this->volumeId = $volumeId;
  }
  public function getVolumeId()
  {
    return $this->volumeId;
  }
}
