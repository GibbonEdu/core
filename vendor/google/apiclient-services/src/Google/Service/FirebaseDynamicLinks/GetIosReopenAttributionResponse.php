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

class Google_Service_FirebaseDynamicLinks_GetIosReopenAttributionResponse extends Google_Model
{
  public $deepLink;
  public $invitationId;
  public $resolvedLink;
  public $utmCampaign;
  public $utmMedium;
  public $utmSource;

  public function setDeepLink($deepLink)
  {
    $this->deepLink = $deepLink;
  }
  public function getDeepLink()
  {
    return $this->deepLink;
  }
  public function setInvitationId($invitationId)
  {
    $this->invitationId = $invitationId;
  }
  public function getInvitationId()
  {
    return $this->invitationId;
  }
  public function setResolvedLink($resolvedLink)
  {
    $this->resolvedLink = $resolvedLink;
  }
  public function getResolvedLink()
  {
    return $this->resolvedLink;
  }
  public function setUtmCampaign($utmCampaign)
  {
    $this->utmCampaign = $utmCampaign;
  }
  public function getUtmCampaign()
  {
    return $this->utmCampaign;
  }
  public function setUtmMedium($utmMedium)
  {
    $this->utmMedium = $utmMedium;
  }
  public function getUtmMedium()
  {
    return $this->utmMedium;
  }
  public function setUtmSource($utmSource)
  {
    $this->utmSource = $utmSource;
  }
  public function getUtmSource()
  {
    return $this->utmSource;
  }
}
