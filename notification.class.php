<?php
require_once 'php-discord-sdk/support/sdk_discord.php';

class Notification
{
    protected $notif;
    public function __construct()
    {
        
    }

    public function initDiscord()
    {
        $token = getenv_docker('DISCORD_TOKEN', false);
        if(!$token){
            return false;
        }
        // https://discord.com/api/webhooks/985966054704558122/wLJqCxkSQux13hV3YKTgd0rrRr-nqdEmQmf-TjKF0_rdBvPwwaTAZ_NJWN99lnjPZLEp
        $channelid = "wLJqCxkSQux13hV3YKTgd0rrRr-nqdEmQmf-TjKF0_rdBvPwwaTAZ_NJWN99lnjPZLEp";

    }
   
}