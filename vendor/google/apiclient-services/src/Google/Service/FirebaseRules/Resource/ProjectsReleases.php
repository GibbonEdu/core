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
 * The "releases" collection of methods.
 * Typical usage is:
 *  <code>
 *   $firebaserulesService = new Google_Service_FirebaseRules(...);
 *   $releases = $firebaserulesService->releases;
 *  </code>
 */
class Google_Service_FirebaseRules_Resource_ProjectsReleases extends Google_Service_Resource
{
  /**
   * Create a `Release`.
   *
   * Release names should reflect the developer's deployment practices. For
   * example, the release name may include the environment name, application name,
   * application version, or any other name meaningful to the developer. Once a
   * `Release` refers to a `Ruleset`, the rules can be enforced by Firebase Rules-
   * enabled services.
   *
   * More than one `Release` may be 'live' concurrently. Consider the following
   * three `Release` names for `projects/foo` and the `Ruleset` to which they
   * refer.
   *
   * Release Name                    | Ruleset Name
   * --------------------------------|------------- projects/foo/releases/prod
   * | projects/foo/rulesets/uuid123 projects/foo/releases/prod/beta |
   * projects/foo/rulesets/uuid123 projects/foo/releases/prod/v23  |
   * projects/foo/rulesets/uuid456
   *
   * The table reflects the `Ruleset` rollout in progress. The `prod` and
   * `prod/beta` releases refer to the same `Ruleset`. However, `prod/v23` refers
   * to a new `Ruleset`. The `Ruleset` reference for a `Release` may be updated
   * using the UpdateRelease method. (releases.create)
   *
   * @param string $name Resource name for the project which owns this `Release`.
   *
   * Format: `projects/{project_id}`
   * @param Google_Service_FirebaseRules_Release $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_FirebaseRules_Release
   */
  public function create($name, Google_Service_FirebaseRules_Release $postBody, $optParams = array())
  {
    $params = array('name' => $name, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_FirebaseRules_Release");
  }
  /**
   * Delete a `Release` by resource name. (releases.delete)
   *
   * @param string $name Resource name for the `Release` to delete.
   *
   * Format: `projects/{project_id}/releases/{release_id}`
   * @param array $optParams Optional parameters.
   * @return Google_Service_FirebaseRules_FirebaserulesEmpty
   */
  public function delete($name, $optParams = array())
  {
    $params = array('name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_FirebaseRules_FirebaserulesEmpty");
  }
  /**
   * Get a `Release` by name. (releases.get)
   *
   * @param string $name Resource name of the `Release`.
   *
   * Format: `projects/{project_id}/releases/{release_id}`
   * @param array $optParams Optional parameters.
   * @return Google_Service_FirebaseRules_Release
   */
  public function get($name, $optParams = array())
  {
    $params = array('name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_FirebaseRules_Release");
  }
  /**
   * Get the `Release` executable to use when enforcing rules.
   * (releases.getExecutable)
   *
   * @param string $name Resource name of the `Release`.
   *
   * Format: `projects/{project_id}/releases/{release_id}`
   * @param array $optParams Optional parameters.
   *
   * @opt_param string executableVersion The requested runtime executable version.
   * Defaults to FIREBASE_RULES_EXECUTABLE_V1.
   * @return Google_Service_FirebaseRules_GetReleaseExecutableResponse
   */
  public function getExecutable($name, $optParams = array())
  {
    $params = array('name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('getExecutable', array($params), "Google_Service_FirebaseRules_GetReleaseExecutableResponse");
  }
  /**
   * List the `Release` values for a project. This list may optionally be filtered
   * by `Release` name, `Ruleset` name, `TestSuite` name, or any combination
   * thereof. (releases.listProjectsReleases)
   *
   * @param string $name Resource name for the project.
   *
   * Format: `projects/{project_id}`
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Next page token for the next batch of `Release`
   * instances.
   * @opt_param int pageSize Page size to load. Maximum of 100. Defaults to 10.
   * Note: `page_size` is just a hint and the service may choose to load fewer
   * than `page_size` results due to the size of the output. To traverse all of
   * the releases, the caller should iterate until the `page_token` on the
   * response is empty.
   * @opt_param string filter `Release` filter. The list method supports filters
   * with restrictions on the `Release.name`, `Release.ruleset_name`, and
   * `Release.test_suite_name`.
   *
   * Example 1: A filter of 'name=prod*' might return `Release`s with names within
   * 'projects/foo' prefixed with 'prod':
   *
   * Name                          | Ruleset Name
   * ------------------------------|------------- projects/foo/releases/prod    |
   * projects/foo/rulesets/uuid1234 projects/foo/releases/prod/v1 |
   * projects/foo/rulesets/uuid1234 projects/foo/releases/prod/v2 |
   * projects/foo/rulesets/uuid8888
   *
   * Example 2: A filter of `name=prod* ruleset_name=uuid1234` would return only
   * `Release` instances for 'projects/foo' with names prefixed with 'prod'
   * referring to the same `Ruleset` name of 'uuid1234':
   *
   * Name                          | Ruleset Name
   * ------------------------------|------------- projects/foo/releases/prod    |
   * projects/foo/rulesets/1234 projects/foo/releases/prod/v1 |
   * projects/foo/rulesets/1234
   *
   * In the examples, the filter parameters refer to the search filters are
   * relative to the project. Fully qualified prefixed may also be used. e.g.
   * `test_suite_name=projects/foo/testsuites/uuid1`
   * @return Google_Service_FirebaseRules_ListReleasesResponse
   */
  public function listProjectsReleases($name, $optParams = array())
  {
    $params = array('name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_FirebaseRules_ListReleasesResponse");
  }
  /**
   * Update a `Release` via PATCH.
   *
   * Only updates to the `ruleset_name` and `test_suite_name` fields will be
   * honored. `Release` rename is not supported. To create a `Release` use the
   * CreateRelease method. (releases.patch)
   *
   * @param string $name Resource name for the project which owns this `Release`.
   *
   * Format: `projects/{project_id}`
   * @param Google_Service_FirebaseRules_UpdateReleaseRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_FirebaseRules_Release
   */
  public function patch($name, Google_Service_FirebaseRules_UpdateReleaseRequest $postBody, $optParams = array())
  {
    $params = array('name' => $name, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_FirebaseRules_Release");
  }
}
