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

/**
 * The "feedback" collection of methods.
 * Typical usage is:
 *  <code>
 *   $alertcenterService = new Google_Service_AlertCenter(...);
 *   $feedback = $alertcenterService->feedback;
 *  </code>
 */
class Google_Service_AlertCenter_Resource_AlertsFeedback extends Google_Service_Resource
{
  /**
   * Creates a new alert feedback. (feedback.create)
   *
   * @param string $alertId Required. The identifier of the alert this feedback
   * belongs to. Returns a NOT_FOUND error if no such alert.
   * @param Google_Service_AlertCenter_AlertFeedback $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string customerId Optional. The unique identifier of the Google
   * account of the customer the alert's feedback is associated with. This is
   * obfuscated and not the plain customer ID as stored internally. Inferred from
   * the caller identity if not provided.
   * @return Google_Service_AlertCenter_AlertFeedback
   */
  public function create($alertId, Google_Service_AlertCenter_AlertFeedback $postBody, $optParams = array())
  {
    $params = array('alertId' => $alertId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_AlertCenter_AlertFeedback");
  }
  /**
   * Lists all the feedback for an alert. (feedback.listAlertsFeedback)
   *
   * @param string $alertId Required. The alert identifier. If the alert does not
   * exist returns a NOT_FOUND error.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string customerId Optional. The unique identifier of the Google
   * account of the customer the alert is associated with. This is obfuscated and
   * not the plain customer ID as stored internally. Inferred from the caller
   * identity if not provided.
   * @return Google_Service_AlertCenter_ListAlertFeedbackResponse
   */
  public function listAlertsFeedback($alertId, $optParams = array())
  {
    $params = array('alertId' => $alertId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AlertCenter_ListAlertFeedbackResponse");
  }
}
