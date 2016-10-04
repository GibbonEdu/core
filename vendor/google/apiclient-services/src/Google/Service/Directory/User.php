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

class Google_Service_Directory_User extends Google_Collection
{
  protected $collection_key = 'nonEditableAliases';
  public $addresses;
  public $agreedToTerms;
  public $aliases;
  public $changePasswordAtNextLogin;
  public $creationTime;
  public $customSchemas;
  public $customerId;
  public $deletionTime;
  public $emails;
  public $etag;
  public $externalIds;
  public $hashFunction;
  public $id;
  public $ims;
  public $includeInGlobalAddressList;
  public $ipWhitelisted;
  public $isAdmin;
  public $isDelegatedAdmin;
  public $isMailboxSetup;
  public $kind;
  public $lastLoginTime;
  protected $nameType = 'Google_Service_Directory_UserName';
  protected $nameDataType = '';
  public $nonEditableAliases;
  public $notes;
  public $orgUnitPath;
  public $organizations;
  public $password;
  public $phones;
  public $primaryEmail;
  public $relations;
  public $suspended;
  public $suspensionReason;
  public $thumbnailPhotoEtag;
  public $thumbnailPhotoUrl;
  public $websites;

  public function setAddresses($addresses)
  {
    $this->addresses = $addresses;
  }
  public function getAddresses()
  {
    return $this->addresses;
  }
  public function setAgreedToTerms($agreedToTerms)
  {
    $this->agreedToTerms = $agreedToTerms;
  }
  public function getAgreedToTerms()
  {
    return $this->agreedToTerms;
  }
  public function setAliases($aliases)
  {
    $this->aliases = $aliases;
  }
  public function getAliases()
  {
    return $this->aliases;
  }
  public function setChangePasswordAtNextLogin($changePasswordAtNextLogin)
  {
    $this->changePasswordAtNextLogin = $changePasswordAtNextLogin;
  }
  public function getChangePasswordAtNextLogin()
  {
    return $this->changePasswordAtNextLogin;
  }
  public function setCreationTime($creationTime)
  {
    $this->creationTime = $creationTime;
  }
  public function getCreationTime()
  {
    return $this->creationTime;
  }
  public function setCustomSchemas($customSchemas)
  {
    $this->customSchemas = $customSchemas;
  }
  public function getCustomSchemas()
  {
    return $this->customSchemas;
  }
  public function setCustomerId($customerId)
  {
    $this->customerId = $customerId;
  }
  public function getCustomerId()
  {
    return $this->customerId;
  }
  public function setDeletionTime($deletionTime)
  {
    $this->deletionTime = $deletionTime;
  }
  public function getDeletionTime()
  {
    return $this->deletionTime;
  }
  public function setEmails($emails)
  {
    $this->emails = $emails;
  }
  public function getEmails()
  {
    return $this->emails;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setExternalIds($externalIds)
  {
    $this->externalIds = $externalIds;
  }
  public function getExternalIds()
  {
    return $this->externalIds;
  }
  public function setHashFunction($hashFunction)
  {
    $this->hashFunction = $hashFunction;
  }
  public function getHashFunction()
  {
    return $this->hashFunction;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIms($ims)
  {
    $this->ims = $ims;
  }
  public function getIms()
  {
    return $this->ims;
  }
  public function setIncludeInGlobalAddressList($includeInGlobalAddressList)
  {
    $this->includeInGlobalAddressList = $includeInGlobalAddressList;
  }
  public function getIncludeInGlobalAddressList()
  {
    return $this->includeInGlobalAddressList;
  }
  public function setIpWhitelisted($ipWhitelisted)
  {
    $this->ipWhitelisted = $ipWhitelisted;
  }
  public function getIpWhitelisted()
  {
    return $this->ipWhitelisted;
  }
  public function setIsAdmin($isAdmin)
  {
    $this->isAdmin = $isAdmin;
  }
  public function getIsAdmin()
  {
    return $this->isAdmin;
  }
  public function setIsDelegatedAdmin($isDelegatedAdmin)
  {
    $this->isDelegatedAdmin = $isDelegatedAdmin;
  }
  public function getIsDelegatedAdmin()
  {
    return $this->isDelegatedAdmin;
  }
  public function setIsMailboxSetup($isMailboxSetup)
  {
    $this->isMailboxSetup = $isMailboxSetup;
  }
  public function getIsMailboxSetup()
  {
    return $this->isMailboxSetup;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLastLoginTime($lastLoginTime)
  {
    $this->lastLoginTime = $lastLoginTime;
  }
  public function getLastLoginTime()
  {
    return $this->lastLoginTime;
  }
  public function setName(Google_Service_Directory_UserName $name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNonEditableAliases($nonEditableAliases)
  {
    $this->nonEditableAliases = $nonEditableAliases;
  }
  public function getNonEditableAliases()
  {
    return $this->nonEditableAliases;
  }
  public function setNotes($notes)
  {
    $this->notes = $notes;
  }
  public function getNotes()
  {
    return $this->notes;
  }
  public function setOrgUnitPath($orgUnitPath)
  {
    $this->orgUnitPath = $orgUnitPath;
  }
  public function getOrgUnitPath()
  {
    return $this->orgUnitPath;
  }
  public function setOrganizations($organizations)
  {
    $this->organizations = $organizations;
  }
  public function getOrganizations()
  {
    return $this->organizations;
  }
  public function setPassword($password)
  {
    $this->password = $password;
  }
  public function getPassword()
  {
    return $this->password;
  }
  public function setPhones($phones)
  {
    $this->phones = $phones;
  }
  public function getPhones()
  {
    return $this->phones;
  }
  public function setPrimaryEmail($primaryEmail)
  {
    $this->primaryEmail = $primaryEmail;
  }
  public function getPrimaryEmail()
  {
    return $this->primaryEmail;
  }
  public function setRelations($relations)
  {
    $this->relations = $relations;
  }
  public function getRelations()
  {
    return $this->relations;
  }
  public function setSuspended($suspended)
  {
    $this->suspended = $suspended;
  }
  public function getSuspended()
  {
    return $this->suspended;
  }
  public function setSuspensionReason($suspensionReason)
  {
    $this->suspensionReason = $suspensionReason;
  }
  public function getSuspensionReason()
  {
    return $this->suspensionReason;
  }
  public function setThumbnailPhotoEtag($thumbnailPhotoEtag)
  {
    $this->thumbnailPhotoEtag = $thumbnailPhotoEtag;
  }
  public function getThumbnailPhotoEtag()
  {
    return $this->thumbnailPhotoEtag;
  }
  public function setThumbnailPhotoUrl($thumbnailPhotoUrl)
  {
    $this->thumbnailPhotoUrl = $thumbnailPhotoUrl;
  }
  public function getThumbnailPhotoUrl()
  {
    return $this->thumbnailPhotoUrl;
  }
  public function setWebsites($websites)
  {
    $this->websites = $websites;
  }
  public function getWebsites()
  {
    return $this->websites;
  }
}
