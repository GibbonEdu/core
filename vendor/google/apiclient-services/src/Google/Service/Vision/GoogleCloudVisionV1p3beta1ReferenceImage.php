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

class Google_Service_Vision_GoogleCloudVisionV1p3beta1ReferenceImage extends Google_Collection
{
  protected $collection_key = 'boundingPolys';
  protected $boundingPolysType = 'Google_Service_Vision_GoogleCloudVisionV1p3beta1BoundingPoly';
  protected $boundingPolysDataType = 'array';
  public $name;
  public $uri;

  /**
   * @param Google_Service_Vision_GoogleCloudVisionV1p3beta1BoundingPoly
   */
  public function setBoundingPolys($boundingPolys)
  {
    $this->boundingPolys = $boundingPolys;
  }
  /**
   * @return Google_Service_Vision_GoogleCloudVisionV1p3beta1BoundingPoly
   */
  public function getBoundingPolys()
  {
    return $this->boundingPolys;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setUri($uri)
  {
    $this->uri = $uri;
  }
  public function getUri()
  {
    return $this->uri;
  }
}
