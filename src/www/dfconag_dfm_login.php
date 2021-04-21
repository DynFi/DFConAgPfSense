<?php
/*
 * dfconag_dfm_login.php
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

if ($_POST) {
  unset($input_errors);
  
  if (isset($_POST['cancel'])) {
    header('Location: /dfconag_service.php?action=disconnect');
    exit;
  }   
  
  $input = array_map('trim', $_POST);
  
  if (empty($input['dfmusername'])) {
    $input_errors[] = gettext("DynFi® Manager username is required");
  }
  
  if (empty($input['dfmpassword'])) {
    $input_errors[] = gettext("DynFi® Manager password is required");
  }
    
  if (!$input_errors) {
    $obj = null;
    try {
      $res = dfconag_get_add_options($input['dfmusername'], $input['dfmpassword']);      
      $obj = json_decode($res, true);
    } catch (Exception $e) {
      if (strpos($e->getMessage(), '{') !== false) {
        $err = json_decode($e->getMessage(), true);
        $input_errors[] = sprintf(gettext("Registration failed (%s)"), (isset($err['userMessage'])) ? $err['userMessage'] : $err['errorCode']);    
      } else {
        $input_errors[] = $e->getMessage();
      }
    }    
    if ($obj) {
      if (function_exists('phpsession_begin'))
        @phpsession_begin();
      else if (function_exists('session_start'))
        @session_start();
      $_SESSION['dfconag_username'] = $input['dfmusername'];
      $_SESSION['dfconag_password'] = $input['dfmpassword'];
      $_SESSION['dfconag_addoptions'] = $obj;
      if (function_exists('phpsession_end'))
        @phpsession_end(true);
      header('Location: /dfconag_register.php');
    }
  }
}

$pgtitle = array(gettext("Services"), gettext("DynFi Connection Agent"), gettext("Connect"));
$pglinks = array("", "", "");
include("head.inc");

if ($input_errors)
    print_input_errors($input_errors);
?>

<div id="container">
<?php
$sb = new Form_Button(
  'submit',
  'Continue',
  null,
  'fa-check'
);
$sb->addClass('btn-primary');
$form = new Form($sb);
$section = new Form_Section('Connect to DynFi® Manager');

$section->addInput(new Form_Input(
  'dfmusername',
  'DynFi® Manager username',
  'text'
));

$section->addInput(new Form_Input(
  'dfmpassword',
  'DynFi® Manager password',
  'password'
));

$form->add($section);

$form->addGlobal(new Form_Button(
  'cancel',
  'Cancel',
  null,
  'fa-times'
))->addClass('btn-warning');
print $form;
?>
</div>

<?php include("foot.inc"); ?>
