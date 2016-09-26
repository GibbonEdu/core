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

/**
 * The "results" collection of methods.
 * Typical usage is:
 *  <code>
 *   $consumersurveysService = new Google_Service_ConsumerSurveys(...);
 *   $results = $consumersurveysService->results;
 *  </code>
 */
class Google_Service_ConsumerSurveys_Resource_Results extends Google_Service_Resource
{
  /**
   * Retrieves any survey results that have been produced so far. Results are
   * formatted as an Excel file. (results.get)
   *
   * @param string $surveyUrlId External URL ID for the survey.
   * @param array $optParams Optional parameters.
   * @return Google_Service_ConsumerSurveys_SurveyResults
   */
  public function get($surveyUrlId, $optParams = array())
  {
    $params = array('surveyUrlId' => $surveyUrlId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_ConsumerSurveys_SurveyResults");
  }
}
