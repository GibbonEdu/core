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

class Google_Service_Appengine_SslSettings extends Google_Model
{
  public $certificateId;
  public $pendingManagedCertificateId;
  public $sslManagementType;

  public function setCertificateId($certificateId)
  {
    $this->certificateId = $certificateId;
  }
  public function getCertificateId()
  {
    return $this->certificateId;
  }
  public function setPendingManagedCertificateId($pendingManagedCertificateId)
  {
    $this->pendingManagedCertificateId = $pendingManagedCertificateId;
  }
  public function getPendingManagedCertificateId()
  {
    return $this->pendingManagedCertificateId;
  }
  public function setSslManagementType($sslManagementType)
  {
    $this->sslManagementType = $sslManagementType;
  }
  public function getSslManagementType()
  {
    return $this->sslManagementType;
  }
}
