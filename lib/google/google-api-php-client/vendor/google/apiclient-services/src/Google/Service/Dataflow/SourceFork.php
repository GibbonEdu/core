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

class Google_Service_Dataflow_SourceFork extends Google_Model
{
  protected $primaryType = 'Google_Service_Dataflow_SourceSplitShard';
  protected $primaryDataType = '';
  protected $primarySourceType = 'Google_Service_Dataflow_DerivedSource';
  protected $primarySourceDataType = '';
  protected $residualType = 'Google_Service_Dataflow_SourceSplitShard';
  protected $residualDataType = '';
  protected $residualSourceType = 'Google_Service_Dataflow_DerivedSource';
  protected $residualSourceDataType = '';

  public function setPrimary(Google_Service_Dataflow_SourceSplitShard $primary)
  {
    $this->primary = $primary;
  }
  public function getPrimary()
  {
    return $this->primary;
  }
  public function setPrimarySource(Google_Service_Dataflow_DerivedSource $primarySource)
  {
    $this->primarySource = $primarySource;
  }
  public function getPrimarySource()
  {
    return $this->primarySource;
  }
  public function setResidual(Google_Service_Dataflow_SourceSplitShard $residual)
  {
    $this->residual = $residual;
  }
  public function getResidual()
  {
    return $this->residual;
  }
  public function setResidualSource(Google_Service_Dataflow_DerivedSource $residualSource)
  {
    $this->residualSource = $residualSource;
  }
  public function getResidualSource()
  {
    return $this->residualSource;
  }
}
