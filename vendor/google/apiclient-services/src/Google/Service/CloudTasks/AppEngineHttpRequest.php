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

class Google_Service_CloudTasks_AppEngineHttpRequest extends Google_Model
{
  protected $appEngineRoutingType = 'Google_Service_CloudTasks_AppEngineRouting';
  protected $appEngineRoutingDataType = '';
  public $body;
  public $headers;
  public $httpMethod;
  public $relativeUri;

  /**
   * @param Google_Service_CloudTasks_AppEngineRouting
   */
  public function setAppEngineRouting(Google_Service_CloudTasks_AppEngineRouting $appEngineRouting)
  {
    $this->appEngineRouting = $appEngineRouting;
  }
  /**
   * @return Google_Service_CloudTasks_AppEngineRouting
   */
  public function getAppEngineRouting()
  {
    return $this->appEngineRouting;
  }
  public function setBody($body)
  {
    $this->body = $body;
  }
  public function getBody()
  {
    return $this->body;
  }
  public function setHeaders($headers)
  {
    $this->headers = $headers;
  }
  public function getHeaders()
  {
    return $this->headers;
  }
  public function setHttpMethod($httpMethod)
  {
    $this->httpMethod = $httpMethod;
  }
  public function getHttpMethod()
  {
    return $this->httpMethod;
  }
  public function setRelativeUri($relativeUri)
  {
    $this->relativeUri = $relativeUri;
  }
  public function getRelativeUri()
  {
    return $this->relativeUri;
  }
}
