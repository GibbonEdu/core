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
 * The "alerts" collection of methods.
 * Typical usage is:
 *  <code>
 *   $alertcenterService = new Google_Service_AlertCenter(...);
 *   $alerts = $alertcenterService->alerts;
 *  </code>
 */
class Google_Service_AlertCenter_Resource_Alerts extends Google_Service_Resource
{
  /**
   * Marks the specified alert for deletion. An alert that has been marked for
   * deletion will be excluded from the results of a List operation by default,
   * and will be removed from the Alert Center after 30 days. Marking an alert for
   * deletion will have no effect on an alert which has already been marked for
   * deletion. Attempting to mark a nonexistent alert for deletion will return
   * NOT_FOUND. (alerts.delete)
   *
   * @param string $alertId Required. The identifier of the alert to delete.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string customerId Optional. The unique identifier of the Google
   * account of the customer the alert is associated with. This is obfuscated and
   * not the plain customer ID as stored internally. Inferred from the caller
   * identity if not provided.
   * @return Google_Service_AlertCenter_AlertcenterEmpty
   */
  public function delete($alertId, $optParams = array())
  {
    $params = array('alertId' => $alertId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_AlertCenter_AlertcenterEmpty");
  }
  /**
   * Gets the specified alert. (alerts.get)
   *
   * @param string $alertId Required. The identifier of the alert to retrieve.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string customerId Optional. The unique identifier of the Google
   * account of the customer the alert is associated with. This is obfuscated and
   * not the plain customer ID as stored internally. Inferred from the caller
   * identity if not provided.
   * @return Google_Service_AlertCenter_Alert
   */
  public function get($alertId, $optParams = array())
  {
    $params = array('alertId' => $alertId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AlertCenter_Alert");
  }
  /**
   * Lists all the alerts for the current user and application.
   * (alerts.listAlerts)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string customerId Optional. The unique identifier of the Google
   * account of the customer the alerts are associated with. This is obfuscated
   * and not the plain customer ID as stored internally. Inferred from the caller
   * identity if not provided.
   * @opt_param int pageSize Optional. Requested page size. Server may return
   * fewer items than requested. If unspecified, server will pick an appropriate
   * default.
   * @opt_param string filter Optional. Query string for filtering alert results.
   * This string must be specified as an expression or list of expressions, using
   * the following grammar:
   *
   * ### Expressions
   *
   * An expression has the general form `  `.
   *
   * A field or value which contains a space or a colon must be enclosed by double
   * quotes.
   *
   * #### Operators
   *
   * Operators follow the BNF specification:
   *
   * ` ::= "=" | ":"`
   *
   * ` ::= "<" | ">" | "<=" | ">="`
   *
   * Relational operators are defined only for timestamp fields. Equality
   * operators are defined only for string fields.
   *
   * #### Timestamp fields
   *
   * The value supplied for a timestamp field must be an [RFC
   * 3339](https://tools.ietf.org/html/rfc3339) date-time string.
   *
   * Supported timestamp fields are `create_time`, `start_time`, and `end_time`.
   *
   * #### String fields
   *
   * The value supplied for a string field may be an arbitrary string.
   *
   * #### Examples
   *
   * To query for all alerts created on or after April 5, 2018:
   *
   * `create_time >= "2018-04-05T00:00:00Z"`
   *
   * To query for all alerts from the source "Gmail phishing":
   *
   * `source:"Gmail phishing"`
   *
   * ### Joining expressions
   *
   * Expressions may be joined to form a more complex query. The BNF specification
   * is:
   *
   * ` ::=  |    |  ` ` ::= "AND" | "OR" | ""` ` ::= "NOT"`
   *
   * Using the empty string as a conjunction acts as an implicit AND.
   *
   * The precedence of joining operations, from highest to lowest, is NOT, AND,
   * OR.
   *
   * #### Examples
   *
   * To query for all alerts which started in 2017:
   *
   * `start_time >= "2017-01-01T00:00:00Z" AND start_time <
   * "2018-01-01T00:00:00Z"`
   *
   * To query for all user reported phishing alerts from the source "Gmail
   * phishing":
   *
   * `type:"User reported phishing" source:"Gmail phishing"`
   * @opt_param string pageToken Optional. A token identifying a page of results
   * the server should return. If empty, a new iteration is started. To continue
   * an iteration, pass in the value from the previous ListAlertsResponse's
   * next_page_token field.
   * @opt_param string orderBy Optional. Sort the list results by a certain order.
   * If not specified results may be returned in arbitrary order. You can sort the
   * results in a descending order based on the creation timestamp using
   * order_by="create_time desc". Currently, only sorting by create_time desc is
   * supported.
   * @return Google_Service_AlertCenter_ListAlertsResponse
   */
  public function listAlerts($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AlertCenter_ListAlertsResponse");
  }
}
