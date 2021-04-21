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

$pgtitle = array(gettext("Services"), gettext("DynFi Connection Agent"), gettext("Disconnect"));
$pglinks = array("", "dfconag_status.php", "@self");
include("head.inc");
?>

<div class="panel panel-default">
  <div class="panel-heading">
     <h2 class="panel-title"><?= gettext("Please confirm disconnecting this device from DynFi® Manager"); ?></h2>
  </div>
  <div class="panel-body" style="padding: 10px">
    <label class="chkboxlbl">
      <input type="checkbox" id="alsodelete" checked="checked" onchange="checkDelete(this)" /> <?= gettext("Also delete this device from DynFi® Manager"); ?>
    </label>
  </div>
</div>
<nav class="action-buttons">
  <a href="/dfconag_service.php?action=disconnectdelete" class="btn btn-warning" style="float: left" id="bconfirm">
    <?= gettext("Disconnect"); ?>
  </a>
  <a href="/dfconag_status.php" class="btn btn-primary">
    <?= gettext("Cancel"); ?>
  </a>
</nav>

<?php include("foot.inc"); ?>

<script type="text/javascript">
function checkDelete(el) {
  if ($(el).is(':checked')) {
    $('#bconfirm').attr('href', '/dfconag_service.php?action=disconnectdelete');
  } else {
    $('#bconfirm').attr('href', '/dfconag_service.php?action=disconnect');
  }
}
</script>

