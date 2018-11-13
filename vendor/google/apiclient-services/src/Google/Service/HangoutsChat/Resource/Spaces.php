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
 * The "spaces" collection of methods.
 * Typical usage is:
 *  <code>
 *   $chatService = new Google_Service_HangoutsChat(...);
 *   $spaces = $chatService->spaces;
 *  </code>
 */
class Google_Service_HangoutsChat_Resource_Spaces extends Google_Service_Resource
{
  /**
   * Returns a space. (spaces.get)
   *
   * @param string $name Required. Resource name of the space, in the form
   * "spaces".
   *
   * Example: spaces/AAAAMpdlehY
   * @param array $optParams Optional parameters.
   * @return Google_Service_HangoutsChat_Space
   */
  public function get($name, $optParams = array())
  {
    $params = array('name' => $name);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_HangoutsChat_Space");
  }
  /**
   * Lists spaces the caller is a member of. (spaces.listSpaces)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken A token identifying a page of results the server
   * should return.
   * @opt_param int pageSize Requested page size. The value is capped at 1000.
   * Server may return fewer results than requested. If unspecified, server will
   * default to 100.
   * @return Google_Service_HangoutsChat_ListSpacesResponse
   */
  public function listSpaces($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_HangoutsChat_ListSpacesResponse");
  }
}
