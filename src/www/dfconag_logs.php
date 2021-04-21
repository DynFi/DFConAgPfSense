<?php
/*
 * dfconag_logs.php
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

$pgtitle = array(gettext("Services"), gettext("DynFi Connection Agent"), gettext("Log"));
$pglinks = array("", "dfconag_status.php", "@self");

include("head.inc");

$limit = 50;
if (isset($_GET['limit']) && (intval($_GET['limit']) > 50))
  $limit = intval($_GET['limit']);
$logentries = dfconag_get_logs($limit);

?>

<div class="panel panel-default">
  <div class="panel-heading">
    <h2 class="panel-title"><?= gettext("DynFi Connection Agent log"); ?></h2>
  </div>
  <div class="panel-body">
    <div class="table-responsive">
      <table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" data-sortable>
        <thead>
          <tr class="text-nowrap">
            <th><?=gettext("Time")?></th>
            <th style="width: 100%"><?=gettext("Message")?></th>
          </tr>
        </thead>
        <tbody>
<?php
foreach ($logentries as $item) {
?>
          <tr class="text-nowrap">
            <td>
              <?=htmlspecialchars($item['time'])?>
            </td>
            <td style="word-wrap:break-word; word-break:break-all; white-space:normal">
              <?=htmlspecialchars($item['message'])?>
            </td>
          </tr>
<?php
}
?>
        </tbody>
      </table>
<?php
if (empty($logentries)) {
  print_info_box(gettext('No logs to display.'));
}    
?>
    </div>
  </div>
</div>

<?php if ($limit <= count($logentries)) { ?>
<a class="btn btn-primary" href="/dfconag_logs.php?limit=<?= $limit + 50; ?>"><i class="fa fa-plus"></i> <?= gettext("Show older entries"); ?></a>
<?php } ?>

<?php include("foot.inc"); ?>
