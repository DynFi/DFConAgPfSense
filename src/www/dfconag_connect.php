<?php
/*
 * dfconag_connect.php
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

require_once("filter.inc");

require_once("/usr/local/pkg/dfconag/dfconag.inc");

global $g, $config;

if (!is_array($config['installedpackages']['dfconag'])) {
  if (function_exists('init_config_arr')) {
    init_config_arr(array('installedpackages', 'dfconag'));
  } else  {
    $config['installedpackages']['dfconag'] = array();
  }
}

$iflist = array_intersect_key(
  get_configured_interface_with_descr(),
  array_flip(
    array_keys(get_configured_interface_with_descr())
  )
);

$pconfig = array_merge(array(), $config['installedpackages']['dfconag']);
if ((!isset($pconfig['interfaces'])) || empty($pconfig['interfaces'])) {
  $pconfig['interfaces'] = array_keys($iflist);
} else {
  $pconfig['interfaces'] = explode(',', $pconfig['interfaces']);
}

if ($_POST) {
  unset($input_errors);

  if (isset($_POST['cancel'])) {
    header('Location: /dfconag_status.php');
    exit;
  }

  $input = array_map('trim_if_string', $_POST);
  $input['interfaces'] = $_POST['interfaces'];
  
  if (empty($input['dfmhost'])) {
    $input_errors[] = gettext("DynFi® Manager host is required");
  }
  
  if (empty($input['dfmsshport'])) {
    $input_errors[] = gettext("DynFi® Manager SSH port is required");
  } else if (!is_port($input['dfmsshport'])) {
    $input_errors[] = gettext("A valid DynFi® Manager SSH port must be specified");
  }
  
  if (empty($input['interfaces'])) {
    $input_errors[] = gettext("Select at least one interface");
  }

  if (!$input_errors) {
    $config['installedpackages']['dfconag']['dfmhost'] = $input['dfmhost'];
    $config['installedpackages']['dfconag']['dfmsshport'] = $input['dfmsshport']; 
    $config['installedpackages']['dfconag']['interfaces'] = implode(',', $input['interfaces']); 
    write_config("DynFi Connection Agent", false, true);

    $checkResult = dfconag_check_connection();
    if ($checkResult == 'OK') {
      $whoResult = dfconag_whoami();
      if ($whoResult) {
        set_flash_message('success', gettext("Successfully reconnected"));
        header('Location: /dfconag_status.php');
        exit;
      } else {        
        $res = dfconag_keyscan();   
        if ($res) {
        
          if (!empty($input['dfmtoken'])) {
            dfconag_log('using token: '.$input['dfmtoken']);
            $tokenData = dfconag_decode_token($input['dfmtoken']);
            if (($tokenData) && (isset($tokenData['key'])) && ($res['key'] == "[".$input['dfmhost']."]:".$input['dfmsshport']." ".trim($tokenData['key'], " \t\n\r"))) {
              $config['installedpackages']['dfconag']['knownhosts'] = $res['hashed'];
              $config['installedpackages']['dfconag']['knownhostsnothashed'] = $res['key'];
              write_config("DynFi Connection Agent", false, true);
              dfconag_store_knownhosts($res['key'], $res['hashed']);

              $addoptions = null;
              try {
                $res = dfconag_get_add_options('#token#', $input['dfmtoken']);
                $addoptions = json_decode($res, true);
              } catch (Exception $e) {
                $input_errors[] = $e->getMessage();
              }
              if ($addoptions) {
                if (function_exists('phpsession_begin'))
                  @phpsession_begin();
                else if (function_exists('session_start'))
                  @session_start();                
                $_SESSION['dfconag_username'] = '#token#';
                $_SESSION['dfconag_password'] = $input['dfmtoken'];
                $_SESSION['dfconag_addoptions'] = $addoptions;
                if (function_exists('phpsession_end'))
                  @phpsession_end(true);
                header('Location: /dfconag_register.php');
                exit;
              }
            } else {
              $input_errors[] = sprintf(gettext("Token keys mismatch (%s) vs (%s)"), $res['key'], "[".$input['dfmhost']."]:".$input['dfmsshport']." ".trim($tokenData['key'], " \t\n\r"));
            }
          } else {
        
            if ((!empty($config['installedpackages']['dfconag']['knownhostsnothashed'])) && ($config['installedpackages']['dfconag']['knownhostsnothashed'] == $res['key'])) {
              dfconag_store_knownhosts($res['key'], $res['hashed']);
              header('Location: /dfconag_dfm_login.php');
              exit;
            }        
            
            if (function_exists('phpsession_begin'))
              @phpsession_begin();
            else if (function_exists('session_start'))
              @session_start();
            $_SESSION['dfconag_keys'] = $res;
            if (function_exists('phpsession_end'))
              @phpsession_end(true);          
            header('Location: /dfconag_confirm_key.php');
            exit;
          }
        } else {
          $input_errors[] = gettext("Key scan failed");
        }
      }
    } else {
      $input_errors[] = sprintf(gettext("Connection Agent was unable to contact %s at port %s (%s)"), $input['dfmhost'], $input['dfmsshport'], $checkResult);
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

$section->addInput(new Form_Textarea(
  'dfmtoken',
  'Token',
  ''
))->setHelp('Connection token is a short text which allows smooth connection process between Connection Agent from this firewall and DynFi® Manager&reg;. To obtain a token, please ask your DynFi® Manager&reg; administrator. Tokens in DynFi® Manager can be generated in settings (top right ⚙️ → Connection Agent).');

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
  'dfmhost',
  'DynFi® Manager host',
  'text',
  htmlspecialchars($pconfig['dfmhost'])
));

$section->addInput(new Form_Input(
  'dfmsshport',
  'DynFi® Manager SSH port',
  'number',
  $pconfig['dfmsshport']
));

$section->addInput(new Form_Select(
  'interfaces',
  'Interface(s)',
  $pconfig['interfaces'],
  $iflist,
  true
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

<script type="text/javascript">

function decodeToken() {
    var host = '';
    var port = '';
    var token = $('#dfmtoken').val();
    if (!token.length) {
        $('#tokenmark').hide();        
        return;
    }
    var tArr = token.split('.');
    if (tArr.length == 3) {
        var base64 = tArr[1].replace(/-/g, '+').replace(/_/g, '/');
        try {
            var payloadJson = decodeURIComponent(atob(base64).split('').map(function(c) {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
            var payload = JSON.parse(payloadJson);
            host = payload.adr;
            port = payload.prt;
        } catch (e) {}
    }
    if ((host) && (port)) {
        $('#tokenmark').attr("class", "fa fa-check");
        $('#tokenmark').css("color", "#080");
    } else {
        $('#tokenmark').attr("class", "fa fa-times");
        $('#tokenmark').css("color", "#D00");
    }
    $('#tokenmark').show();
    $('#dfmhost').val(host);
    $('#dfmsshport').val(port);
}

$(document).ready(function ($) {
  $('#dfmhost').parent().parent().hide();
  $('#dfmsshport').parent().parent().hide();
  $('.form-group select').parent().parent().hide();
  $('#dfmtoken').parent().parent().find('span').append('&nbsp;<i id="tokenmark" style="display: none"></i>');
  $('#dfmtoken').change(decodeToken).keyup(decodeToken);
  $('#btnadvanced').click(function () {
    $('#btnadvanced').parent().parent().hide();
    $('#dfmhost').parent().parent().show();
    $('#dfmsshport').parent().parent().show();
    $('.form-group select').parent().parent().show();
  });
});
</script>
