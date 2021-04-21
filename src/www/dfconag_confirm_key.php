<?php
/*
 * dfconag_confirm_key.php
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

global $g, $config;

if (!isset($_SESSION['dfconag_keys']))
  header('Location: /dfconag_status.php');

$keys = $_SESSION['dfconag_keys'];

if (isset($_GET['confirm'])) {
  if (function_exists('phpsession_begin'))  
    @phpsession_begin();
  else if (function_exists('session_start'))
    @session_start();
  unset($_SESSION['dfconag_keys']);
  if (function_exists('phpsession_end'))
    @phpsession_end(true);  
  if ($_GET['confirm'] == 'yes') {
    $config['installedpackages']['dfconag']['knownhosts'] = $keys['hashed'];
    $config['installedpackages']['dfconag']['knownhostsnothashed'] = $keys['key'];
    write_config("DynFi Connection Agent", false, true);
    dfconag_store_knownhosts($keys['key'], $keys['hashed']);
    header('Location: /dfconag_dfm_login.php');
    exit;
  } else {
    set_flash_message('info', gettext("SSH key rejected"));
    header('Location: /dfconag_status.php');
    exit;
  }
}

$pgtitle = array(gettext("Services"), gettext("DynFi Connection Agent"), gettext("SSH key confirmation"));
$pglinks = array("", "", "");
include("head.inc");
?>

<div class="panel panel-default">
  <div class="panel-heading">
     <h2 class="panel-title"><?= gettext("Please confirm DynFi® Manager SSH key"); ?></h2>
  </div>
  <div class="panel-body table-responsive" style="padding: 10px">
    <pre><?php echo $keys['key'] ?></pre>
  </div>
</div>
<nav class="action-buttons">
  <a href="/dfconag_confirm_key.php?confirm=yes" class="btn btn-success" style="float: left">
    <?= gettext("Confirm and continue"); ?>
  </a>
  <a href="/dfconag_confirm_key.php?confirm=no" class="btn btn-warning">
    <?= gettext("Reject"); ?>
  </a>
</nav>

<?php include("foot.inc"); ?>
