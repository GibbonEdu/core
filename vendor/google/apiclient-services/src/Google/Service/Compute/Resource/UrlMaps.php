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
 * The "urlMaps" collection of methods.
 * Typical usage is:
 *  <code>
 *   $computeService = new Google_Service_Compute(...);
 *   $urlMaps = $computeService->urlMaps;
 *  </code>
 */
class Google_Service_Compute_Resource_UrlMaps extends Google_Service_Resource
{
  /**
   * Deletes the specified UrlMap resource. (urlMaps.delete)
   *
   * @param string $project Project ID for this request.
   * @param string $urlMap Name of the UrlMap resource to delete.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Compute_Operation
   */
  public function delete($project, $urlMap, $optParams = array())
  {
    $params = array('project' => $project, 'urlMap' => $urlMap);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_Compute_Operation");
  }
  /**
   * Returns the specified UrlMap resource. Get a list of available URL maps by
   * making a list() request. (urlMaps.get)
   *
   * @param string $project Project ID for this request.
   * @param string $urlMap Name of the UrlMap resource to return.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Compute_UrlMap
   */
  public function get($project, $urlMap, $optParams = array())
  {
    $params = array('project' => $project, 'urlMap' => $urlMap);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Compute_UrlMap");
  }
  /**
   * Creates a UrlMap resource in the specified project using the data included in
   * the request. (urlMaps.insert)
   *
   * @param string $project Project ID for this request.
   * @param Google_Service_Compute_UrlMap $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Compute_Operation
   */
  public function insert($project, Google_Service_Compute_UrlMap $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Compute_Operation");
  }
  /**
   * Initiates a cache invalidation operation, invalidating the specified path,
   * scoped to the specified UrlMap. (urlMaps.invalidateCache)
   *
   * @param string $project Project ID for this request.
   * @param string $urlMap Name of the UrlMap scoping this request.
   * @param Google_Service_Compute_CacheInvalidationRule $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Compute_Operation
   */
  public function invalidateCache($project, $urlMap, Google_Service_Compute_CacheInvalidationRule $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'urlMap' => $urlMap, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('invalidateCache', array($params), "Google_Service_Compute_Operation");
  }
  /**
   * Retrieves the list of UrlMap resources available to the specified project.
   * (urlMaps.listUrlMaps)
   *
   * @param string $project Project ID for this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Sets a filter expression for filtering listed
   * resources, in the form filter={expression}. Your {expression} must be in the
   * format: field_name comparison_string literal_string.
   *
   * The field_name is the name of the field you want to compare. Only atomic
   * field types are supported (string, number, boolean). The comparison_string
   * must be either eq (equals) or ne (not equals). The literal_string is the
   * string value to filter to. The literal value must be valid for the type of
   * field you are filtering by (string, number, boolean). For string fields, the
   * literal value is interpreted as a regular expression using RE2 syntax. The
   * literal value must match the entire field.
   *
   * For example, to filter for instances that do not have a name of example-
   * instance, you would use filter=name ne example-instance.
   *
   * You can filter on nested fields. For example, you could filter on instances
   * that have set the scheduling.automaticRestart field to true. Use filtering on
   * nested fields to take advantage of labels to organize and search for results
   * based on label values.
   *
   * To filter on multiple expressions, provide each separate expression within
   * parentheses. For example, (scheduling.automaticRestart eq true) (zone eq us-
   * central1-f). Multiple expressions are treated as AND expressions, meaning
   * that resources must match all expressions to pass the filters.
   * @opt_param string maxResults The maximum number of results per page that
   * should be returned. If the number of available results is larger than
   * maxResults, Compute Engine returns a nextPageToken that can be used to get
   * the next page of results in subsequent list requests.
   * @opt_param string pageToken Specifies a page token to use. Set pageToken to
   * the nextPageToken returned by a previous list request to get the next page of
   * results.
   * @return Google_Service_Compute_UrlMapList
   */
  public function listUrlMaps($project, $optParams = array())
  {
    $params = array('project' => $project);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Compute_UrlMapList");
  }
  /**
   * Updates the entire content of the UrlMap resource. This method supports patch
   * semantics. (urlMaps.patch)
   *
   * @param string $project Project ID for this request.
   * @param string $urlMap Name of the UrlMap resource to update.
   * @param Google_Service_Compute_UrlMap $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Compute_Operation
   */
  public function patch($project, $urlMap, Google_Service_Compute_UrlMap $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'urlMap' => $urlMap, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Compute_Operation");
  }
  /**
   * Updates the entire content of the UrlMap resource. (urlMaps.update)
   *
   * @param string $project Project ID for this request.
   * @param string $urlMap Name of the UrlMap resource to update.
   * @param Google_Service_Compute_UrlMap $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Compute_Operation
   */
  public function update($project, $urlMap, Google_Service_Compute_UrlMap $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'urlMap' => $urlMap, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Compute_Operation");
  }
  /**
   * Runs static validation for the UrlMap. In particular, the tests of the
   * provided UrlMap will be run. Calling this method does NOT create the UrlMap.
   * (urlMaps.validate)
   *
   * @param string $project Project ID for this request.
   * @param string $urlMap Name of the UrlMap resource to be validated as.
   * @param Google_Service_Compute_UrlMapsValidateRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Compute_UrlMapsValidateResponse
   */
  public function validate($project, $urlMap, Google_Service_Compute_UrlMapsValidateRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'urlMap' => $urlMap, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('validate', array($params), "Google_Service_Compute_UrlMapsValidateResponse");
  }
}
