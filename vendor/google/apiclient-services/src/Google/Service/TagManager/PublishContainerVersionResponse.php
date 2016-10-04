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

class Google_Service_TagManager_PublishContainerVersionResponse extends Google_Model
{
  public $compilerError;
  protected $containerVersionType = 'Google_Service_TagManager_ContainerVersion';
  protected $containerVersionDataType = '';

  public function setCompilerError($compilerError)
  {
    $this->compilerError = $compilerError;
  }
  public function getCompilerError()
  {
    return $this->compilerError;
  }
  public function setContainerVersion(Google_Service_TagManager_ContainerVersion $containerVersion)
  {
    $this->containerVersion = $containerVersion;
  }
  public function getContainerVersion()
  {
    return $this->containerVersion;
  }
}
