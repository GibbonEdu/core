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
 * The "models" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mlService = new Google_Service_CloudMachineLearningEngine(...);
 *   $models = $mlService->models;
 *  </code>
 */
class Google_Service_CloudMachineLearningEngine_Resource_ProjectsModels extends Google_Service_Resource
{
  /**
   * Creates a model which will later contain one or more versions.
   *
   * You must add at least one version before you can request predictions from the
   * model. Add versions by calling [projects.models.versions.create](/ml-
   * engine/reference/rest/v1/projects.models.versions/create). (models.create)
   *
   * @param string $parent Required. The project name.
   * @param Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1Model $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1Model
   */
  public function create($parent, Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1Model $postBody, $optParams = array())
  {
    $params = array('parent' => $parent, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1Model");
  }
  /**
   * Deletes a model.
   *
   * You can only delete a model if there are no versions in it. You can delete
   * versions by calling [projects.models.versions.delete](/ml-
   * engine/reference/rest/v1/projects.models.versions/delete). (models.delete)
   *
   * @param string $name Required. The name of the model.
   * @param array $optParams Optional parameters.
   * @return Google_Service_CloudMachineLearningEngine_GoogleLongrunningOperation
   */
  public function delete($name, $optParams = array())
  {
    $params = array('name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_CloudMachineLearningEngine_GoogleLongrunningOperation");
  }
  /**
   * Gets information about a model, including its name, the description (if set),
   * and the default version (if at least one version of the model has been
   * deployed). (models.get)
   *
   * @param string $name Required. The name of the model.
   * @param array $optParams Optional parameters.
   * @return Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1Model
   */
  public function get($name, $optParams = array())
  {
    $params = array('name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1Model");
  }
  /**
   * Gets the access control policy for a resource. Returns an empty policy if the
   * resource exists and does not have a policy set. (models.getIamPolicy)
   *
   * @param string $resource REQUIRED: The resource for which the policy is being
   * requested. See the operation documentation for the appropriate value for this
   * field.
   * @param array $optParams Optional parameters.
   * @return Google_Service_CloudMachineLearningEngine_GoogleIamV1Policy
   */
  public function getIamPolicy($resource, $optParams = array())
  {
    $params = array('resource' => $resource);
    $params = array_merge($params, $optParams);
    return $this->call('getIamPolicy', array($params), "Google_Service_CloudMachineLearningEngine_GoogleIamV1Policy");
  }
  /**
   * Lists the models in a project.
   *
   * Each project can contain multiple models, and each model can have multiple
   * versions. (models.listProjectsModels)
   *
   * @param string $parent Required. The name of the project whose models are to
   * be listed.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Optional. A page token to request the next page
   * of results.
   *
   * You get the token from the `next_page_token` field of the response from the
   * previous call.
   * @opt_param int pageSize Optional. The number of models to retrieve per "page"
   * of results. If there are more remaining results than this number, the
   * response message will contain a valid value in the `next_page_token` field.
   *
   * The default value is 20, and the maximum page size is 100.
   * @return Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1ListModelsResponse
   */
  public function listProjectsModels($parent, $optParams = array())
  {
    $params = array('parent' => $parent);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1ListModelsResponse");
  }
  /**
   * Sets the access control policy on the specified resource. Replaces any
   * existing policy. (models.setIamPolicy)
   *
   * @param string $resource REQUIRED: The resource for which the policy is being
   * specified. See the operation documentation for the appropriate value for this
   * field.
   * @param Google_Service_CloudMachineLearningEngine_GoogleIamV1SetIamPolicyRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_CloudMachineLearningEngine_GoogleIamV1Policy
   */
  public function setIamPolicy($resource, Google_Service_CloudMachineLearningEngine_GoogleIamV1SetIamPolicyRequest $postBody, $optParams = array())
  {
    $params = array('resource' => $resource, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('setIamPolicy', array($params), "Google_Service_CloudMachineLearningEngine_GoogleIamV1Policy");
  }
  /**
   * Returns permissions that a caller has on the specified resource. If the
   * resource does not exist, this will return an empty set of permissions, not a
   * NOT_FOUND error.
   *
   * Note: This operation is designed to be used for building permission-aware UIs
   * and command-line tools, not for authorization checking. This operation may
   * "fail open" without warning. (models.testIamPermissions)
   *
   * @param string $resource REQUIRED: The resource for which the policy detail is
   * being requested. See the operation documentation for the appropriate value
   * for this field.
   * @param Google_Service_CloudMachineLearningEngine_GoogleIamV1TestIamPermissionsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_CloudMachineLearningEngine_GoogleIamV1TestIamPermissionsResponse
   */
  public function testIamPermissions($resource, Google_Service_CloudMachineLearningEngine_GoogleIamV1TestIamPermissionsRequest $postBody, $optParams = array())
  {
    $params = array('resource' => $resource, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('testIamPermissions', array($params), "Google_Service_CloudMachineLearningEngine_GoogleIamV1TestIamPermissionsResponse");
  }
}
