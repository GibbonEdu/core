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

class Google_Service_Groupssettings_Groups extends Google_Model
{
  public $allowExternalMembers;
  public $allowGoogleCommunication;
  public $allowWebPosting;
  public $archiveOnly;
  public $customFooterText;
  public $customReplyTo;
  public $defaultMessageDenyNotificationText;
  public $description;
  public $email;
  public $favoriteRepliesOnTop;
  public $includeCustomFooter;
  public $includeInGlobalAddressList;
  public $isArchived;
  public $kind;
  public $maxMessageBytes;
  public $membersCanPostAsTheGroup;
  public $messageDisplayFont;
  public $messageModerationLevel;
  public $name;
  public $primaryLanguage;
  public $replyTo;
  public $sendMessageDenyNotification;
  public $showInGroupDirectory;
  public $spamModerationLevel;
  public $whoCanAdd;
  public $whoCanAddReferences;
  public $whoCanAssignTopics;
  public $whoCanContactOwner;
  public $whoCanEnterFreeFormTags;
  public $whoCanInvite;
  public $whoCanJoin;
  public $whoCanLeaveGroup;
  public $whoCanMarkDuplicate;
  public $whoCanMarkFavoriteReplyOnAnyTopic;
  public $whoCanMarkFavoriteReplyOnOwnTopic;
  public $whoCanMarkNoResponseNeeded;
  public $whoCanModifyTagsAndCategories;
  public $whoCanPostMessage;
  public $whoCanTakeTopics;
  public $whoCanUnassignTopic;
  public $whoCanUnmarkFavoriteReplyOnAnyTopic;
  public $whoCanViewGroup;
  public $whoCanViewMembership;

