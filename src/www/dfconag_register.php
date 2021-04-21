<?php
/*
 * dfconag_register.php
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

if ((!isset($_SESSION['dfconag_addoptions'])) || (!isset($_SESSION['dfconag_username'])) || (!isset($_SESSION['dfconag_password'])))
  header('Location: /dfconag_status.php');

$options = $_SESSION['dfconag_addoptions'];
$username = $_SESSION['dfconag_username'];
$password = $_SESSION['dfconag_password'];

if ($_POST) {
  unset($input_errors);
  
  if (isset($_POST['cancel'])) {
    header('Location: /dfconag_service.php?action=disconnect');
    exit;
  }   

  $input = array_map('trim', $_POST);  

  if (!$input_errors) {
  
    $mainTunnelPort = intval($options['nextTunnelPort']);
    $dvTunnelPort = $mainTunnelPort + 1;

    if ((intval($input['maintunnelport'])) && (intval($input['dvtunnelport']))) {
      $mainTunnelPort = intval($input['maintunnelport']);
      $dvTunnelPort = intval($input['dvtunnelport']);
    }

    try {
      $portsResp = dfconag_reserve_ports($username, $password, $mainTunnelPort, $dvTunnelPort);
    } catch (Exception $e) {
      if (strpos($e->getMessage(), '{') !== false) {
        $err = json_decode($e->getMessage(), true);
        $input_errors[] = sprintf(gettext("Tunnel ports reservation failed (%s)"), (isset($err['userMessage'])) ? $err['userMessage'] : $err['errorCode']);    
      } else {
        $input_errors[] = $e->getMessage();
      }
    }
  
    if (!$input_errors) {
    
      if (isset($portsResp['errorCode'])) {  
        $input_errors[] = sprintf(gettext("Tunnel ports reservation failed (%s)"), (isset($portsResp['userMessage'])) ? $portsResp['userMessage'] : $portsResp['errorCode']);            
      } else {
      
        $config['installedpackages']['dfconag']['maintunnelport'] = $mainTunnelPort;
        $config['installedpackages']['dfconag']['dvtunnelport'] = $dvTunnelPort;        
        write_config("DynFi Connection Agent", false, true);
      
        try {
          $addResp = dfconag_register($username, $password, $input['groupid'], $input['username'], $input['password']);
        } catch (Exception $e) {
          if (strpos($e->getMessage(), '{') !== false) {
            $err = json_decode($e->getMessage(), true);
            $input_errors[] = sprintf(gettext("Registration failed (%s)"), (isset($err['userMessage'])) ? $err['userMessage'] : $e->getMessage());    
          } else {
            $input_errors[] = $e->getMessage();
          }
        }
              
        if (!$input_errors) {        
        
          if (isset($addResp['errorCode'])) {  
            $input_errors[] = sprintf(gettext("Registration failed (%s)"), (isset($addResp['userMessage'])) ? $addResp['userMessage'] : $addResp['errorCode']);            
          } else if (!isset($addResp['id'])) {            
            $input_errors[] = sprintf(gettext("Registration failed (%s)"), print_r($addResp, true));
          } else {

            set_flash_message('success', gettext("Successfully connected to DynFi® Manager"));
            
            if (function_exists('phpsession_begin'))
              @phpsession_begin();
            else if (function_exists('session_start'))
              @session_start();
            unset($_SESSION['dfconag_addoptions']);
            unset($_SESSION['dfconag_username']);
            unset($_SESSION['dfconag_password']);
            if (function_exists('phpsession_end'))
              @phpsession_end(true);            
            
            header('Location: /dfconag_status.php');
            exit;
          }
        }
      }
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
  'Register this device to DynFi® Manager',
  null,
  'fa-check'
);
$sb->addClass('btn-success');
$form = new Form($sb);
$section = new Form_Section('Connect to DynFi® Manager');

$deviceGroups = array();
foreach ($options['availableDeviceGroups'] as $d) {
  $deviceGroups[$d['id']] = $d['name'];
}

$usernames = array();
foreach ($config['system']['user'] as $u) {
  $g = local_user_get_groups($u);
  if (in_array('admins', $g)) {
    $un = ($u['name'] == 'admin') ? 'root' : $u['name'];
    $usernames[$un] = $un;
  }
}

$section->addInput(new Form_Select(
  'groupid',
  'Device group in Manager',
  null,
  $deviceGroups
))->setHelp('This device will be added to the selected device group in DynFi® Manager.');

$section->addInput(new Form_Select(
  'username',
  'Account used by Manager',
  null,
  $usernames
))->setHelp('DynFi® Manager will connect to this device using the selected account of this device.');

$group = new Form_Group('Authentication mechanism');
$group->add(new Form_Checkbox(
  'authm',
  'Authentication mechanism',
  'new SSH key pair',
  true,
  'key'
))->displayAsRadio()->setHelp('DynFi® Manager will connect to this device using a newly generated SSH key pair');
$group->add(new Form_Checkbox(
  'authm',
  'Authentication mechanism',
  'SSH password',
  null,
  'pass'
))->displayAsRadio()->setHelp('DynFi manager will connect to this device using account\'s password');
$section->add($group);

$section->addInput(new Form_Input(
  'password',
  'SSH password',
  'password'
));

if ($username != '#token#') {

  $btnadv = new Form_Button(
    'btnadvanced',
    'Advanced options',
    null,
    'fa-cog'
  );

  $btnadv->setAttribute('type', 'button')->addClass('btn-info btn-sm');

  $section->addInput(new Form_StaticText(
    'Show advanced options',
    $btnadv
  ));
  
  $section->addInput(new Form_Input(
    'maintunnelport',
    'Main tunnel port',
    'number',
    intval($options['nextTunnelPort'])
  ));
  
  $section->addInput(new Form_Input(
    'dvtunnelport',
    'DirectView tunnel port',
    'number',
   intval($options['nextTunnelPort']) + 1
  ));

}


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

<script type="text/javascript">

function checkAuth() {
  var v = $('input[name="authm"]:checked').val();
  if (v == 'key') {
    $('#password').val('');
    $('#password').parent().parent().hide();
  } else {
    $('#password').parent().parent().show();
  }
}

$(document).ready(function ($) {
  $('#maintunnelport').parent().parent().hide();
  $('#dvtunnelport').parent().parent().hide();
  
  $('#btnadvanced').click(function () {
    $('#btnadvanced').parent().parent().hide();
    $('#maintunnelport').parent().parent().show();
    $('#dvtunnelport').parent().parent().show();
  });

  $('input[name="authm"]').change(checkAuth);
  checkAuth();
});

</script>
