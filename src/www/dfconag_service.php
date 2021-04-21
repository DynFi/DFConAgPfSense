<?php
/*
 * dfconag_service.php
 *
 * Copyright (c) 2020 DynFi
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require("guiconfig.inc");
require_once("/usr/local/pkg/dfconag/dfconag.inc");

try {
  switch ($_GET['action']) {
    case 'disconnectdelete':
      dfconag_disconnect(true);
      set_flash_message('info', gettext("Disconnected and deleted this device from DynFi® Manager"));      
      break;
    case 'disconnect':
      dfconag_disconnect();
      set_flash_message('info', gettext("Disconnected from DynFi® Manager"));
      break;
    case 'reset':
      dfconag_reset();
      set_flash_message('info', gettext("DynFi Connection Agent configuration cleared"));
      break;
    case 'autossh':
      dfconag_install_autossh();
      set_flash_message('info', gettext("Autossh installed"));
      break;
    default: break;  
  }
} catch (Exception $e) {
  $message = $e->getMessage();
  if (strpos($e->getMessage(), '{') !== false) {
    $err = json_decode($e->getMessage(), true);
    $message = sprintf(gettext("Tunnel ports reservation failed (%s)"), (isset($err['userMessage'])) ? $err['userMessage'] : $err['errorCode']);        
  }
  set_flash_message('danger', $message);      
}

header('Location: /dfconag_status.php');
