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

class Google_Service_Vision_GoogleCloudVisionV1p1beta1BoundingPoly extends Google_Collection
{
  protected $collection_key = 'vertices';
  protected $normalizedVerticesType = 'Google_Service_Vision_GoogleCloudVisionV1p1beta1NormalizedVertex';
  protected $normalizedVerticesDataType = 'array';
  protected $verticesType = 'Google_Service_Vision_GoogleCloudVisionV1p1beta1Vertex';
  protected $verticesDataType = 'array';

  /**
   * @param Google_Service_Vision_GoogleCloudVisionV1p1beta1NormalizedVertex
   */
  public function setNormalizedVertices($normalizedVertices)
  {
    $this->normalizedVertices = $normalizedVertices;
  }
  /**
   * @return Google_Service_Vision_GoogleCloudVisionV1p1beta1NormalizedVertex
   */
  public function getNormalizedVertices()
  {
    return $this->normalizedVertices;
  }
  /**
   * @param Google_Service_Vision_GoogleCloudVisionV1p1beta1Vertex
   */
  public function setVertices($vertices)
  {
    $this->vertices = $vertices;
  }
  /**
   * @return Google_Service_Vision_GoogleCloudVisionV1p1beta1Vertex
   */
  public function getVertices()
  {
    return $this->vertices;
  }
}
