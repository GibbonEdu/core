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

class Google_Service_CloudNaturalLanguageAPI_AnalyzeSentimentResponse extends Google_Model
{
  protected $documentSentimentType = 'Google_Service_CloudNaturalLanguageAPI_Sentiment';
  protected $documentSentimentDataType = '';
  public $language;

  public function setDocumentSentiment(Google_Service_CloudNaturalLanguageAPI_Sentiment $documentSentiment)
  {
    $this->documentSentiment = $documentSentiment;
  }
  public function getDocumentSentiment()
  {
    return $this->documentSentiment;
  }
  public function setLanguage($language)
  {
    $this->language = $language;
  }
  public function getLanguage()
  {
    return $this->language;
  }
}
