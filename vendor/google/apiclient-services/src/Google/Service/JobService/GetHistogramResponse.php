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

class Google_Service_JobService_GetHistogramResponse extends Google_Collection
{
  protected $collection_key = 'results';
  protected $metadataType = 'Google_Service_JobService_ResponseMetadata';
  protected $metadataDataType = '';
  protected $resultsType = 'Google_Service_JobService_HistogramResult';
  protected $resultsDataType = 'array';

  /**
   * @param Google_Service_JobService_ResponseMetadata
   */
  public function setMetadata(Google_Service_JobService_ResponseMetadata $metadata)
  {
    $this->metadata = $metadata;
  }
  /**
   * @return Google_Service_JobService_ResponseMetadata
   */
  public function getMetadata()
  {
    return $this->metadata;
  }
  /**
   * @param Google_Service_JobService_HistogramResult
   */
  public function setResults($results)
  {
    $this->results = $results;
  }
  /**
   * @return Google_Service_JobService_HistogramResult
   */
  public function getResults()
  {
    return $this->results;
  }
}
