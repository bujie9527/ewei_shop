<?php

error_reporting(0);
require '../../../../../framework/bootstrap.inc.php';
require '../../../../../addons/ewei_shopv2/defines.php';
require '../../../../../addons/ewei_shopv2/core/inc/functions.php';
global $_W, $_GPC;

ignore_user_abort(); //忽略关闭浏览器
set_time_limit(0); //永远执行

$sets = pdo_fetchall('select uniacid from ' . tablename('ewei_shop_sysset'));
foreach ($sets as $set) {

    $_W['uniacid'] = $set['uniacid'];
    if (empty($_W['uniacid'])) {
        continue;
    } 
    $trade = m('common')->getSysset('trade', $_W['uniacid']);
    $logs = pdo_fetchall("select id,`day`,fullbackday,openid,priceevery,price,fullbacktime,goodsid,optionid from ".tablename('ewei_shop_fullback_log')." where uniacid = ".$_W['uniacid']." and isfullback = 0 and (fullbacktime =0 or fullbacktime < ".strtotime('-1 days').") and fullbackday < day ");
    $today = strtotime(date("Y-m-d"),time());
    foreach ($logs as $key => $value){

        // 查询当前log下的log_map 有大于当前时间的跳过
        $mapCount = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_shop_fullback_log_map') . ' WHERE logid=:logid AND fullback_time >= :fullback_time', array(
            ':logid' => $value['id'],
            ':fullback_time' => strtotime(date('Y-m-d 00:00:00')),
        ));

        if ($mapCount > 0) {

            continue;
        }

        if(($value['day']-$value['fullbackday'])>1){
            $count = floor((time()-$value['fullbacktime'])/86400);//天数

            if($count>=1){
                // 如果一直没有执行在执行的时候直接到了完成的时间的话
                if($value['day']-$value['fullbackday']<=$count){
                    $count  = $value['day']-$value['fullbackday'];
                    $value['priceevery'] = $value['price']-$value['priceevery']*$value['fullbackday'];
                    $success = pdo_update('ewei_shop_fullback_log', array('fullbackday'=>$value['day'],'fullbacktime'=>$today,'isfullback'=>1), array('id' => $value['id']));
                    if ($success){
                        $result = m('member')->setCredit($value['openid'], 'credit2', $value['priceevery'], array('0', $_W['shopset']['shop']['name'] . '全返余额' . $value['priceevery']));
                    }
                }else{
                    $value['priceevery'] = $value['priceevery'] * $count;
                    $value['fullbackday'] = $value['fullbackday'] + $count;

                    $success = pdo_update('ewei_shop_fullback_log', array('fullbackday'=>$value['fullbackday'],'fullbacktime'=>$today), array('id' => $value['id']));
                    if ($success){
                        $result = m('member')->setCredit($value['openid'], 'credit2', $value['priceevery'], array('0', $_W['shopset']['shop']['name'] . '全返余额' . $value['priceevery']));
                    }
                }
            }
        }elseif(($value['day']-$value['fullbackday'])==1){
            $count = 1;
            $value['priceevery'] = $value['price']-$value['priceevery']*$value['fullbackday'];

            $success = pdo_update('ewei_shop_fullback_log', array('fullbackday'=>$value['day'],'fullbacktime'=>time(),'isfullback'=>1), array('id' => $value['id']));

            if ($success){
                $result = m('member')->setCredit($value['openid'], 'credit2', $value['priceevery'], array('0', $_W['shopset']['shop']['name'] . '全返余额' . $value['priceevery']));
            }

        }

        if($count>1 && $success){
            for ($i = 1; $i <= $count-1; $i++) {
                $logdata = array();
                $logdata['uniacid'] =  $_W['uniacid'];
                $logdata['fullback_time'] = $value['fullbacktime']+(86400*$i);
                $logdata['logid'] = $value['id'];
                $logdata['price'] = 0;
                $logdata['goodsid'] = $value['goodsid'];
                $logdata['optionid'] = $value['optionid'];
                $logdata['day'] = 0;
                pdo_insert('ewei_shop_fullback_log_map',$logdata);
            }
        }elseif ($success){
            $logdata = array();
            $logdata['uniacid'] =  $_W['uniacid'];
            $logdata['fullback_time'] = $today;
            $logdata['logid'] = $value['id'];
            $logdata['price'] = $value['priceevery'];
            $logdata['goodsid'] = $value['goodsid'];
            $logdata['optionid'] = $value['optionid'];
            $logdata['day'] = $count;
            pdo_insert('ewei_shop_fullback_log_map',$logdata);
        }

    }

}




