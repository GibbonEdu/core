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

class Google_Service_Compute_BackendBucket extends Google_Model
{
  public $bucketName;
  protected $cdnPolicyType = 'Google_Service_Compute_BackendBucketCdnPolicy';
  protected $cdnPolicyDataType = '';
  public $creationTimestamp;
  public $description;
  public $enableCdn;
  public $id;
  public $kind;
  public $name;
  public $selfLink;

  public function setBucketName($bucketName)
  {
    $this->bucketName = $bucketName;
  }
  public function getBucketName()
  {
    return $this->bucketName;
  }
  /**
   * @param Google_Service_Compute_BackendBucketCdnPolicy
   */
  public function setCdnPolicy(Google_Service_Compute_BackendBucketCdnPolicy $cdnPolicy)
  {
    $this->cdnPolicy = $cdnPolicy;
  }
  /**
   * @return Google_Service_Compute_BackendBucketCdnPolicy
   */
  public function getCdnPolicy()
  {
    return $this->cdnPolicy;
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
  public function setEnableCdn($enableCdn)
  {
    $this->enableCdn = $enableCdn;
  }
  public function getEnableCdn()
  {
    return $this->enableCdn;
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
}
