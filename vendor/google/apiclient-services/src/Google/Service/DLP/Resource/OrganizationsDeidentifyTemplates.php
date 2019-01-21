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
 * The "deidentifyTemplates" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dlpService = new Google_Service_DLP(...);
 *   $deidentifyTemplates = $dlpService->deidentifyTemplates;
 *  </code>
 */
class Google_Service_DLP_Resource_OrganizationsDeidentifyTemplates extends Google_Service_Resource
{
  /**
   * Creates a DeidentifyTemplate for re-using frequently used configuration for
   * de-identifying content, images, and storage. See
   * https://cloud.google.com/dlp/docs/creating-templates-deid to learn more.
   * (deidentifyTemplates.create)
   *
   * @param string $parent The parent resource name, for example projects/my-
   * project-id or organizations/my-org-id.
   * @param Google_Service_DLP_GooglePrivacyDlpV2CreateDeidentifyTemplateRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_DLP_GooglePrivacyDlpV2DeidentifyTemplate
   */
  public function create($parent, Google_Service_DLP_GooglePrivacyDlpV2CreateDeidentifyTemplateRequest $postBody, $optParams = array())
  {
    $params = array('parent' => $parent, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_DLP_GooglePrivacyDlpV2DeidentifyTemplate");
  }
  /**
   * Deletes a DeidentifyTemplate. See https://cloud.google.com/dlp/docs/creating-
   * templates-deid to learn more. (deidentifyTemplates.delete)
   *
   * @param string $name Resource name of the organization and deidentify template
   * to be deleted, for example
   * `organizations/433245324/deidentifyTemplates/432452342` or projects/project-
   * id/deidentifyTemplates/432452342.
   * @param array $optParams Optional parameters.
   * @return Google_Service_DLP_GoogleProtobufEmpty
   */
  public function delete($name, $optParams = array())
  {
    $params = array('name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_DLP_GoogleProtobufEmpty");
  }
  /**
   * Gets a DeidentifyTemplate. See https://cloud.google.com/dlp/docs/creating-
   * templates-deid to learn more. (deidentifyTemplates.get)
   *
   * @param string $name Resource name of the organization and deidentify template
   * to be read, for example
   * `organizations/433245324/deidentifyTemplates/432452342` or projects/project-
   * id/deidentifyTemplates/432452342.
   * @param array $optParams Optional parameters.
   * @return Google_Service_DLP_GooglePrivacyDlpV2DeidentifyTemplate
   */
  public function get($name, $optParams = array())
  {
    $params = array('name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_DLP_GooglePrivacyDlpV2DeidentifyTemplate");
  }
  /**
   * Lists DeidentifyTemplates. See https://cloud.google.com/dlp/docs/creating-
   * templates-deid to learn more.
   * (deidentifyTemplates.listOrganizationsDeidentifyTemplates)
   *
   * @param string $parent The parent resource name, for example projects/my-
   * project-id or organizations/my-org-id.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Optional page token to continue retrieval. Comes
   * from previous call to `ListDeidentifyTemplates`.
   * @opt_param string orderBy Optional comma separated list of fields to order
   * by, followed by `asc` or `desc` postfix. This list is case-insensitive,
   * default sorting order is ascending, redundant space characters are
   * insignificant.
   *
   * Example: `name asc,update_time, create_time desc`
   *
   * Supported fields are:
   *
   * - `create_time`: corresponds to time the template was created. -
   * `update_time`: corresponds to time the template was last updated. - `name`:
   * corresponds to template's name. - `display_name`: corresponds to template's
   * display name.
   * @opt_param int pageSize Optional size of the page, can be limited by server.
   * If zero server returns a page of max size 100.
   * @return Google_Service_DLP_GooglePrivacyDlpV2ListDeidentifyTemplatesResponse
   */
  public function listOrganizationsDeidentifyTemplates($parent, $optParams = array())
  {
    $params = array('parent' => $parent);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_DLP_GooglePrivacyDlpV2ListDeidentifyTemplatesResponse");
  }
  /**
   * Updates the DeidentifyTemplate. See https://cloud.google.com/dlp/docs
   * /creating-templates-deid to learn more. (deidentifyTemplates.patch)
   *
   * @param string $name Resource name of organization and deidentify template to
   * be updated, for example
   * `organizations/433245324/deidentifyTemplates/432452342` or projects/project-
   * id/deidentifyTemplates/432452342.
   * @param Google_Service_DLP_GooglePrivacyDlpV2UpdateDeidentifyTemplateRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_DLP_GooglePrivacyDlpV2DeidentifyTemplate
   */
  public function patch($name, Google_Service_DLP_GooglePrivacyDlpV2UpdateDeidentifyTemplateRequest $postBody, $optParams = array())
  {
    $params = array('name' => $name, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_DLP_GooglePrivacyDlpV2DeidentifyTemplate");
  }
}
