<?php
$debug = false;
date_default_timezone_set('Asia/Kuala_Lumpur');
ini_set("max_execution_time", "0");
ini_set("max_input_time", "0");
set_time_limit(0);
if(isset($argv[1]) and $argv[1] == '-debug') {
	error_reporting(E_ALL);
	$debug = true;
}
require('./helper.php');
require('./mybb.class.php');
require('./tbdshout.class.php');

$username = 'test';
$password = 'test';
$admin = 'Ahlspiess';
$host = 'http://w3.tbd.my/';

$mybb = new MyBB($host);
$sb = new TBDShoutBox($host);
$mybb->Login($username, $password);

$post_key	=	$mybb->post_key;
$xpm_key	=	$mybb->xpm_key;
$xpm_user	=	$mybb->xpm_user;

if(empty($post_key) OR !$post_key) {
	e("[+] Invalid Login");
	exit;
}
e("[+] Successfuly Login");
e("[+] Post Key: {$post_key}");
if($debug) {
	e(sprintf("[+] XPM Key: %s XPM User: %s", $xpm_key, $xpm_user));
}

static $pmsg = array();
$banned = array();
$lastseen = array();
while(true) {
	$data = $sb->FetchChat_ws($xpm_user, $xpm_key);
	if(isset($data['msg']) and !empty($data['msg'])) {
		if(!in_array(strtolower($data['user']), $banned)) {
			if(!isset($pmsg[$data['shout_id']])) {
				switch(strtolower($data['msg'])) {						
					case (stripos($data['msg'], '@seen') === 0):
					case (stripos($data['msg'], '@seen:') === 0):
					case (stripos($data['msg'], '@seen,') === 0):
						$msg = str_ireplace("@seen:", "", $data['msg']);
						$msg = str_ireplace("@seen,", "", $msg);
						$msg = str_ireplace("@seen", "", $msg);
						$msg = ltrim($msg, ':');
						$msg = ltrim($msg, ',');
						$msg = ltrim($msg, ' ');
						$nick = strtolower($msg);
						if($nick == $username) {
							$sb->SendShout("@{$data['user']}, itu diri saya sendiri la :fp2: :fp2: ~zzz ", $post_key);
						} elseif(isset($lastseen[$nick])) {
							$sb->SendShout("Kali terakhir saya nampak [b]{$msg}[/b] pada " .date('F j, Y, g:i a', $lastseen[$nick]) . ' ~^^', $post_key);
						} else {
							$sb->SendShout("Maaf, saya tak pernah nampak user tersebut disini. ~^^", $post_key);
						}
						break;
						
					case (stripos($data['msg'], '@ban') === 0):
						if($data['user'] == $admin) {
							$ban = str_ireplace('@ban', '', $data['msg']);
							$ban = ltrim($ban);
							$banned[] = strtolower($ban);
						}
					break;
					
					case (stripos($data['msg'], '@unban') === 0):
						if($data['user'] == $admin) {
							$ban = str_ireplace('@unban', '', $data['msg']);
							$ban = ltrim($ban);
							$key = array_search($ban, $banned);
							if($key !== false) {
								unset($banned[$key]);
							}
						}
					break;
						
					default: break;
				}
				e(vsprintf("%s => %s", $data));
				$pmsg[$data['shout_id']] = true;
			}
		}
	}
	$lastseen[strtolower($data['user'])] = time();
}
?>