  public function setAllowExternalMembers($allowExternalMembers)
  {
    $this->allowExternalMembers = $allowExternalMembers;
  }
  public function getAllowExternalMembers()
  {
    return $this->allowExternalMembers;
  }
  public function setAllowGoogleCommunication($allowGoogleCommunication)
  {
    $this->allowGoogleCommunication = $allowGoogleCommunication;
  }
  public function getAllowGoogleCommunication()
  {
    return $this->allowGoogleCommunication;
  }
  public function setAllowWebPosting($allowWebPosting)
  {
    $this->allowWebPosting = $allowWebPosting;
  }
  public function getAllowWebPosting()
  {
    return $this->allowWebPosting;
  }
  public function setArchiveOnly($archiveOnly)
  {
    $this->archiveOnly = $archiveOnly;
  }
  public function getArchiveOnly()
  {
    return $this->archiveOnly;
  }
  public function setCustomFooterText($customFooterText)
  {
    $this->customFooterText = $customFooterText;
  }
  public function getCustomFooterText()
  {
    return $this->customFooterText;
  }
  public function setCustomReplyTo($customReplyTo)
  {
    $this->customReplyTo = $customReplyTo;
  }
  public function getCustomReplyTo()
  {
    return $this->customReplyTo;
  }
  public function setDefaultMessageDenyNotificationText($defaultMessageDenyNotificationText)
  {
    $this->defaultMessageDenyNotificationText = $defaultMessageDenyNotificationText;
  }
  public function getDefaultMessageDenyNotificationText()
  {
    return $this->defaultMessageDenyNotificationText;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
  }
  public function setFavoriteRepliesOnTop($favoriteRepliesOnTop)
  {
    $this->favoriteRepliesOnTop = $favoriteRepliesOnTop;
  }
  public function getFavoriteRepliesOnTop()
  {
    return $this->favoriteRepliesOnTop;
  }
  public function setIncludeCustomFooter($includeCustomFooter)
  {
    $this->includeCustomFooter = $includeCustomFooter;
  }
  public function getIncludeCustomFooter()
  {
    return $this->includeCustomFooter;
  }
  public function setIncludeInGlobalAddressList($includeInGlobalAddressList)
  {
    $this->includeInGlobalAddressList = $includeInGlobalAddressList;
  }
  public function getIncludeInGlobalAddressList()
  {
    return $this->includeInGlobalAddressList;
  }
  public function setIsArchived($isArchived)
  {
    $this->isArchived = $isArchived;
  }
  public function getIsArchived()
  {
    return $this->isArchived;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMaxMessageBytes($maxMessageBytes)
  {
    $this->maxMessageBytes = $maxMessageBytes;
  }
  public function getMaxMessageBytes()
  {
    return $this->maxMessageBytes;
  }
  public function setMembersCanPostAsTheGroup($membersCanPostAsTheGroup)
  {
    $this->membersCanPostAsTheGroup = $membersCanPostAsTheGroup;
  }
  public function getMembersCanPostAsTheGroup()
  {
    return $this->membersCanPostAsTheGroup;
  }
  public function setMessageDisplayFont($messageDisplayFont)
  {
    $this->messageDisplayFont = $messageDisplayFont;
  }
  public function getMessageDisplayFont()
  {
    return $this->messageDisplayFont;
  }
  public function setMessageModerationLevel($messageModerationLevel)
  {
    $this->messageModerationLevel = $messageModerationLevel;
  }
  public function getMessageModerationLevel()
  {
    return $this->messageModerationLevel;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setPrimaryLanguage($primaryLanguage)
  {
    $this->primaryLanguage = $primaryLanguage;
  }
  public function getPrimaryLanguage()
  {
    return $this->primaryLanguage;
  }
  public function setReplyTo($replyTo)
  {
    $this->replyTo = $replyTo;
  }
  public function getReplyTo()
  {
    return $this->replyTo;
  }
  public function setSendMessageDenyNotification($sendMessageDenyNotification)
  {
    $this->sendMessageDenyNotification = $sendMessageDenyNotification;
  }
  public function getSendMessageDenyNotification()
  {
    return $this->sendMessageDenyNotification;
  }
  public function setShowInGroupDirectory($showInGroupDirectory)
  {
    $this->showInGroupDirectory = $showInGroupDirectory;
  }
  public function getShowInGroupDirectory()
  {
    return $this->showInGroupDirectory;
  }
  public function setSpamModerationLevel($spamModerationLevel)
  {
    $this->spamModerationLevel = $spamModerationLevel;
  }
  public function getSpamModerationLevel()
  {
    return $this->spamModerationLevel;
  }
  public function setWhoCanAdd($whoCanAdd)
  {
    $this->whoCanAdd = $whoCanAdd;
  }
  public function getWhoCanAdd()
  {
    return $this->whoCanAdd;
  }
  public function setWhoCanAddReferences($whoCanAddReferences)
  {
    $this->whoCanAddReferences = $whoCanAddReferences;
  }
  public function getWhoCanAddReferences()
  {
    return $this->whoCanAddReferences;
  }
  public function setWhoCanAssignTopics($whoCanAssignTopics)
  {
    $this->whoCanAssignTopics = $whoCanAssignTopics;
  }
  public function getWhoCanAssignTopics()
  {
    return $this->whoCanAssignTopics;
  }
  public function setWhoCanContactOwner($whoCanContactOwner)
  {
    $this->whoCanContactOwner = $whoCanContactOwner;
  }
  public function getWhoCanContactOwner()
  {
    return $this->whoCanContactOwner;
  }
  public function setWhoCanEnterFreeFormTags($whoCanEnterFreeFormTags)
  {
    $this->whoCanEnterFreeFormTags = $whoCanEnterFreeFormTags;
  }
  public function getWhoCanEnterFreeFormTags()
  {
    return $this->whoCanEnterFreeFormTags;
  }
  public function setWhoCanInvite($whoCanInvite)
  {
    $this->whoCanInvite = $whoCanInvite;
  }
  public function getWhoCanInvite()
  {
    return $this->whoCanInvite;
  }
  public function setWhoCanJoin($whoCanJoin)
  {
    $this->whoCanJoin = $whoCanJoin;
  }
  public function getWhoCanJoin()
  {
    return $this->whoCanJoin;
  }
  public function setWhoCanLeaveGroup($whoCanLeaveGroup)
  {
    $this->whoCanLeaveGroup = $whoCanLeaveGroup;
  }
  public function getWhoCanLeaveGroup()
  {
    return $this->whoCanLeaveGroup;
  }
  public function setWhoCanMarkDuplicate($whoCanMarkDuplicate)
  {
    $this->whoCanMarkDuplicate = $whoCanMarkDuplicate;
  }
  public function getWhoCanMarkDuplicate()
  {
    return $this->whoCanMarkDuplicate;
  }
  public function setWhoCanMarkFavoriteReplyOnAnyTopic($whoCanMarkFavoriteReplyOnAnyTopic)
  {
    $this->whoCanMarkFavoriteReplyOnAnyTopic = $whoCanMarkFavoriteReplyOnAnyTopic;
  }
  public function getWhoCanMarkFavoriteReplyOnAnyTopic()
  {
    return $this->whoCanMarkFavoriteReplyOnAnyTopic;
  }
  public function setWhoCanMarkFavoriteReplyOnOwnTopic($whoCanMarkFavoriteReplyOnOwnTopic)
  {
    $this->whoCanMarkFavoriteReplyOnOwnTopic = $whoCanMarkFavoriteReplyOnOwnTopic;
  }
  public function getWhoCanMarkFavoriteReplyOnOwnTopic()
  {
    return $this->whoCanMarkFavoriteReplyOnOwnTopic;
  }
  public function setWhoCanMarkNoResponseNeeded($whoCanMarkNoResponseNeeded)
  {
    $this->whoCanMarkNoResponseNeeded = $whoCanMarkNoResponseNeeded;
  }
  public function getWhoCanMarkNoResponseNeeded()
  {
    return $this->whoCanMarkNoResponseNeeded;
  }
  public function setWhoCanModifyTagsAndCategories($whoCanModifyTagsAndCategories)
  {
    $this->whoCanModifyTagsAndCategories = $whoCanModifyTagsAndCategories;
  }
  public function getWhoCanModifyTagsAndCategories()
  {
    return $this->whoCanModifyTagsAndCategories;
  }
  public function setWhoCanPostMessage($whoCanPostMessage)
  {
    $this->whoCanPostMessage = $whoCanPostMessage;
  }
  public function getWhoCanPostMessage()
  {
    return $this->whoCanPostMessage;
  }
  public function setWhoCanTakeTopics($whoCanTakeTopics)
  {
    $this->whoCanTakeTopics = $whoCanTakeTopics;
  }
  public function getWhoCanTakeTopics()
  {
    return $this->whoCanTakeTopics;
  }
  public function setWhoCanUnassignTopic($whoCanUnassignTopic)
  {
    $this->whoCanUnassignTopic = $whoCanUnassignTopic;
  }
  public function getWhoCanUnassignTopic()
  {
    return $this->whoCanUnassignTopic;
  }
  public function setWhoCanUnmarkFavoriteReplyOnAnyTopic($whoCanUnmarkFavoriteReplyOnAnyTopic)
  {
    $this->whoCanUnmarkFavoriteReplyOnAnyTopic = $whoCanUnmarkFavoriteReplyOnAnyTopic;
  }
  public function getWhoCanUnmarkFavoriteReplyOnAnyTopic()
  {
    return $this->whoCanUnmarkFavoriteReplyOnAnyTopic;
  }
  public function setWhoCanViewGroup($whoCanViewGroup)
  {
    $this->whoCanViewGroup = $whoCanViewGroup;
  }
  public function getWhoCanViewGroup()
  {
    return $this->whoCanViewGroup;
  }
  public function setWhoCanViewMembership($whoCanViewMembership)
  {
    $this->whoCanViewMembership = $whoCanViewMembership;
  }
  public function getWhoCanViewMembership()
  {
    return $this->whoCanViewMembership;
  }
}
