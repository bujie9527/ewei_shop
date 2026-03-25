<?php


class GoodsController extends PluginMobilePage
{

    public function detail()
    {
        global $_GPC, $_W;

        $id = $_GPC['id'];


        if (!$this->model->checkGoodsExists($id)) {
            die("浏览商品不存在");
        }

        $data = $this->model->invoke('goods::get_detail');
        // 主图需要塞到所有缩略图里面
        array_unshift($data['goods']['thumbs'], $data['goods']['thumb']);

        require_once EWEI_SHOPV2_PATH . 'core/mobile/goods/picker.php';

        $pickerData = $this->model->invoke('goods::get_picker');

        // 这里面有评论信息
        if (!empty($data['goods']['packagegoods'])) {
            $_GPC['goodsid'] = $data['goods']['packagegoods']['goodsid'];
            $packages = $this->model->invoke('package::get_list');
            $data['packages'] = isset($packages['list']) ? $packages['list'] : null;

            // 贴上二维码
            array_walk($data['packages'], function (&$item) use ($_W) {
                $site = $_W['siteroot'];
                $_W['siteroot'] = $this->model->set['mobile_domain'] ? $this->model->set['mobile_domain'] : $_W['siteroot'];
                $item['qrcode'] = m('qrcode')->createQrCode(mobileUrl('goods.package.detail', array('pid' => $item['id']), true));
                $_W['siteroot'] = $site;

            });
        }


        // 拼接多规格商品信息
        $data['specs'] = isset($pickerData['specs']) ? $pickerData['specs'] : null;
        // 选项信息
        $data['options'] = isset($pickerData['options']) ? $pickerData['options'] : null;

        // 自定义表单
        $data['diyform'] = $pickerData['diyform'];
        // 注入页面标题
        $data['title'] = $data['goods']['title'];
        // 评论相关信息
        $comments = $this->model->invoke('goods::get_comments');
        // 评论列表
        $commentList = $this->model->invoke('goods::get_comment_list');

        $data['comment'] = array(
            'count' => isset($comments['count']) ? $comments['count'] : null,
            'list' => $commentList['list'],
            'total' => $commentList['total'],
            'page' => $commentList['page'],
            'pagesize' => $commentList['pagesize']
        );

        // 商品购买二维码
        $site = $_W['siteroot'];
        $_W['siteroot'] = $this->model->set['mobile_domain'] ? $this->model->set['mobile_domain'] : $_W['siteroot'];
        $data['goods']['qrcode'] = m('qrcode')->createQrCode(mobileUrl('goods.detail', array('id' => $id), true));
        $_W['siteroot'] = $site;
        // 面包屑导航
        $data['goods']['breadcrumb'] = $this->model->getBreadcrumb($id);

        // 看了又看数据
        $data['goods']['footerMark'] = $this->model->getUserFooterMark($id);

        if (isset($_GPC['debug'])) {
            print_r($data);
            exit;
        }

        // 渲染页面
        return $this->view('goods.detail', $data);

    }


    /**
     * 评论列表
     * @return mixed
     * @author: Vencenty
     * @time: 2019/5/30 14:12
     */
    public function comment_list()
    {
        return $this->model->invoke('goods::get_comment_list', false);
    }

    /**
     * 获取评论
     * @return mixed
     * @author: Vencenty
     * @time: 2019/6/5 14:30
     */
    public function comments()
    {
        return $this->model->invoke('goods::get_comments', false);
    }

    /**
     * 自定义表单加入购物车
     * @return mixed
     * @author: Vencenty
     * @time: 2019/6/5 14:30
     */
    public function addShopCartDiyForm()
    {
        return $this->model->invoke('order.create::diyform', false);
    }

    /**
     * 无自定义表单，普通加入购物车
     * @author: Vencenty
     * @time: 2019/6/11 9:09
     */
    public function addShopCart()
    {
        return $this->model->invoke('member.cart::add', false);
    }

    /**
     * 计算多规格商品价格
     * 目前接口已经废弃
     * @author: Vencenty
     * @time: 2019/5/31 19:59
     */
    public function calcSpecGoodsPrice()
    {
        global $_W, $_GPC;

        // 商品id
        $id = $_GPC['id'];
        // $id = 5347;
        $optionid = $_GPC['optionid'];

        // 排序后拼接
        sort($optionid);
        // 前台传过来的排序后的optionid
        $optionid = implode('_', $optionid);

        $r = pdo_getall('ewei_shop_goods_option', array('goodsid' => $id));
        $specs = array_column($r, 'specs', 'id');
        // 整理一下数据库中的排序字段,因为有些数据没有做排序处理
        array_walk($specs, function (&$value) {
            $tempValue = explode('_', $value);
            sort($tempValue);
            $value = implode('_', $tempValue);
        });

        // 查找结果,返回的是数据库记录的id
        $rowId = array_search($optionid, $specs);
        // 查找结果
        $findResult = array_filter($r, function ($value) use ($rowId) {
            return $value['id'] == $rowId;
        });

        return json_encode(current($findResult));
    }


}
