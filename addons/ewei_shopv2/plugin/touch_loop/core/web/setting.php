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

/**
 * Class Setting_EweiShopV2Page
 * @property Touch_loopModel $model
 */
class Setting_EweiShopV2Page extends PluginWebPage {

	function main(){
		global $_W, $_GPC;

        $data = $this->model->getSet();
		if($_W['ispost']) {
            $data = array(
                'type' => (int)($_GPC['data']['type']),
                'icon' => ($_GPC['data']['icon']),
                'post_id' => (int)($_GPC['data']['post_id']),
            );
            $this->model->updateSet($data);

            show_json(1,'更新成功');
        }
		include $this->template();
	}

}
