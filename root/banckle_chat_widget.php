<?php

define('IN_PHPBB', true);
// Specify the path to you phpBB3 installation directory.
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : '';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
// The common.php file is required.
include_once($phpbb_root_path . 'common.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);

$user->setup();

global $db, $user, $auth, $template;

$sql = 'SELECT * FROM phpbb_config WHERE config_name = \'banckle_live_chat\'';
$result = $db->sql_query($sql);
$row = $db->sql_fetchrow($result);

if($row)
{
	$widget = '<!--Monitoring Code-->
<script type="text/javascript" async="async" src="https://apps.banckle.com/livechat/visitor.do?dep='.$row['config_value'].'"></script>

<!--Chat Link Code-->
<div style="overflow: hidden; margin: 0pt; padding: 0pt; background: none repeat scroll 0% 0% transparent; width: 264px; height: 70px; z-index: 1000000000; position: fixed; bottom: -3px; right: 20px;"><a href="javascript:;" onclick="blc_startChat()">
<img style=\'border:0px;\' id="blc_chatImg" src=\'https://apps.banckle.com/livechat/onlineImg.do?d='.$row['config_value'].'\'/>
</a></div>';
}
else {
	$widget = "";
}

echo $widget;

//your PHP and/or HTML code goes here

?>