<?php

if (!defined('IN_IA')) {
    exit('Access Denied');
}
require_once EWEI_SHOPV2_PLUGIN . 'app/core/page_mobile.php';

class Touch_loop_EweiShopV2Page extends AppMobilePage
{
    public function main()
    {
       if (p('touch_loop'))
       {
            return $this->getposter();
       }
       show_json(0,'插件配置错误');
    }
    public function getposter()
    {
        global $_W, $_GPC;
        $openid = $_W['openid'];
        /**
         * @var $plugin Touch_loopModel
         */
        $plugin = p('touch_loop');
        $goods = pdo_get('ewei_shop_goods', array('id' => 10318));
        $member = m('member')->getInfo($_W['openid']);
        $set = $plugin->getSet();
        $item = ($set['poster']);
        $poster['bg'] = $item['bg'];
        $version = rand(1, 10);
        $md5 = md5(json_encode(array(
            'siteroot' => $_W['siteroot'],
            'openid' => $member['openid'],
            'goodstitle' => $goods['title'],
            'goodprice' => $goods['minprice'],
            'goodsoldprice' => $goods['productprice'],
            'version' => $version,
            'goodsid' => $goods['id']
        )));
        $plugin->deleteImage(array(
            'siteroot' => $_W['siteroot'],
            'openid' => $member['openid'],
            'goodstitle' => $goods['title'],
            'goodprice' => $goods['minprice'],
            'goodsoldprice' => $goods['productprice'],
            'version' => $version,
            'goodsid' => $goods['id']
        ));
        $filename = $md5 . '.png';
        $path = IA_ROOT . "/addons/ewei_shopv2/data/toouch_loop/code/" . $_W['uniacid'] . '/';
        $target = $path . $filename;
        @mkdirs($path);
        if (is_file($target)) {
            $file = $_W['siteroot'] . "addons/ewei_shopv2/data/toouch_loop/code/" . $_W['uniacid'] . "/" . $filename . '?v=1.0';
            app_json(array('status' => 1, 'filename' => $file));
        }
        $poster['data'] = json_decode(str_replace('&quot;', "'", $item['data']), true);
        $image = imagecreatetruecolor(640, 1008);
        $bg = imagecreatefromstring(file_get_contents(tomedia($poster['bg'])));
        imagecopy($image, $bg, 0, 0, 0, 0, 640, 1008);
        imagedestroy($bg);
        $data = $poster['data'];
        /**
         * @var $plugin Touch_loopModel
         */
        if (empty($plugin)) {
            return false;
        }
        $plugin->image = $image;
        $plugin->goods = $goods;
        $plugin->member = $member;
        foreach ($data as $item) {
            if (isset($item['type']) && strlen($item['type']) > 0) {
                $func = 'build' . ucfirst($item['type']);
                call_user_func_array(array($plugin, $func), array('params' => $item));
            }
        }
        imagepng($plugin->image, $target);
        imagedestroy($bg);
        if (is_file($target)) {
            app_json(array('status' => 1, 'filename' => $file));
        }
    }

}