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

class Google_Service_Drive_TeamDriveRestrictions extends Google_Model
{
  public $adminManagedRestrictions;
  public $copyRequiresWriterPermission;
  public $domainUsersOnly;
  public $teamMembersOnly;

  public function setAdminManagedRestrictions($adminManagedRestrictions)
  {
    $this->adminManagedRestrictions = $adminManagedRestrictions;
  }
  public function getAdminManagedRestrictions()
  {
    return $this->adminManagedRestrictions;
  }
  public function setCopyRequiresWriterPermission($copyRequiresWriterPermission)
  {
    $this->copyRequiresWriterPermission = $copyRequiresWriterPermission;
  }
  public function getCopyRequiresWriterPermission()
  {
    return $this->copyRequiresWriterPermission;
  }
  public function setDomainUsersOnly($domainUsersOnly)
  {
    $this->domainUsersOnly = $domainUsersOnly;
  }
  public function getDomainUsersOnly()
  {
    return $this->domainUsersOnly;
  }
  public function setTeamMembersOnly($teamMembersOnly)
  {
    $this->teamMembersOnly = $teamMembersOnly;
  }
  public function getTeamMembersOnly()
  {
    return $this->teamMembersOnly;
  }
}
