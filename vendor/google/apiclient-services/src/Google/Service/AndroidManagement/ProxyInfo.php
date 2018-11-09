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

class Google_Service_AndroidManagement_ProxyInfo extends Google_Collection
{
  protected $collection_key = 'excludedHosts';
  public $excludedHosts;
  public $host;
  public $pacUri;
  public $port;

  public function setExcludedHosts($excludedHosts)
  {
    $this->excludedHosts = $excludedHosts;
  }
  public function getExcludedHosts()
  {
    return $this->excludedHosts;
  }
  public function setHost($host)
  {
    $this->host = $host;
  }
  public function getHost()
  {
    return $this->host;
  }
  public function setPacUri($pacUri)
  {
    $this->pacUri = $pacUri;
  }
  public function getPacUri()
  {
    return $this->pacUri;
  }
  public function setPort($port)
  {
    $this->port = $port;
  }
  public function getPort()
  {
    return $this->port;
  }
}
