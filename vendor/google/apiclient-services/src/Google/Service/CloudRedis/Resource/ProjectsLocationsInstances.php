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
 * The "instances" collection of methods.
 * Typical usage is:
 *  <code>
 *   $redisService = new Google_Service_CloudRedis(...);
 *   $instances = $redisService->instances;
 *  </code>
 */
class Google_Service_CloudRedis_Resource_ProjectsLocationsInstances extends Google_Service_Resource
{
  /**
   * Creates a Redis instance based on the specified tier and memory size.
   *
   * By default, the instance is accessible from the project's [default
   * network](/compute/docs/networks-and-firewalls#networks).
   *
   * The creation is executed asynchronously and callers may check the returned
   * operation to track its progress. Once the operation is completed the Redis
   * instance will be fully functional. Completed longrunning.Operation will
   * contain the new instance object in the response field.
   *
   * The returned operation is automatically deleted after a few hours, so there
   * is no need to call DeleteOperation. (instances.create)
   *
   * @param string $parent Required. The resource name of the instance location
   * using the form:     `projects/{project_id}/locations/{location_id}` where
   * `location_id` refers to a GCP region
   * @param Google_Service_CloudRedis_Instance $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string instanceId Required. The logical name of the Redis instance
   * in the customer project with the following restrictions:
   *
   * * Must contain only lowercase letters, numbers, and hyphens. * Must start
   * with a letter. * Must be between 1-40 characters. * Must end with a number or
   * a letter. * Must be unique within the customer project / location
   * @return Google_Service_CloudRedis_Operation
   */
  public function create($parent, Google_Service_CloudRedis_Instance $postBody, $optParams = array())
  {
    $params = array('parent' => $parent, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_CloudRedis_Operation");
  }
  /**
   * Deletes a specific Redis instance.  Instance stops serving and data is
   * deleted. (instances.delete)
   *
   * @param string $name Required. Redis instance resource name using the form:
   * `projects/{project_id}/locations/{location_id}/instances/{instance_id}` where
   * `location_id` refers to a GCP region
   * @param array $optParams Optional parameters.
   * @return Google_Service_CloudRedis_Operation
   */
  public function delete($name, $optParams = array())
  {
    $params = array('name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_CloudRedis_Operation");
  }
  /**
   * Gets the details of a specific Redis instance. (instances.get)
   *
   * @param string $name Required. Redis instance resource name using the form:
   * `projects/{project_id}/locations/{location_id}/instances/{instance_id}` where
   * `location_id` refers to a GCP region
   * @param array $optParams Optional parameters.
   * @return Google_Service_CloudRedis_Instance
   */
  public function get($name, $optParams = array())
  {
    $params = array('name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_CloudRedis_Instance");
  }
  /**
   * Lists all Redis instances owned by a project in either the specified location
   * (region) or all locations.
   *
   * The location should have the following format: *
   * `projects/{project_id}/locations/{location_id}`
   *
   * If `location_id` is specified as `-` (wildcard), then all regions available
   * to the project are queried, and the results are aggregated.
   * (instances.listProjectsLocationsInstances)
   *
   * @param string $parent Required. The resource name of the instance location
   * using the form:     `projects/{project_id}/locations/{location_id}` where
   * `location_id` refers to a GCP region
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize The maximum number of items to return.
   *
   * If not specified, a default value of 1000 will be used by the service.
   * Regardless of the page_size value, the response may include a partial list
   * and a caller should only rely on response's next_page_token to determine if
   * there are more instances left to be queried.
   * @opt_param string pageToken The next_page_token value returned from a
   * previous List request, if any.
   * @return Google_Service_CloudRedis_ListInstancesResponse
   */
  public function listProjectsLocationsInstances($parent, $optParams = array())
  {
    $params = array('parent' => $parent);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_CloudRedis_ListInstancesResponse");
  }
  /**
   * Updates the metadata and configuration of a specific Redis instance.
   *
   * Completed longrunning.Operation will contain the new instance object in the
   * response field. The returned operation is automatically deleted after a few
   * hours, so there is no need to call DeleteOperation. (instances.patch)
   *
   * @param string $name Required. Unique name of the resource in this scope
   * including project and location using the form:
   * `projects/{project_id}/locations/{location_id}/instances/{instance_id}`
   *
   * Note: Redis instances are managed and addressed at regional level so
   * location_id here refers to a GCP region; however, users may choose which
   * specific zone (or collection of zones for cross-zone instances) an instance
   * should be provisioned in. Refer to [location_id] and
   * [alternative_location_id] fields for more details.
   * @param Google_Service_CloudRedis_Instance $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string updateMask Required. Mask of fields to update. At least one
   * path must be supplied in this field. The elements of the repeated paths field
   * may only include these fields from Instance:
   *
   * `displayName` `labels` `memorySizeGb` `redisConfig`
   * @return Google_Service_CloudRedis_Operation
   */
  public function patch($name, Google_Service_CloudRedis_Instance $postBody, $optParams = array())
  {
    $params = array('name' => $name, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_CloudRedis_Operation");
  }
}
