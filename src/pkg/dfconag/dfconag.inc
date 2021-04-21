<?php
/*
 * dfconag.inc
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

require_once("pfsense-utils.inc");
require_once("config.inc");
require_once("globals.inc");
require_once("functions.inc");
require_once("services.inc");
require_once("service-utils.inc");
require_once("pkg-utils.inc");
require_once("filter.inc");
require_once("notices.inc");
require_once("util.inc");
require_once("priv.inc");
require_once("xmlrpc_client.inc");

global $g, $config;


function dfconag_log($message) {
  $message = date("Y/m/d H:i:s")." ".str_replace(array("\n", "\r"), "", $message);
  file_put_contents('/var/log/dfconag.log', $message.PHP_EOL , FILE_APPEND | LOCK_EX);
}


function dfconag_do_shell($cmd, $input = null) {  
  $descriptorspec = array(
    0 => array("pipe", "r"),
    1 => array("pipe", "w"),
    2 => array("pipe", "w"),
  );
  $pipes = array();
   
  $process = proc_open($cmd, $descriptorspec, $pipes);
  
  if ($input) {
    fwrite($pipes[0], $input);
    fclose($pipes[0]);
  }

  $result = stream_get_contents($pipes[1]);
  $error = stream_get_contents($pipes[2]);
  
  fclose($pipes[1]);
  fclose($pipes[2]);
  proc_close($process);
  
  return array(
    'output' => trim($result, " \t\n\r"),
    'error' => trim($error, " \t\n\r")
  );
}


function dfconag_pretest() {
  global $config;  
  if ((!isset($config['system']['enablesshd'])) && (!isset($config['system']['ssh']['enable']))) {
    return 'SSH_NOT_ENABLED';
  }
  if (empty(shell_exec('which autossh')))
    return 'AUTOSSH_MISSING';
  return 'OK';
}


function dfconag_check_status() {
  global $config;  
  $settings = array_merge(array(), $config['installedpackages']['dfconag']);
  
  if (!isset($settings['enable']))
    return null;  
  
  $settings['remotesshport'] = (!empty($config['system']['ssh']['port'])) ? $config['system']['ssh']['port'] : 22;
  $settings['remotedvport'] = (!empty($config['system']['webgui']['port'])) ? $config['system']['webgui']['port'] : ($config['system']['webgui']['protocol'] == 'https' ? 443 : 80);
  
  return $settings;
}


function dfconag_check_connection() {
  global $config;    
  $settings = array_merge(array(), $config['installedpackages']['dfconag']);
  
  $res = dfconag_do_shell('nc -zv -w 10 '.$settings['dfmhost'].' '.$settings['dfmsshport']);
  
  dfconag_log('conntest: '.print_r($res, true));
  
  $output = (!empty($res['output'])) ? $res['output'] : $res['error']; 
  if (strpos($output, 'succeeded') === false) {    
    return trim(str_replace('nc:', '', $output));
  }
  return 'OK';
}


function dfconag_restart() {
  dfconag_log("restarting agent");
  stop_service('dfconag'); 
  start_service('dfconag'); 
}


function dfconag_start() {
  dfconag_log("starting agent");
  start_service('dfconag'); 
}


function dfconag_stop() {
  dfconag_log("stopping agent");
  stop_service('dfconag'); 
}

function dfconag_whoami() {
  global $config;    
  $settings = array_merge(array(), $config['installedpackages']['dfconag']);  
  
  dfconag_check_privatekey();
  
  $res = dfconag_do_shell('ssh -o UserKnownHostsFile=/var/dfconag/known_hosts -i /var/dfconag/key -p '.$settings['dfmsshport'].' register@'.$settings['dfmhost'].' who-am-i');  
  dfconag_log("who-am-i: ".print_r($res, true));

  $res = json_decode($res['output'], true);
  
  if (isset($res['id'])) {
    $config['installedpackages']['dfconag']['enable'] = true;
    $config['installedpackages']['dfconag']['deviceid'] = $res['id'];
    write_config("DynFi Connection Agent", false, true);
    
    dfconag_log("reconnected to ".$res['id']);
    
    dfconag_write_rcfile();
    dfconag_start();
    
    return $res;
  }

  return null;
}


function dfconag_store_knownhosts($key, $hashedkey) {
  if (file_exists('/var/dfconag/known_hosts'))
    unlink('/var/dfconag/known_hosts');
  file_put_contents('/var/dfconag/known_hosts', "$key\n$hashedkey\n");  
}


function dfconag_keyscan() {
  global $config;    
  $settings = array_merge(array(), $config['installedpackages']['dfconag']);  

  $res = dfconag_do_shell('ssh-keyscan -p '.$settings['dfmsshport'].' '.$settings['dfmhost']);  
  $resh = dfconag_do_shell('ssh-keyscan -H -p '.$settings['dfmsshport'].' '.$settings['dfmhost']);

  if (!empty($res['error']))
    dfconag_log("ssh-keyscan: ".$res['error']);
  if (!empty($resh['error']))
    dfconag_log("ssh-keyscan-hashed: ".$resh['error']);      
  
  if ((empty($res['output'])) || (empty($resh['output'])))
    return null;

  return array(
    'key' => $res['output'],
    'hashed' => $resh['output']
  );
}


function dfconag_get_add_options($username, $password) {
  global $config;
  
  dfconag_check_privatekey();
  
  $settings = array_merge(array(), $config['installedpackages']['dfconag']); 
  
  $input = json_encode(
    ($username == '#token#') ? array('token' => $password) 
    : array('username' => $username, 'password' => $password)
  );
  $res = dfconag_do_shell('ssh -o UserKnownHostsFile=/var/dfconag/known_hosts -i /var/dfconag/key -p '.$settings['dfmsshport'].' robot@'.$settings['dfmhost'].' get-add-options', $input);  
  dfconag_log("get-add-options: ".print_r($res, true));

  if ((!empty($res['error'])) && (strpos($res['error'], 'Warning:') !== 0)) {
    throw new Exception($res['error']);
  }

  return $res['output'];
}


function dfconag_reserve_ports($username, $password, $mainTunnelPort, $dvTunnelPort) {
  global $config;
  
  dfconag_check_privatekey();
  
  $settings = array_merge(array(), $config['installedpackages']['dfconag']); 
  
  $input = json_encode(
    ($username == '#token#') ? array(
      'token' => $password,
      'mainTunnelPort' => $mainTunnelPort,
      'dvTunnelPort' => $dvTunnelPort
    )
    : array(
      'username' => $username,
      'password' => $password,
      'mainTunnelPort' => $mainTunnelPort,
      'dvTunnelPort' => $dvTunnelPort
    )
  );

  $res = dfconag_do_shell('ssh -o UserKnownHostsFile=/var/dfconag/known_hosts -i /var/dfconag/key -p '.$settings['dfmsshport'].' register@'.$settings['dfmhost'].' reserve-ports', $input);  
  dfconag_log("reserve-ports: ".print_r($res, true));

  if ((!empty($res['error'])) && (strpos($res['error'], 'Warning:') !== 0)) {
    throw new Exception($res['error']);
  }

  return json_decode($res['output'], true);
}


function dfconag_register($username, $password, $groupid, $sshuser, $secret) {
  global $config;
  
  dfconag_check_privatekey();
  
  $settings = array_merge(array(), $config['installedpackages']['dfconag']); 
  
  $publicKey = null;
  $authType = 'password';
  if (empty($secret)) {
    dfconag_log('generating key for auth');
    shell_exec("ssh-keygen -m PEM -q -t rsa -N '' -C \"dfconag@`hostname`\" -f /tmp/tmpkey");  
    if ((!file_exists('/tmp/tmpkey')) || (!file_exists('/tmp/tmpkey.pub'))) {
      throw new Exception("SSH keys generation failed");
    }
    $authType = 'key';
    $secret = file_get_contents('/tmp/tmpkey');
    $publicKey = file_get_contents('/tmp/tmpkey.pub');
  }

  if (!empty($publicKey)) {
    $config['installedpackages']['dfconag']['authorizeduser'] = $sshuser;
    $config['installedpackages']['dfconag']['authorizedkey'] = $publicKey;                
    write_config("DynFi Connection Agent", false, true);        
    dfconag_check_authorized_keys($sshuser, $publicKey);
  }

  $input = json_encode(
    ($username == '#token#') ? array(
      'token' => $password,
      'deviceGroup' => $groupid,
      'sshConfig' => array(
          'username' => $sshuser,
          'authType' => $authType,
          'secret' => $secret
      ),
      'osVersion' => file_get_contents('/etc/version'),
      'configBase64' => base64_encode(file_get_contents('/conf/config.xml'))
    )
    : array(
      'username' => $username,
      'password' => $password,
      'deviceGroup' => $groupid,
      'sshConfig' => array(
          'username' => $sshuser,
          'authType' => $authType,
          'secret' => $secret
      ),
      'osVersion' => file_get_contents('/etc/version'),
      'configBase64' => base64_encode(file_get_contents('/conf/config.xml'))
    )
  );

  $remotesshport = (!empty($config['system']['ssh']['port'])) ? $config['system']['ssh']['port'] : 22;
  $remotedvport = (!empty($config['system']['webgui']['port'])) ? $config['system']['webgui']['port'] : ($config['system']['webgui']['protocol'] == 'https' ? 443 : 80);
  
  $res = dfconag_do_shell('ssh -o UserKnownHostsFile=/var/dfconag/known_hosts -i /var/dfconag/key -p '.$settings['dfmsshport'].'  -R '.$settings['maintunnelport'].':localhost:'.$remotesshport.' -R '.$settings['dvtunnelport'].':localhost:'.$remotedvport.' attach@'.$settings['dfmhost'].' add-me', $input);  
  
  dfconag_log("add-me: ".print_r($res, true));  

  $obj = array();
  try {
    $obj = json_decode($res['output'], true);    
  } catch (Exception $e) {}

  if (!isset($obj['id'])) {
    if (isset($obj['errorCode']))
      throw new Exception($res['output']); 
    else if (!empty($res['error']))
      throw new Exception($res['error']);
    else
      throw new Exception($res['output']);
  }

  $config['installedpackages']['dfconag']['enable'] = true;   
  $config['installedpackages']['dfconag']['deviceid'] = $obj['id'];
  write_config();
  
  dfconag_write_rcfile();
  dfconag_start();
  
  return $obj;
}


function dfconag_delete_from_manager() {
  global $config; 
  $settings = array_merge(array(), $config['installedpackages']['dfconag']); 

  dfconag_check_privatekey();
  
  $input = json_encode(array(
    'deviceId' => $settings['deviceid']
  ));
  $res = dfconag_do_shell('ssh -o UserKnownHostsFile=/var/dfconag/known_hosts -i /var/dfconag/key -p '.$settings['dfmsshport'].' register@'.$settings['dfmhost'].' delete-me', $input);    
  dfconag_log("delete-me: ".print_r($res, true));  
        
  if (!empty($res['error']))
    throw new Exception($res['error']);
  
  return json_decode($res['output'], true);      
}


function dfconag_disconnect($delete = false) {
  global $config;
  
  if ($delete) {
    $ccResp = dfconag_check_connection();
    if ($ccResp == 'OK') {
      dfconag_delete_from_manager();
    } else {
      throw new Exception("Can not request deleting device, DynFi Manager is not accessible");      
    }
  }

  if (isset($config['installedpackages']['dfconag']['enable']))
    unset($config['installedpackages']['dfconag']['enable']);
  if (isset($config['installedpackages']['dfconag']['authorizeduser']))
    unset($config['installedpackages']['dfconag']['authorizeduser']);
  if (isset($config['installedpackages']['dfconag']['authorizedkey']))
    unset($config['installedpackages']['dfconag']['authorizedkey']);
  
  write_config("DynFi Connection Agent", false, true);
  
  dfconag_stop();
  dfconag_write_rcfile();
}


function dfconag_check_privatekey() {
  global $config;
  $settings = array_merge(array(), $config['installedpackages']['dfconag']); 
  
  if (file_exists('/var/dfconag/key')) {
    if ((empty($settings['sshprivatekey'])) || (empty($settings['sshpublickey']))) {
      dfconag_log("storing SSH keys in config");      
      $config['installedpackages']['dfconag']['sshprivatekey'] =  file_get_contents('/var/dfconag/key');
      $config['installedpackages']['dfconag']['sshpublickey'] =  file_get_contents('/var/dfconag/key.pub');
      write_config("DynFi Connection Agent", false, true);
    }
  } else {
    if ((empty($settings['sshprivatekey'])) || (empty($settings['sshpublickey']))) {
      dfconag_log("attempting to generate SSH keys");
      dfconag_generate_ssh_keys();
      if (!file_exists('/var/dfconag/key'))
          throw new Exception('SSH keys missing');
      dfconag_log("storing SSH keys in config");
      $config['installedpackages']['dfconag']['sshprivatekey'] =  file_get_contents('/var/dfconag/key');
      $config['installedpackages']['dfconag']['sshpublickey'] =  file_get_contents('/var/dfconag/key.pub');
      write_config("DynFi Connection Agent", false, true);
    } else {
      dfconag_log("generating SSH key files");  
      file_put_contents('/var/dfconag/key', $settings['sshprivatekey']);
      file_put_contents('/var/dfconag/key.pub', $settings['sshpublickey']);
      chmod('/var/dfconag/key', 0600);
    }
  }
}


function dfconag_generate_ssh_keys() {
  shell_exec('mkdir -p /var/dfconag');
  $res = dfconag_do_shell('ssh-keygen -q -t ed25519 -N \'\' -f /var/dfconag/key');
  dfconag_log("ssh-keygen: ".print_r($res, true));
  if (!empty($res['error']))
    throw new Exception($res['error']);
}


function dfconag_reset() {
  global $config;
  
  dfconag_log("clearing agent configuration");
  
  if (isset($config['installedpackages']['dfconag']['enable']))
    unset($config['installedpackages']['dfconag']['enable']);
  if (isset($config['installedpackages']['dfconag']['dfmhost']))
    unset($config['installedpackages']['dfconag']['dfmhost']);
  if (isset($config['installedpackages']['dfconag']['dfmsshport']))
    unset($config['installedpackages']['dfconag']['dfmsshport']);
  if (isset($config['installedpackages']['dfconag']['knownhosts']))
    unset($config['installedpackages']['dfconag']['knownhosts']);
  if (isset($config['installedpackages']['dfconag']['knownhostsnothashed']))
    unset($config['installedpackages']['dfconag']['knownhostsnothashed']);
  if (isset($config['installedpackages']['dfconag']['authorizeduser']))
    unset($config['installedpackages']['dfconag']['authorizeduser']);
  if (isset($config['installedpackages']['dfconag']['authorizedkey']))    
    unset($config['installedpackages']['dfconag']['authorizedkey']);
  if (isset($config['installedpackages']['dfconag']['maintunnelport']))
    unset($config['installedpackages']['dfconag']['maintunnelport']);
  if (isset($config['installedpackages']['dfconag']['dvtunnelport']))
    unset($config['installedpackages']['dfconag']['dvtunnelport']);
  if (isset($config['installedpackages']['dfconag']['deviceid']))
    unset($config['installedpackages']['dfconag']['deviceid']);
  if (isset($config['installedpackages']['dfconag']['sshprivatekey']))
    unset($config['installedpackages']['dfconag']['sshprivatekey']);
  if (isset($config['installedpackages']['dfconag']['sshpublickey']))
    unset($config['installedpackages']['dfconag']['sshpublickey']);
  
  write_config("DynFi Connection Agent", false, true);

  if (file_exists('/var/dfconag/known_hosts'))
      unlink('/var/dfconag/known_hosts');
  if (file_exists('/var/dfconag/key'))
      unlink('/var/dfconag/key');
  if (file_exists('/var/dfconag/key.pub'))
      unlink('/var/dfconag/key.pub');
  
  dfconag_stop();
  dfconag_write_rcfile();
}


function dfconag_decode_token($jwt) {
  $payload = null;  
  $arr = explode('.', $jwt);
  if (count($arr) != 3)
    return null;
  $dataEnc = $arr[1];
  $r = strlen($dataEnc) % 4;
  if ($r)
    $dataEnc .= str_repeat('=', (4 - $r));
  $data = base64_decode(strtr($dataEnc, '-_', '+/'));
  return json_decode($data, true);      
}


function dfconag_write_rcfile() {
  global $config;
  $settings = array_merge(array(), $config['installedpackages']['dfconag']);       
      
  $start = "echo \"DFConAg not enabled\"";
  $stop = "echo \"DFConAg not running\"";
  
  if (isset($settings['enable'])) {  
    extract($settings);
    $remotesshport = (!empty($config['system']['ssh']['port'])) ? $config['system']['ssh']['port'] : 22;
    $remotedvport = (!empty($config['system']['webgui']['port'])) ? $config['system']['webgui']['port'] : ($config['system']['webgui']['protocol'] == 'https' ? 443 : 80);
  
    dfconag_log("generating rcfile for [-p $dfmsshport -R $maintunnelport:localhost:$remotesshport -R $dvtunnelport:localhost:$remotedvport robot@$dfmhost]");
  
    $start = "
if ! pgrep -qF /var/run/dfconag.pid 2> /dev/null; then
  echo \"Starting DFConAg autossh to $$dfmhost\"
  env AUTOSSH_GATETIME=0 env AUTOSSH_PIDFILE=/var/run/dfconag.pid env AUTOSSH_LOGFILE=/var/log/dfconag.log env AUTOSSH_LOGLEVEL=6 env AUTOSSH_PATH=/usr/bin/ssh /usr/local/bin/autossh -M 0 -f -N -oUserKnownHostsFile=/var/dfconag/known_hosts -oServerAliveInterval=30 -oServerAliveCountMax=3 -i /var/dfconag/key -p $dfmsshport -R $maintunnelport:localhost:$remotesshport -R $dvtunnelport:localhost:$remotedvport robot@$dfmhost
else
  echo \"DFConAg already running\"
fi
";

    $stop = "
if [ -f /var/run/dfconag.pid ]; then
  echo \"Stopping DFConAg autossh\"
  /bin/pkill -TERM -F /var/run/dfconag.pid
  if [ -f /var/run/dfconag.pid ]; then
    rm /var/run/dfconag.pid
  fi  
else
  echo \"DFConAg not running\"
fi
";
  } else {
   dfconag_log("generating rcfile for a disabled service");
  }

  write_rcfile(array(
    "file" => "dfconag.sh",
    "start" => $start,
    "stop" => $stop
  ));
}


function dfconag_check_authorized_keys($username, $key) {
  global $config;
  if ($username == 'root')
    $username = 'admin';
  dfconag_log("adding authorized key for $username [$key]");
  foreach ($config['system']['user'] as &$user) {
    if ($user['name'] == $username) {
      $keys = (isset($user['authorizedkeys'])) ? base64_decode($user['authorizedkeys']) : '';
      if (strpos($keys, $key) === false) {
        $olines = explode("\n", $keys);
        $nlines = array();
        foreach ($olines as $line) {
          if (strpos($line, "dfconag@") === false) {
            $nlines[] = trim($line);
          }
        }
        $nlines[] = trim($key);
        $user['authorizedkeys'] = base64_encode(implode("\r\n", $nlines));
        local_user_set($user);
        write_config("DynFi Connection Agent", false, true);
      }
      break;
    }
  }
}


function dfconag_get_logs($limit) {
  exec("/usr/bin/tail -r -n $limit /var/log/dfconag.log", $logarr);
  $date_pattern = '/\d{4}\/\d{1,2}\/\d{1,2} \d{1,2}:\d{1,2}:\d{1,2}/';
  $logentries = array();  
  foreach ($logarr as $l) {
    preg_match($date_pattern, $l, $date_matches);
    if (!empty($date_matches)) {
      $dt = $date_matches[0];
      $logentries[] = array(
        'time' => $dt,
        'message' => trim(str_replace($dt, '', $l))
      );      
    }
  }
  return $logentries;
}


function dfconag_generate_rules($type) {
  global $config;
  
  if ($type != 'filter')
    return "";

  $settings = array_merge(array(), $config['installedpackages']['dfconag']);       
  if (!isset($settings['enable']))
    return "";
  
  extract($settings);

  $rules = "# allow DynFi Connection Agent traffic\n";

  $ifaces = array();
  if (function_exists('get_interface_ports'))
    $ifaces = get_interface_ports();
  else if (function_exists('get_interfaces_with_gateway'))
    $ifaces = get_interfaces_with_gateway();
    
  foreach ($ifaces as $ifent => $ifdesc) {    
    dfconag_log("generating rule for $ifent $dfmhost:$maintunnelport");    
    $rules .= "pass  out  quick  on $ifent inet proto tcp  from any to $dfmhost port $maintunnelport flags S/SA keep state  label \"DynFi Connection Agent main tunnel\"\n";
    dfconag_log("generating rule for $ifent $dfmhost:$dvtunnelport");    
    $rules .= "pass  out  quick  on $ifent inet proto tcp  from any to $dfmhost port $dvtunnelport flags S/SA keep state  label \"DynFi Connection Agent DirectView tunnel\"\n";
  }
  
  dfconag_config_resync();
  
  return $rules;
}


function dfconag_config_resync() {
  global $config;  
  $settings = array_merge(array(), $config['installedpackages']['dfconag']);
  
  dfconag_log("config resync");    
  
  if (dfconag_pretest() != 'OK')
    return;
  
  dfconag_check_privatekey();
  dfconag_write_rcfile();
  
  if (isset($settings['enable'])) {
    dfconag_restart();
  }
}


function dfconag_install_autossh() {
  shell_exec('/usr/local/bin/install_autossh');
}
