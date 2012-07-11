<?php
class acp_banckle_chat
{
   var $u_action;
   var $new_config;
   function main($id, $mode)
   {
      global $db, $user, $auth, $template;
      global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;
      switch($mode)
      {
         case 'index':		 	
            $this->page_title = 'ACP_BANCKLE_CHAT';
            $this->tpl_name = 'acp_banckle_chat';						
			
			if(isset($_REQUEST['panel']))
			{
				$panel = $_REQUEST['panel'];
			}
			else
			{
				$panel = 'default';
			}
			
			$sql = 'SELECT * FROM phpbb_config WHERE config_name = \'banckle_live_chat\'';
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			
			$data = array();
			
			if($row)
			{
				$data['is_active'] = 1;
			}
			else
			{
				$data['is_active'] = 0;
			}
						
			
			if(isset($_POST['user']) && !empty($_POST['user']))
			{
				$loginId = $_POST['user'];
				$password = $_POST['password'];
				$content = $this->banckleLiveChatRequest('https://apps.banckle.com/api/authenticate?userid=' . $loginId . '&password=' . $password . '&sourceSite=' . $_SERVER["SERVER_NAME"] . '&platform=cmsms', "GET", "JSON", "");
				
				if ($content !== false && !empty($content))
				{					
					$response = json_decode($content,true);
					
					if (array_key_exists('error', $response))
					{
						$data['error'] = $response['error']['details'];
					}
					else
					{
						$token = $response['return']['token'];
						$panel = 'deployments';
						
						$xmlDeploy = $this->banckleLiveChatRequest('https://apps.banckle.com/em/api/deployments.xml?_token=' . $token, "GET", "XML", "");						
							
						$xmlDeploy = new SimpleXMLElement(utf8_encode($xmlDeploy));
												
						
						if (count($xmlDeploy->deployment) > 0)
						{
							foreach($xmlDeploy->deployment as $deploy)
							{
								$count = count($data['deployments']);
								$data['deployments'][$count]['name'] = (string)$deploy->name;
								$data['deployments'][$count]['id'] = (string)$deploy->id;
							}
							
																				
							foreach($data['deployments'] as $deployment)
							{
							   $template->assign_block_vars('deployments', array(
								  'NAME' => $deployment['name'],
								  'ID' => $deployment['id'],
							   ));
							}
							
							
							
						}
						else
						{
							$data['error'] = "Sorry! No Deployment is Available";
						}
						
					}
					
				}
				else
				{
					$data['error'] = "<h2>Oops! Something is wrong. Please try again.</h2>";
				}
								
				
			}			
			
			
			if(isset($_POST['activate']))
			{
				$deployment_id = $_POST['deployId'];
				
				$sql = 'INSERT INTO phpbb_config SET config_name = \'banckle_live_chat\', config_value = \''.$deployment_id.'\'';
				$result = $db->sql_query($sql);
				
				$contents = file_get_contents('../index.php');			
				$pos = strpos($contents,"page_footer();");
				$final_str = substr($contents,0,$pos-1) . "include_once('banckle_chat_widget.php');" . substr($contents,$pos,100);
				
				file_put_contents('../index.php',$final_str);
				$panel = "default";
				$data['is_active'] = 1;
			}
			
			if(isset($_POST['deactivate']))
			{								
				$sql = 'DELETE FROM phpbb_config WHERE config_name = \'banckle_live_chat\'';
				$result = $db->sql_query($sql);
				
				$contents = file_get_contents('../index.php');			
				
				$final_str =  str_replace("include_once('banckle_chat_widget.php');","",$contents);
				
				file_put_contents('../index.php',$final_str);
				
				$panel = "default";
				$data['is_active'] = 0;
			}
			

			
			//$template->assign_block_vars('data',$data);			
			
			
			$template->assign_vars(array(
    			'CURRENT_URL'    => $this->getCurrentPageUrl(),
				'DASHBOARD_URL'	 => $this->getCurrentPageUrl(array('panel'=>'')),
				'IS_ACTIVE'			 => $data['is_active'],
				'PANEL'			=> $panel,
				'TOKEN'			=> $token,
				'ERROR'			=> $data['error']
			));				
			
            break;		 
      }
   }
   
	function getCurrentPageUrl(array $newparams = array(), $remove_others=false, array $remove_exceptions=array())
	{
		$pageURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
		if ($_SERVER["SERVER_PORT"] != "80")
		{
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} 
		else 
		{
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		
		$url_arr = parse_url($pageURL);
		$pageURL = $url_arr['scheme'] . "://" . $url_arr['host'] . $url_arr['path'];
		
		if(count($_SERVER['QUERY_STRING']) > 0 || count($newparams) > 0)
		{
			$pageURL .= "?";
		}
		
		if($remove_others == false)
		{
			if(count($_SERVER['QUERY_STRING']) > 0)
			{
				parse_str($_SERVER['QUERY_STRING'],$params);
			}
		}
		else
		{
			$param = array();
			
			if(count($remove_exceptions) > 0)
			{
				if(count($_SERVER['QUERY_STRING']) > 0)
				{
					parse_str($_SERVER['QUERY_STRING'],$params);
					
					foreach($params as $key => $param)
					{
						if(!in_array($key,$remove_exceptions))
						{
							unset($params[$key]);
						}
					}			
				}			
			}		
		}
		
		$params = array_merge($params,$newparams);
		
		foreach($params as $key => $param){
			if(empty($param) && $param != 0) unset($params[$key]);
		}
				
		
		$pageURL .= http_build_query($params,'','&');
		
		return $pageURL;
	}
	
	function banckleLiveChatRequest($url, $method="GET", $headerType="XML", $xmlsrc="")
	{
		$method = strtoupper($method);
		$headerType = strtoupper($headerType);
		$session = curl_init();
		curl_setopt($session, CURLOPT_URL, $url);
		if ($method == "GET") {
		  curl_setopt($session, CURLOPT_HTTPGET, 1);
		} else {
		  curl_setopt($session, CURLOPT_POST, 1);
		  curl_setopt($session, CURLOPT_POSTFIELDS, $xmlsrc);
		  curl_setopt($session, CURLOPT_CUSTOMREQUEST, $method);
		}
		curl_setopt($session, CURLOPT_HEADER, false);
		if ($headerType == "XML") {
		  curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/xml', 'Content-Type: application/xml'));
		} else {
		  curl_setopt($session, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
		}
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		if (preg_match("/^(https)/i", $url))
		  curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($session);
		curl_close($session);
		return $result;
	}
   
}
?>