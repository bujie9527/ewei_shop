<?php

//20200612
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Debug_EweiShopV2Page extends WebPage
{

    function main()
    {
        global $_W,$_GPC;
        $openid = 'o212nwihCret5sl-O4oD7L6PnLEU';
        if(p('globonus')){
            p('globonus')->upgradeLevelByAgent($openid);
        }
    }


}