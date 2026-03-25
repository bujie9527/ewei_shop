<?php
/*
 * 人人商城
 *
 * 青岛易联互动网络科技有限公司
 * http://www.we7shop.cn
 * TEL: 4000097827/18661772381/15865546761
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class SettingController extends PluginWebPage
{

    public function main()
    {
        global $_W, $_GPC;
        ($this->createDoaminTable());

        if (!is_dir(IA_ROOT . '/pc')) {
            @mkdirs(IA_ROOT . '/pc');
            ($this->newSymlink());
            @copy(EWEI_SHOPV2_PLUGIN . $this->pluginname . '/core/tmp/index.php', IA_ROOT . '/pc/index.php');
        }
        if ($_W['ispost']) {
            if (!empty($_GPC['qq_nick']) && !empty($_GPC['qq_num'])) {
                $qq = array();
                foreach ($_GPC['qq_nick'] as $key => $val) {
                    $qq[$key]['nickname'] = $val;
                    $qq[$key]['qqnum'] = $_GPC['qq_num'][$key];
                }
            }
            if (!empty($_GPC['wx_nick']) && !empty($_GPC['wx_img'])) {
                $wx = array();
                foreach ($_GPC['wx_nick'] as $key => $val) {
                    $wx[$key]['wxnickname'] = $val;
                    $wx[$key]['wximg'] = $_GPC['wx_img'][$key];
                }
                $wx_nick = $_GPC['wx_nick'];
                $wx_img = $_GPC['wx_img'];
            }
            $data = array();
            $data['search'] = $_GPC['search'];
            $data['search'] = str_replace('，', ',', $data['search']);
            $data['copyright'] = $_GPC['copyright'];
            $data['qq'] = $qq;
            $data['wx'] = $wx;
            $data['wx_nick'] = $wx_nick;
            $data['wx_img'] = $wx_img;
            $data['domain'] = $_GPC['domain'];
            $data['mobile_domain'] = $_GPC['mobile_domain'];
            $data['advBanner'] = $_GPC['advBanner'];
            if (!empty($data['advBanner'])){
                $data['advBannerText'] = $_GPC['advBannerText'];
            }
            pdo_delete('ewei_shop_domain_bindings', ['uniacid' => $_W['uniacid'], 'plugin' => 'pc']);
            $res = pdo_get('ewei_shop_domain_bindings', ['domain' => $_GPC['domain']]);
            if (empty($res)) {
                pdo_delete('ewei_shop_domain_bindings', array('domain' => $_GPC['domain']));
                pdo_insert('ewei_shop_domain_bindings', ['uniacid' => $_W['uniacid'], 'plugin' => 'pc', 'domain' => $data['domain'], 'mobile_domain' => $data['mobile_domain']]);
            }
            if ($res && $res['uniacid'] != $_W['uniacid']) {
                show_json(0, '域名已经被绑定');
            }
            m('common')->updatePluginset(array('pc' => $data));
            show_json(1);
        }
        $data = m('common')->getPluginset('pc');
        if (mb_strlen($data['domain']) > 0) {
            $data['url'] = $data['domain'];
        } else {
            $data['url'] = pcUrl('pc', null, true);
        }
        $domain = $data['domain'];

        include $this->template();
    }


    public function pcUrl($do, $query, $full)
    {
        global $_W, $_GPC;
        $result = m('common')->getPluginSet('pc');
        if (isset($result['domain']) && mb_strlen($result['domain'])) {
            return $siteroot = ($full === true ? $_W['siteroot'] : './') . '?r=' . $do . '&' . http_build_query($query);
        } else {
            return pcUrl($do, $query, $full);
        }
    }

    public function newSymlink()
    {
        $manual = ATTACHMENT_ROOT;  // 原路径
        $manualLink = IA_ROOT . '/pc/attachment';   // 软连接路径
        $isExistFile = true;    // 原文件是否存在的标识
        if (is_dir($manual) && !is_file($manualLink)) {  // 原文件存在且软连接不存在时，创建软连接
            return (symlink($manual, $manualLink));              // 创建软连接
        } else {
            return true;
        }
        if (!is_file($manualLink)) {
            $isExistFile = false;
        } elseif (!is_file($manual)) { // 原文件不存在时
            $isExistFile = false;
        }
        return array('isExistFile' => $isExistFile, 'manual' => $manualLink);
    }

    public function createDoaminTable()
    {
        if (!pdo_tableexists('ewei_shop_domain_bindings')) {
            return (pdo_query(" CREATE TABLE " . tablename('ewei_shop_domain_bindings') . " (
                              `id` int NOT NULL AUTO_INCREMENT,
                              `uniacid` int DEFAULT NULL,
                              `domain` varchar(255) DEFAULT NULL,
                              `plugin` varchar(255) DEFAULT NULL,
                              `mobile_domain` varchar(255) DEFAULT NULL,
                              PRIMARY KEY (`id`)
                            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;")
            );
        }
    }
}
