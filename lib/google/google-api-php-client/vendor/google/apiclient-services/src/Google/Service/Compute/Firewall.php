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

class Google_Service_Compute_Firewall extends Google_Collection
{
  protected $collection_key = 'targetTags';
  protected $allowedType = 'Google_Service_Compute_FirewallAllowed';
  protected $allowedDataType = 'array';
  public $creationTimestamp;
  public $description;
  public $id;
  public $kind;
  public $name;
  public $network;
  public $selfLink;
  public $sourceRanges;
  public $sourceTags;
  public $targetTags;

  /**
   * @param Google_Service_Compute_FirewallAllowed
   */
  public function setAllowed($allowed)
  {
    $this->allowed = $allowed;
  }
  /**
   * @return Google_Service_Compute_FirewallAllowed
   */
  public function getAllowed()
  {
    return $this->allowed;
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
  public function setNetwork($network)
  {
    $this->network = $network;
  }
  public function getNetwork()
  {
    return $this->network;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  public function setSourceRanges($sourceRanges)
  {
    $this->sourceRanges = $sourceRanges;
  }
  public function getSourceRanges()
  {
    return $this->sourceRanges;
  }
  public function setSourceTags($sourceTags)
  {
    $this->sourceTags = $sourceTags;
  }
  public function getSourceTags()
  {
    return $this->sourceTags;
  }
  public function setTargetTags($targetTags)
  {
    $this->targetTags = $targetTags;
  }
  public function getTargetTags()
  {
    return $this->targetTags;
  }
}
