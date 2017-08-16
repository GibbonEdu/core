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

class Google_Service_Compute_Instance extends Google_Collection
{
  protected $collection_key = 'serviceAccounts';
  public $canIpForward;
  public $cpuPlatform;
  public $creationTimestamp;
  public $description;
  protected $disksType = 'Google_Service_Compute_AttachedDisk';
  protected $disksDataType = 'array';
  protected $guestAcceleratorsType = 'Google_Service_Compute_AcceleratorConfig';
  protected $guestAcceleratorsDataType = 'array';
  public $id;
  public $kind;
  public $labelFingerprint;
  public $labels;
  public $machineType;
  protected $metadataType = 'Google_Service_Compute_Metadata';
  protected $metadataDataType = '';
  public $name;
  protected $networkInterfacesType = 'Google_Service_Compute_NetworkInterface';
  protected $networkInterfacesDataType = 'array';
  protected $schedulingType = 'Google_Service_Compute_Scheduling';
  protected $schedulingDataType = '';
  public $selfLink;
  protected $serviceAccountsType = 'Google_Service_Compute_ServiceAccount';
  protected $serviceAccountsDataType = 'array';
  public $startRestricted;
  public $status;
  public $statusMessage;
  protected $tagsType = 'Google_Service_Compute_Tags';
  protected $tagsDataType = '';
  public $zone;

  public function setCanIpForward($canIpForward)
  {
    $this->canIpForward = $canIpForward;
  }
  public function getCanIpForward()
  {
    return $this->canIpForward;
  }
  public function setCpuPlatform($cpuPlatform)
  {
    $this->cpuPlatform = $cpuPlatform;
  }
  public function getCpuPlatform()
  {
    return $this->cpuPlatform;
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
  /**
   * @param Google_Service_Compute_AttachedDisk
   */
  public function setDisks($disks)
  {
    $this->disks = $disks;
  }
  /**
   * @return Google_Service_Compute_AttachedDisk
   */
  public function getDisks()
  {
    return $this->disks;
  }
  /**
   * @param Google_Service_Compute_AcceleratorConfig
   */
  public function setGuestAccelerators($guestAccelerators)
  {
    $this->guestAccelerators = $guestAccelerators;
  }
  /**
   * @return Google_Service_Compute_AcceleratorConfig
   */
  public function getGuestAccelerators()
  {
    return $this->guestAccelerators;
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
  public function setLabelFingerprint($labelFingerprint)
  {
    $this->labelFingerprint = $labelFingerprint;
  }
  public function getLabelFingerprint()
  {
    return $this->labelFingerprint;
  }
  public function setLabels($labels)
  {
    $this->labels = $labels;
  }
  public function getLabels()
  {
    return $this->labels;
  }
  public function setMachineType($machineType)
  {
    $this->machineType = $machineType;
  }
  public function getMachineType()
  {
    return $this->machineType;
  }
  /**
   * @param Google_Service_Compute_Metadata
   */
  public function setMetadata(Google_Service_Compute_Metadata $metadata)
  {
    $this->metadata = $metadata;
  }
  /**
   * @return Google_Service_Compute_Metadata
   */
  public function getMetadata()
  {
    return $this->metadata;
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
   * @param Google_Service_Compute_NetworkInterface
   */
  public function setNetworkInterfaces($networkInterfaces)
  {
    $this->networkInterfaces = $networkInterfaces;
  }
  /**
   * @return Google_Service_Compute_NetworkInterface
   */
  public function getNetworkInterfaces()
  {
    return $this->networkInterfaces;
  }
  /**
   * @param Google_Service_Compute_Scheduling
   */
  public function setScheduling(Google_Service_Compute_Scheduling $scheduling)
  {
    $this->scheduling = $scheduling;
  }
  /**
   * @return Google_Service_Compute_Scheduling
   */
  public function getScheduling()
  {
    return $this->scheduling;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  /**
   * @param Google_Service_Compute_ServiceAccount
   */
  public function setServiceAccounts($serviceAccounts)
  {
    $this->serviceAccounts = $serviceAccounts;
  }
  /**
   * @return Google_Service_Compute_ServiceAccount
   */
  public function getServiceAccounts()
  {
    return $this->serviceAccounts;
  }
  public function setStartRestricted($startRestricted)
  {
    $this->startRestricted = $startRestricted;
  }
  public function getStartRestricted()
  {
    return $this->startRestricted;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setStatusMessage($statusMessage)
  {
    $this->statusMessage = $statusMessage;
  }
  public function getStatusMessage()
  {
    return $this->statusMessage;
  }
  /**
   * @param Google_Service_Compute_Tags
   */
  public function setTags(Google_Service_Compute_Tags $tags)
  {
    $this->tags = $tags;
  }
  /**
   * @return Google_Service_Compute_Tags
   */
  public function getTags()
  {
    return $this->tags;
  }
  public function setZone($zone)
  {
    $this->zone = $zone;
  }
  public function getZone()
  {
    return $this->zone;
  }
}
