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

class Printset_EweiShopV2Page extends PluginWebPage {

	function main() {
		global $_W, $_GPC;
		
		$sys = pdo_fetch('select * from ' . tablename('ewei_shop_exhelper_sys') . ' where uniacid=:uniacid and merchid=0 limit 1 ', array(':uniacid' => $_W['uniacid']));

		if($_W['ispost']){
			
			ca('exhelper.printset');
			

            $data = array(
                'uniacid' => $_W['uniacid'],
                'ip' => 'localhost',
                'port' => intval($_GPC['port']),
                'ebusiness' => trim($_GPC['ebusiness']),
                'apikey' => trim($_GPC['apikey']),
                'merchid' => 0
            );



			if(!empty($data)){
				
                if (empty($sys)) {
                    pdo_insert('ewei_shop_exhelper_sys', $data);
                } else {
                    pdo_update('ewei_shop_exhelper_sys', $data);
                    pdo_update('ewei_shop_exhelper_sys', $data, array('uniacid' => $_W['uniacid'], 'merchid' => 0));
                }

                plog('exhelper.printset.edit', "修改打印机端口 原端口: {$sys['port']} 新端口: {$data['port']}");

                show_json(1);

            }
        }

        include $this->template();
    }


 /*function test(){

        pdo_delete('ewei_shop_exhelper_esheet');
        pdo_query('ALTER TABLE' . tablename('ewei_shop_exhelper_esheet') . "AUTO_INCREMENT=1");
        $data = array();
        $data[0] = array('name' => '顺丰', 'express' => 'shunfeng', 'code' => 'SF', 'datas' => serialize(array(array('style' => '二联150', 'spec' => '（宽100mm高150mm切点90/60）', 'size' => '150', 'isdefault' => 1),array('style' => '三联210', 'spec' => '（宽100mm 高210mm 切点90/60/60）', 'size' => '210', 'isdefault' => 0))));
        $data[1] = array('name' => '百世快递', 'express' => 'huitongkuaidi', 'code' => 'HTKY', 'datas' => serialize(array(array('style' => '二联183', 'spec' => '（宽100mm 高183mm 切点87/5/91）', 'size' => '183', 'isdefault' => 1))));
        $data[2] = array('name' => '韵达', 'express' => 'yunda', 'code' => 'YD', 'datas' => serialize(array(array('style' => '二联180', 'spec' => '（宽100mm高180mm切点110/70）', 'size' => '180', 'isdefault' => 0), array('style' => '二联203', 'spec' => '（宽100mm 高203mm 切点152/51）', 'size' => '203', 'isdefault' => 1))));
        $data[3] = array('name' => '申通', 'express' => 'shentong', 'code' => 'STO', 'datas' => serialize(array(array('style' => '二联180', 'spec' => '（宽100mm高180mm切点110/70）', 'size' => '180', 'isdefault' => 1), array('style' => '二联150', 'spec' => '（宽100mm 高150mm 切点90/60）', 'size' => '150', 'isdefault' => 0))));
        $data[4] = array('name' => '圆通', 'express' => 'yuantong', 'code' => 'YTO', 'datas' => serialize(array(array('style' => '二联180', 'spec' => '（宽100mm高180mm切点110/70）', 'size' => '180', 'isdefault' => 1))));
        $data[5] = array('name' => 'EMS', 'express' => 'ems', 'code' => 'EMS', 'datas' => serialize(array(array('style' => '二联150', 'spec' => '（宽100mm高150mm切点90/60）', 'size' => '150', 'isdefault' => 1))));
        $data[6] = array('name' => '中通', 'express' => 'zhongtong', 'code' => 'ZTO', 'datas' => serialize(array(array('style' => '二联180', 'spec' => '（宽100mm高180mm切点110/70）', 'size' => '180', 'isdefault' => 1))));
        $data[7] = array('name' => '德邦', 'express' => 'debangwuliu', 'code' => 'DBL', 'datas' => serialize(array(array('style' => '二联177', 'spec' => '（宽100mm高177mm切点107/70）', 'size' => '177', 'isdefault' => 1))));
        $data[8] = array('name' => '优速', 'express' => 'youshuwuliu', 'code' => 'UC', 'datas' => serialize(array(array('style' => '二联180', 'spec' => '（宽100mm高180mm切点110/70）', 'size' => '180', 'isdefault' => 1))));
        $data[9] = array('name' => '宅急送', 'express' => 'zhaijisong', 'code' => 'ZJS', 'datas' => serialize(array(array('style' => '二联120', 'spec' => '（宽100mm高116mm切点98/18）', 'size' => '120', 'isdefault' => 1), array('style' => '二联180', 'spec' => '（宽100mm高180mm切点110/70）', 'size' => '180', 'isdefault' => 0))));
        $data[10] = array('name' => '京东', 'express' => 'jd', 'code' => 'JD', 'datas' => serialize(array(array('style' => '二联110', 'spec' => '（宽100mm高110mm切点60/50）', 'size' => '110', 'isdefault' => 1))));
        $data[11] = array('name' => '信丰', 'express' => 'xinfengwuliu', 'code' => 'XFEX', 'datas' => serialize(array(array('style' => '二联150', 'spec' => '（宽100mm高150mm切点90/60）', 'size' => '150', 'isdefault' => 1))));
        $data[12] = array('name' => '全峰', 'express' => 'quanfengkuaidi', 'code' => 'QFKD', 'datas' => serialize(array(array('style' => '二联180', 'spec' => '（宽100mm高180mm切点110/70）', 'size' => '180', 'isdefault' => 1))));
        $data[13] = array('name' => '跨越速运', 'express' => 'kuayue', 'code' => 'KYSY', 'datas' => serialize(array(array('style' => '二联137', 'spec' => '（宽100mm高137mm切点101/36）', 'size' => '137', 'isdefault' => 1))));
        $data[14] = array('name' => '安能', 'express' => 'annengwuliu', 'code' => 'ANE', 'datas' => serialize(array(array('style' => '三联180', 'spec' => '（宽100mm高180mm切点110/30/40）', 'size' => '180', 'isdefault' => 1))));
        $data[15] = array('name' => '快捷', 'express' => 'kuaijiesudi', 'code' => 'FAST', 'datas' => serialize(array(array('style' => '二联180', 'spec' => '（宽100mm高180mm切点110/70）', 'size' => '180', 'isdefault' => 1))));
        $data[16] = array('name' => '国通', 'express' => 'guotongkuaidi', 'code' => 'GTO', 'datas' => serialize(array(array('style' => '二联180', 'spec' => '（宽100mm高180mm切点110/70）', 'size' => '180', 'isdefault' => 1))));
        $data[17] = array('name' => '天天', 'express' => 'tiantian', 'code' => 'HHTT', 'datas' => serialize(array(array('style' => '二联180', 'spec' => '（宽100mm高180mm切点110/70）', 'size' => '180', 'isdefault' => 1))));
        $data[18] = array('name' => '中铁快运', 'express' => 'zhongtiekuaiyun', 'code' => 'ZTKY', 'datas' => serialize(array(array('style' => '二联150', 'spec' => '（宽100mm高150mm切点90/60）', 'size' => '150', 'isdefault' => 1))));
        $data[19] = array('name' => '邮政快递包裹', 'express' => 'youzhengguonei', 'code' => 'YZPY', 'datas' => serialize(array(array('style' => '二联180', 'spec' => '（宽100mm高180mm切点110/70）', 'size' => '180', 'isdefault' => 1))));
        $data[20] = array('name' => '邮政国内标快', 'express' => 'youzhengguonei', 'code' => 'YZBK', 'datas' => serialize(array(array('style' => '二联150', 'spec' => '（宽100mm高150mm切点90/60）', 'size' => '150', 'isdefault' => 1))));
        $data[21] = array('name' => '全一快递', 'express' => 'quanyikuaidi', 'code' => 'UAPEX', 'datas' => serialize(array(array('style' => '二联150', 'spec' => '（宽90mm高150mm切点90/60）', 'size' => '150', 'isdefault' => 1))));
        $data[22] = array('name' => '速尔快递', 'express' => 'sue', 'code' => 'SURE', 'datas' => serialize(array(array('style' => '二联150', 'spec' => '（宽100mm高150mm切点90/60）', 'size' => '150', 'isdefault' => 1))));
        foreach ($data as $item) {
            pdo_insert('ewei_shop_exhelper_esheet', $item);
        }

    }*/

}