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
 * Class Index_EweiShopV2Page
 * @property Touch_loopModel $model 一键发圈model
 */
class Poster_EweiShopV2Page extends PluginWebPage
{
    /**
     * 获取列表
     */
    public function main()
    {
        global $_GPC, $_W;
        $set = $this->model->getSet();
        $item = ($set['poster']);
        $data = json_decode(str_replace('&quot;', "'", $item['data']), true);
        $imgroot = $_W['attachurl'];

        //远程路径
        if (empty($_W['setting']['remote'])) {
            setting_load('remote');
        }
        if (!empty($_W['setting']['remote']['type'])) {
            $imgroot = $_W['attachurl_remote'];
        }

        $imgroot = $_W['attachurl'];
        if ($_W['ispost'])
        {
            $datas = array();
            $data = htmlspecialchars_decode($_GPC['data']);
            $datas['data'] = $data;
            $datas['bg'] = $_GPC['bg'];
            $set['poster'] = $datas;

            $this->model->updateSet($set);
            show_json(1,'更新成功');
        }
        include $this->template();
    }
}
