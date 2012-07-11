<?php
class acp_banckle_chat_info
{
    function module()
    {
        return array(
            'filename'    => 'acp_banckle_chat',
            'title'        => 'ACP_BANCKLE_CHAT',
            'version'    => '1.0.0',
            'modes'        => array(
                'index'        => array('title' => 'ACP_BC_INDEX_TITLE', 'auth' => 'acl_a_', 'cat' => array('BanckleChat')),
            ),
        );
    }

    function install()
    {
		
    }

    function uninstall()
    {
    }
}
?>