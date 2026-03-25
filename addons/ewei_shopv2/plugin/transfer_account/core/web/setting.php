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

class Setting_EweiShopV2Page extends PluginWebPage {
    /**
     * @var $model Transfer_AccountModel
     */
    public $model;
	function main(){
		global $_W, $_GPC;
        $data = $this->model->getSet();
		if($_W['ispost']) {
            $data = $this->model->getGpc()['data'];
            $this->model->updateSet($data);
            show_json(1,'操作成功');
		}
		include $this->template();
	}

}
