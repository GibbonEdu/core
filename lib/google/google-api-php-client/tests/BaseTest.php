<?php
/*
 * Copyright 2011 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class BaseTest extends PHPUnit_Framework_TestCase
{
  const KEY = "";
  private $token;
  private $memcacheHost;
  private $memcachePort;

  public function __construct()
  {
    parent::__construct();

    $this->token = $this->loadToken();
    $this->memcacheHost = getenv('MEMCACHE_HOST') ? getenv('MEMCACHE_HOST') : null;
    $this->memcachePort = getenv('MEMCACHE_PORT') ? getenv('MEMCACHE_PORT') : null;
  }

  public function getClient()
  {
    $client = new Google_Client();
    $client->setDeveloperKey(self::KEY);
    if (strlen($this->token)) {
      $client->setAccessToken($this->token);
    }
    if (strlen($this->memcacheHost)) {
      $client->setClassConfig('Google_Cache_Memcache', 'host', $this->memcacheHost);
      $client->setClassConfig('Google_Cache_Memcache', 'port', $this->memcachePort);
    }
    return $client;
  }

  public function checkToken()
  {
    if (!strlen($this->token)) {
      $this->markTestSkipped("Test requires access token\nrun \"php tests/OAuthHelper.php\"");
      return false;
    }
    return true;
  }

  public function loadToken()
  {
    if (file_exists($f = dirname(__FILE__) . DIRECTORY_SEPARATOR . '.accessToken')) {
      $t = file_get_contents($f);
      if ($token = json_decode($t, true)) {
        if ($token['expires_in'] + $token['created'] > time()) {
          return $t;
        }
      }
    }
  }
}
