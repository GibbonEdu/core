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

class Google_Service_Games_RoomParticipant extends Google_Collection
{
  protected $collection_key = 'capabilities';
  public $autoMatched;
  protected $autoMatchedPlayerType = 'Google_Service_Games_AnonymousPlayer';
  protected $autoMatchedPlayerDataType = '';
  public $capabilities;
  protected $clientAddressType = 'Google_Service_Games_RoomClientAddress';
  protected $clientAddressDataType = '';
  public $connected;
  public $id;
  public $kind;
  public $leaveReason;
  protected $playerType = 'Google_Service_Games_Player';
  protected $playerDataType = '';
  public $status;

  public function setAutoMatched($autoMatched)
  {
    $this->autoMatched = $autoMatched;
  }
  public function getAutoMatched()
  {
    return $this->autoMatched;
  }
  public function setAutoMatchedPlayer(Google_Service_Games_AnonymousPlayer $autoMatchedPlayer)
  {
    $this->autoMatchedPlayer = $autoMatchedPlayer;
  }
  public function getAutoMatchedPlayer()
  {
    return $this->autoMatchedPlayer;
  }
  public function setCapabilities($capabilities)
  {
    $this->capabilities = $capabilities;
  }
  public function getCapabilities()
  {
    return $this->capabilities;
  }
  public function setClientAddress(Google_Service_Games_RoomClientAddress $clientAddress)
  {
    $this->clientAddress = $clientAddress;
  }
  public function getClientAddress()
  {
    return $this->clientAddress;
  }
  public function setConnected($connected)
  {
    $this->connected = $connected;
  }
  public function getConnected()
  {
    return $this->connected;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLeaveReason($leaveReason)
  {
    $this->leaveReason = $leaveReason;
  }
  public function getLeaveReason()
  {
    return $this->leaveReason;
  }
  public function setPlayer(Google_Service_Games_Player $player)
  {
    $this->player = $player;
  }
  public function getPlayer()
  {
    return $this->player;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
}
