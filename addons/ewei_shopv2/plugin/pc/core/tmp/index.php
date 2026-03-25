<?php
/*
 * 人人商城
 *
 * 青岛易联互动网络科技有限公司
 * http://www.we7shop.cn
 * TEL: 4000097827/18661772381/15865546761
 */
define('IN_MOBILE', true);
global $_W;
require __DIR__. '/../framework/bootstrap.inc.php';
load()->web('common');
//var_dump($_W['siteroot']);

//$uniacid  = $_W['uniacid'] = intval($_GPC['i']);
if (empty($uniacid))
{
    $res=  pdo_get('ewei_shop_domain_bindings',['domain'=>$_W['siteroot'],'plugin'=>'pc']);
    if (!empty($res))
    {
        $uniacid =   $_W['uniacid'] = $res['uniacid'];
    }else
    {
        echo '域名未绑定!';
        exit();
    }
}

$_W['attachurl'] = $_W['attachurl_local'] = $_W['siteroot'] . $_W['config']['upload']['attachdir'] . '/';

if (!empty($_W['setting']['remote'][$_W['uniacid']]['type'])) {
    $_W['setting']['remote'] = $_W['setting']['remote'][$_W['uniacid']];
}

$info = uni_setting_load('remote', $uniacid);
if(!empty($info['remote'])){
    if($info['remote']['type'] !=0){
        $_W['setting']['remote'] = $info['remote'];
    }
}
if (!empty($_W['setting']['remote']['type'])) {
    if ($_W['setting']['remote']['type'] == ATTACH_FTP) {
        $_W['attachurl'] = $_W['attachurl_remote'] = $_W['setting']['remote']['ftp']['url'] . '/';
    } elseif ($_W['setting']['remote']['type'] == ATTACH_OSS) {
        $_W['attachurl'] = $_W['attachurl_remote'] = $_W['setting']['remote']['alioss']['url'] . '/';
    } elseif ($_W['setting']['remote']['type'] == ATTACH_QINIU) {
        $_W['attachurl'] = $_W['attachurl_remote'] = $_W['setting']['remote']['qiniu']['url'] . '/';
    } elseif ($_W['setting']['remote']['type'] == ATTACH_COS) {
        $_W['attachurl'] = $_W['attachurl_remote'] = $_W['setting']['remote']['cos']['url'] . '/';
    }
}

//$uniacid = $_GPC['i'] = 4;

// check微擎绑定
//if(!empty($_GPC['formwe7'])){
//    $bind = pdo_fetch('SELECT * FROM '. tablename('ewei_shop_wxapp_bind'). ' WHERE wxapp=:wxapp LIMIT 1', array(':wxapp'=>$uniacid));
//    if(!empty($bind) && !empty($bind['uniacid'])){
//        $uniacid = $_GPC['i'] = $bind['uniacid'];
//    }
//}

header("ACCESS-CONTROL-ALLOW-ORIGIN:*");

if(empty($uniacid)){
    die('Access Denied.');
}
$site = WeUtility::createModuleSite('ewei_shopv2');
$_GPC['c']='site';
$_GPC['a']='entry';
$_GPC['m']='ewei_shopv2';
$_GPC['do']='mobile';
$_W['uniacid'] = (int)$_GPC['i'];
$_W['account'] = uni_fetch($_W['uniacid']);
$_W['acid'] = (int)$_W['account']['acid'];
$_GPC['r'] = str_replace('/','.',$_GPC['r']);

if (strexists($_GPC['r'],'pc'))
{

    $_GPC['r'] = str_replace('pc','',$_GPC['r']);
    $_GPC['r'] = str_replace('pc.','',$_GPC['r']);
}
if (!isset($_GPC['r'])){
    $_GPC['r']='pc';
}else{
    $_GPC['r']='pc.'.$_GPC['r'];
}
$_W['uniacid'] = $uniacid;
if(!is_error($site)) {
    $method = 'doMobileMobile';
    $site->uniacid = $uniacid ;
    $site->inMobile = true;
//    dump([$site, $method]);
    if (method_exists($site, $method)) {
        $r = $site->$method();
        var_dump($r);
        if (!empty($r)) {
            echo $r;die;
        }
        exit;

    }
}

