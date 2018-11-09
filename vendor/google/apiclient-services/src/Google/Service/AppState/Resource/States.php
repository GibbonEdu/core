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
 * The "states" collection of methods.
 * Typical usage is:
 *  <code>
 *   $appstateService = new Google_Service_AppState(...);
 *   $states = $appstateService->states;
 *  </code>
 */
class Google_Service_AppState_Resource_States extends Google_Service_Resource
{
  /**
   * Clears (sets to empty) the data for the passed key if and only if the passed
   * version matches the currently stored version. This method results in a
   * conflict error on version mismatch. (states.clear)
   *
   * @param int $stateKey The key for the data to be retrieved.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string currentDataVersion The version of the data to be cleared.
   * Version strings are returned by the server.
   * @return Google_Service_AppState_WriteResult
   */
  public function clear($stateKey, $optParams = array())
  {
    $params = array('stateKey' => $stateKey);
    $params = array_merge($params, $optParams);
    return $this->call('clear', array($params), "Google_Service_AppState_WriteResult");
  }
  /**
   * Deletes a key and the data associated with it. The key is removed and no
   * longer counts against the key quota. Note that since this method is not safe
   * in the face of concurrent modifications, it should only be used for
   * development and testing purposes. Invoking this method in shipping code can
   * result in data loss and data corruption. (states.delete)
   *
   * @param int $stateKey The key for the data to be retrieved.
   * @param array $optParams Optional parameters.
   */
  public function delete($stateKey, $optParams = array())
  {
    $params = array('stateKey' => $stateKey);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }
  /**
   * Retrieves the data corresponding to the passed key. If the key does not exist
   * on the server, an HTTP 404 will be returned. (states.get)
   *
   * @param int $stateKey The key for the data to be retrieved.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AppState_GetResponse
   */
  public function get($stateKey, $optParams = array())
  {
    $params = array('stateKey' => $stateKey);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AppState_GetResponse");
  }
  /**
   * Lists all the states keys, and optionally the state data. (states.listStates)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool includeData Whether to include the full data in addition to
   * the version number
   * @return Google_Service_AppState_ListResponse
   */
  public function listStates($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AppState_ListResponse");
  }
  /**
   * Update the data associated with the input key if and only if the passed
   * version matches the currently stored version. This method is safe in the face
   * of concurrent writes. Maximum per-key size is 128KB. (states.update)
   *
   * @param int $stateKey The key for the data to be retrieved.
   * @param Google_Service_AppState_UpdateRequest $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string currentStateVersion The version of the app state your
   * application is attempting to update. If this does not match the current
   * version, this method will return a conflict error. If there is no data stored
   * on the server for this key, the update will succeed irrespective of the value
   * of this parameter.
   * @return Google_Service_AppState_WriteResult
   */
  public function update($stateKey, Google_Service_AppState_UpdateRequest $postBody, $optParams = array())
  {
    $params = array('stateKey' => $stateKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_AppState_WriteResult");
  }
}
