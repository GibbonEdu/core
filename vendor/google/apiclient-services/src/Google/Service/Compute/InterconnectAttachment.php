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

class Google_Service_Compute_InterconnectAttachment extends Google_Collection
{
  protected $collection_key = 'candidateSubnets';
  public $adminEnabled;
  public $bandwidth;
  public $candidateSubnets;
  public $cloudRouterIpAddress;
  public $creationTimestamp;
  public $customerRouterIpAddress;
  public $description;
  public $edgeAvailabilityDomain;
  public $googleReferenceId;
  public $id;
  public $interconnect;
  public $kind;
  public $name;
  public $operationalStatus;
  public $pairingKey;
  public $partnerAsn;
  protected $partnerMetadataType = 'Google_Service_Compute_InterconnectAttachmentPartnerMetadata';
  protected $partnerMetadataDataType = '';
  protected $privateInterconnectInfoType = 'Google_Service_Compute_InterconnectAttachmentPrivateInfo';
  protected $privateInterconnectInfoDataType = '';
  public $region;
  public $router;
  public $selfLink;
  public $state;
  public $type;
  public $vlanTag8021q;

  public function setAdminEnabled($adminEnabled)
  {
    $this->adminEnabled = $adminEnabled;
  }
  public function getAdminEnabled()
  {
    return $this->adminEnabled;
  }
  public function setBandwidth($bandwidth)
  {
    $this->bandwidth = $bandwidth;
  }
  public function getBandwidth()
  {
    return $this->bandwidth;
  }
  public function setCandidateSubnets($candidateSubnets)
  {
    $this->candidateSubnets = $candidateSubnets;
  }
  public function getCandidateSubnets()
  {
    return $this->candidateSubnets;
  }
  public function setCloudRouterIpAddress($cloudRouterIpAddress)
  {
    $this->cloudRouterIpAddress = $cloudRouterIpAddress;
  }
  public function getCloudRouterIpAddress()
  {
    return $this->cloudRouterIpAddress;
  }
  public function setCreationTimestamp($creationTimestamp)
  {
    $this->creationTimestamp = $creationTimestamp;
  }
  public function getCreationTimestamp()
  {
    return $this->creationTimestamp;
  }
  public function setCustomerRouterIpAddress($customerRouterIpAddress)
  {
    $this->customerRouterIpAddress = $customerRouterIpAddress;
  }
  public function getCustomerRouterIpAddress()
  {
    return $this->customerRouterIpAddress;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setEdgeAvailabilityDomain($edgeAvailabilityDomain)
  {
    $this->edgeAvailabilityDomain = $edgeAvailabilityDomain;
  }
  public function getEdgeAvailabilityDomain()
  {
    return $this->edgeAvailabilityDomain;
  }
  public function setGoogleReferenceId($googleReferenceId)
  {
    $this->googleReferenceId = $googleReferenceId;
  }
  public function getGoogleReferenceId()
  {
    return $this->googleReferenceId;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setInterconnect($interconnect)
  {
    $this->interconnect = $interconnect;
  }
  public function getInterconnect()
  {
    return $this->interconnect;
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
  public function setOperationalStatus($operationalStatus)
  {
    $this->operationalStatus = $operationalStatus;
  }
  public function getOperationalStatus()
  {
    return $this->operationalStatus;
  }
  public function setPairingKey($pairingKey)
  {
    $this->pairingKey = $pairingKey;
  }
  public function getPairingKey()
  {
    return $this->pairingKey;
  }
  public function setPartnerAsn($partnerAsn)
  {
    $this->partnerAsn = $partnerAsn;
  }
  public function getPartnerAsn()
  {
    return $this->partnerAsn;
  }
  /**
   * @param Google_Service_Compute_InterconnectAttachmentPartnerMetadata
   */
  public function setPartnerMetadata(Google_Service_Compute_InterconnectAttachmentPartnerMetadata $partnerMetadata)
  {
    $this->partnerMetadata = $partnerMetadata;
  }
  /**
   * @return Google_Service_Compute_InterconnectAttachmentPartnerMetadata
   */
  public function getPartnerMetadata()
  {
    return $this->partnerMetadata;
  }
  /**
   * @param Google_Service_Compute_InterconnectAttachmentPrivateInfo
   */
  public function setPrivateInterconnectInfo(Google_Service_Compute_InterconnectAttachmentPrivateInfo $privateInterconnectInfo)
  {
    $this->privateInterconnectInfo = $privateInterconnectInfo;
  }
  /**
   * @return Google_Service_Compute_InterconnectAttachmentPrivateInfo
   */
  public function getPrivateInterconnectInfo()
  {
    return $this->privateInterconnectInfo;
  }
  public function setRegion($region)
  {
    $this->region = $region;
  }
  public function getRegion()
  {
    return $this->region;
  }
  public function setRouter($router)
  {
    $this->router = $router;
  }
  public function getRouter()
  {
    return $this->router;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  public function setState($state)
  {
    $this->state = $state;
  }
  public function getState()
  {
    return $this->state;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVlanTag8021q($vlanTag8021q)
  {
    $this->vlanTag8021q = $vlanTag8021q;
  }
  public function getVlanTag8021q()
  {
    return $this->vlanTag8021q;
  }
}
