<?php
/*
 * dfconag_status.php
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

global $shortcuts;

$pgtitle = array(gettext("Services"), gettext("DynFi Connection Agent"), gettext("Status"));
$pglinks = array("", "self", "@self");
$shortcut_section = 'dfconag';

include("head.inc");

require_once("/usr/local/pkg/dfconag/dfconag.inc");

?>
<div id="container">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h2 class="panel-title"><?= gettext("DynFi Connection Agent"); ?></h2>
    </div>
    <div class="panel-body table-responsive">
    <table class="table table-striped table-condensed" id="statustable">
      <tbody>
        <tr>
          <td colspan="2">
            <strong><?= gettext("DynFi Connection Agent Status"); ?></strong>
          </td>
        </tr>
<?php

$pretestResult = dfconag_pretest();
$status = null;

if ($pretestResult == 'SSH_NOT_ENABLED') {
  echo '<tr class="dfcinf"><td colspan="2">'.gettext("DynFi Connection Agent requires SSH enabled.").'</td></tr>';
} else if ($pretestResult == 'AUTOSSH_MISSING') {
  echo '<tr class="dfcinf"><td colspan="2">'.gettext("Can not use DynFi Connection Agent: autossh command not found.").'<br /><br /><a class="btn btn-primary" href="/dfconag_service.php?action=autossh">'.gettext("Click here to install autossh").'</a></td></tr>';
} else {
  $status = dfconag_check_status();
  if ($status) {
    echo '<tr class="dfcinf"><td>'.gettext("Connected to").'</td><td>'.$status['dfmhost'].':'.$status['dfmsshport'].'</td></tr>';
    echo '<tr class="dfcinf"><td>'.gettext("Device ID").'</td><td>'.$status['deviceid'].'</td></tr>';
    echo '<tr class="dfcinf"><td>'.gettext("Main tunnel").'</td><td>'.$status['maintunnelport'].' &rarr; '.$status['remotesshport'].'</td></tr>';
    echo '<tr class="dfcinf"><td>'.gettext("DirectView tunnel").'</td><td>'.$status['dvtunnelport'].' &rarr; '.$status['remotedvport'].'</td></tr>';
    echo '<tr class="dfcinf"><td>'.gettext("Interfaces").'</td><td>'.$status['interfaces'].'</td></tr>';
  } else {
    echo '<tr class="dfcinf"><td colspan="2">'
      .str_replace('DynFi® Manager', '<a href="https://dynfi.com/download">DynFi® Manager</a>', gettext("The DynFi Connection Agent simplifies the administration of your pfSense-CE firewalls and requires the use of DynFi® Manager."))
      .'<br />'
      .gettext("This device is not connected to any DynFi® Manager.")
      .'</td></tr>';
  }
}

?>
        </tbody>
      </table>    
    </div>
  </div>
<?php if ($pretestResult == 'OK') { ?>  
  <nav class="action-buttons">
<?php 
if ($status) {
?>
  <a href="/dfconag_disconnect.php" class="btn btn-warning">
    <?= gettext("Disconnect"); ?>
  </a>
<?php } else { ?>
  <a href="/dfconag_connect.php" class="btn btn-success" style="float: left">
    <?= gettext("Connect"); ?>
  </a>
  <a href="/dfconag_service.php?action=reset" class="btn btn-danger">
    <?= gettext("Reset"); ?>
  </a>
<?php } ?>
  </nav>
<?php } ?>
</div>

<?php include("foot.inc"); ?>

<script type="text/javascript">
$(document).ready(function ($) {
  $.get('/dfconag_update_check.php', function (response) {    
    if (response.length) {
      var msg = '<?php echo gettext("Version %s of DynFi Connection Agent is available."); ?>'.replace('%s', response)
          + ' <?php echo gettext("Visit %s for installation instructions."); ?>'.replace('%s', '<a href="https://dynfi.com/connection-agent">https://dynfi.com/connection-agent</a>');
      $('#container').prepend('<div class="alert alert-info">' + msg + '</div>');
    }
  });
});
</script>
