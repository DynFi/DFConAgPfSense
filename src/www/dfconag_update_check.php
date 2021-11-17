<?php
/*
 * dfconag_update_check.php
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

require_once("/usr/local/pkg/dfconag/dfconag.inc");

$curr = trim(dfconag_get_current_version());
if (empty($curr))
    exit;

$versionsjson = file_get_contents("https://dynfi.com/versions.json");
if (empty($versionsjson))
    exit;
$versions = json_decode($versionsjson, true);
$latest = trim($versions['dfconag']['pfsense']);

if (version_compare($curr, $latest) < 0)
    echo $latest;
