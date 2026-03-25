<?php
//20200807  修复京东部分商城采集
set_time_limit(0);
if (!defined('IN_IA')) {
    exit('Access Denied');
}

error_reporting(0);
class GoodshelperModel extends PluginModel
{
    private $arg_name = 'id';




    /*
     *  处理商品ID接口
     *  @author xzx
     */
    function matchid($url,$type = 'taobao')
    {

        if ($type == 'redbook' || $type == 'pdd'){
            $this->arg_name = 'goods_id';
            $regx = '/.*[&|\?]'.$this->arg_name.'=([^&]*)(.*)/';

            preg_match($regx, $url,$data);

            $data = $data[1];
        }elseif ($type == 'suning'){
            $url = parse_url($url);

            $param = explode('/',$url['path']);

            $data = array();
            $data['shop_id'] = $param[1];
            $data['goods_id'] = str_replace(strrchr($param[2], "."),"",$param[2]);

        }elseif ($type == 'alibaba'){

            $url = parse_url($url);

            $param = explode('/',$url['path']);

            $data = str_replace(strrchr($param[2], "."),"",$param[2]);

        }elseif ( $type == 'jd'){
            $url = parse_url($url);

            $param = explode('/',$url['path']);

            $data = str_replace(strrchr($param[1], "."),"",$param[1]);


        }else{
            $this->arg_name = 'id';

            $regx = '/.*[&|\?]'.$this->arg_name.'=([^&]*)(.*)/';

            preg_match($regx, $url,$data);

            $data = $data[1];
        }

        return $data;
    }

    /*
     *  获取商品信息接口
     *  @author xzx
     */
    public function getcontent($params)
    {
      
        $url = 'http://renren.weapp.cc/goodshelper.php';

        ksort($params);

        $queryString = '';
        foreach ($params as $key => $value) {
            $queryString .= '&' . ($key . '=' . $value);
        }

        $queryString = substr($queryString, 1);

        $sign = md5('ELINKINT' . $queryString . md5($params['timestamp'] . '#vTqJ$ieOq5hxoQ31PUWXiXH&QUc7JGd') . '#vTqJ$ieOq5hxoQ31PUWXiXH&QUc7JGd');

        $params['sign'] = $sign;

        $res = ihttp_post($url, $params);
        //$res = Http::postJson($url, $params);


        if ($res['code'] != 200 || $res['status'] != 'OK'){
            show_json(0,'返回值错误');
        }
		
      //$res['content'] = '{"error":0,"goods":{"title":"免手洗抖音拖把家用刮刮乐拖把桶办公懒人平板加厚刮刮乐拖地神器","thumbs":[{"id":281227663562,"url":"https://t00img.yangkeduo.com/goods/images/2020-03-07/13c8a69e-2030-41a8-ad0c-fe00974533dd.jpg"},{"id":281227663563,"url":"https://t00img.yangkeduo.com/goods/images/2020-03-07/245c69e1-8a34-431e-b230-d3b646e443ea.jpeg"},{"id":281227663564,"url":"https://t00img.yangkeduo.com/goods/images/2020-03-07/af948b52-4bf3-4029-b92a-1c918b75785f.jpeg"},{"id":281227663565,"url":"https://t00img.yangkeduo.com/goods/images/2020-03-07/8f6f56f4-a4cf-49ce-a676-0f43efc1950d.jpeg"},{"id":281227663566,"url":"https://t00img.yangkeduo.com/goods/images/2020-03-07/48ad615f-59ff-4687-b838-bfe6d96f45ab.jpeg"},{"id":281227663567,"url":"https://t00img.yangkeduo.com/goods/images/2020-03-07/fda48339-78ba-47e0-a3d6-f9d444a4e897.jpeg"},{"id":281227663568,"url":"https://t00img.yangkeduo.com/goods/images/2020-03-07/dd1e6e79-afe2-42d7-b4bf-ca91de5b56c9.jpg"},{"id":281227663569,"url":"https://t00img.yangkeduo.com/goods/images/2020-03-27/a429319b-baba-413f-b867-d6439edac6f5.jpg"}],"sales_count":"已拼10万+件","price":"199","min_group_price":"16.9","max_group_price":"45.19","min_normal_price":"29.9","max_normal_price":"62.9","options":[{"marketPrice":0,"previewPriority":0,"goodsId":91240910838,"yellowEndTime":1601481599,"batchSn":"A0054VC-547046617653543628","limitQuantity":999999,"spec":"3159691549,3160108408","soldQuantity":0,"yellowLabel":"再售25338件后恢复¥23.9","specs":[{"spec_value_id":3159691549,"spec_key":"款式","spec_value":"【爆款热卖】豪华款","spec_key_id":1218},{"spec_value_id":3160108408,"spec_key":"套餐","spec_value":"清洗桶+拖把+1块布（赠刮刀）","spec_key_id":20711}],"price":0,"groupPrice":"16.9","oldGroupPrice":2390,"startTime":0,"attribute":"{}","thumbUrl":"https://t00img.yangkeduo.com/goods/images/2020-03-07/de381ad391c75f3027c6e597c3d20ae5.jpeg","isOnSale":1,"skuId":479334501663,"quantity":1000,"weight":0,"isOnsale":1,"skuExpansionPrice":"0","defaultQuantity":100,"groupSkuUnitPrice":"","normalSkuUnitPrice":"","groupTip":"下单立减2元","normalPrice":"29.9","endTime":0,"staticLimitQuantity":10,"initQuantity":0,"skuID":479334501663},{"marketPrice":0,"previewPriority":1,"goodsId":91240910838,"yellowEndTime":1601481599,"batchSn":"A0054VC-547046617653543628","limitQuantity":999999,"spec":"3159691549,2986825252","soldQuantity":0,"yellowLabel":"再售25338件后恢复¥27.9","specs":[{"spec_value_id":3159691549,"spec_key":"款式","spec_value":"【爆款热卖】豪华款","spec_key_id":1218},{"spec_value_id":2986825252,"spec_key":"套餐","spec_value":"清洗桶+拖把+2块布（赠刮刀）","spec_key_id":20711}],"price":0,"groupPrice":"21.14","oldGroupPrice":2790,"startTime":0,"attribute":"{}","thumbUrl":"https://t00img.yangkeduo.com/goods/images/2020-03-07/a6543407175d0f92a2de0ffaecbfd85c.jpeg","isOnSale":1,"skuId":479334501664,"quantity":1000,"weight":0,"isOnsale":1,"skuExpansionPrice":"0","defaultQuantity":100,"groupSkuUnitPrice":"","normalSkuUnitPrice":"","groupTip":"下单立减2元","normalPrice":"33.9","endTime":0,"staticLimitQuantity":10,"initQuantity":0,"skuID":479334501664},{"marketPrice":0,"previewPriority":2,"goodsId":91240910838,"yellowEndTime":1601481599,"batchSn":"A0054VC-547046617653543628","limitQuantity":999999,"spec":"3159691549,2986822406","soldQuantity":0,"yellowLabel":"再售25338件后恢复¥31.9","specs":[{"spec_value_id":3159691549,"spec_key":"款式","spec_value":"【爆款热卖】豪华款","spec_key_id":1218},{"spec_value_id":2986822406,"spec_key":"套餐","spec_value":"清洗桶+拖把+4块布（赠刮刀）","spec_key_id":20711}],"price":0,"groupPrice":"23.55","oldGroupPrice":3190,"startTime":0,"attribute":"{}","thumbUrl":"https://t00img.yangkeduo.com/goods/images/2020-03-07/a790623e3172ab71fac8628a7a9ff526.jpeg","isOnSale":1,"skuId":479410079985,"quantity":1000,"weight":0,"isOnsale":1,"skuExpansionPrice":"0","defaultQuantity":100,"groupSkuUnitPrice":"","normalSkuUnitPrice":"","groupTip":"下单立减2元","normalPrice":"37.9","endTime":0,"staticLimitQuantity":10,"initQuantity":0,"skuID":479410079985},{"marketPrice":0,"previewPriority":3,"goodsId":91240910838,"yellowEndTime":1601481599,"batchSn":"A0054VC-547046617653543628","limitQuantity":999999,"spec":"3159691549,2986812951","soldQuantity":0,"yellowLabel":"再售25338件后恢复¥35.9","specs":[{"spec_value_id":3159691549,"spec_key":"款式","spec_value":"【爆款热卖】豪华款","spec_key_id":1218},{"spec_value_id":2986812951,"spec_key":"套餐","spec_value":"清洗桶+拖把+6块布（赠刮刀）","spec_key_id":20711}],"price":0,"groupPrice":"27.78","oldGroupPrice":3590,"startTime":0,"attribute":"{}","thumbUrl":"https://t00img.yangkeduo.com/goods/images/2020-03-07/4f437a4f5c9da2fcf72d5b78b93f645a.jpeg","isOnSale":1,"skuId":479410079986,"quantity":1000,"weight":0,"isOnsale":1,"skuExpansionPrice":"0","defaultQuantity":100,"groupSkuUnitPrice":"","normalSkuUnitPrice":"","groupTip":"下单立减2元","normalPrice":"41.9","endTime":0,"staticLimitQuantity":10,"initQuantity":0,"skuID":479410079986},{"marketPrice":0,"previewPriority":4,"goodsId":91240910838,"yellowEndTime":1601481599,"batchSn":"A0054VC-547046617653543628","limitQuantity":999999,"spec":"3159691549,2986814846","soldQuantity":0,"yellowLabel":"再售25338件后恢复¥49.9","specs":[{"spec_value_id":3159691549,"spec_key":"款式","spec_value":"【爆款热卖】豪华款","spec_key_id":1218},{"spec_value_id":2986814846,"spec_key":"套餐","spec_value":"清洗桶+2套拖把+10块布（赠刮刀）","spec_key_id":20711}],"price":0,"groupPrice":"39.4","oldGroupPrice":4990,"startTime":0,"attribute":"{}","thumbUrl":"https://t00img.yangkeduo.com/goods/images/2020-03-07/b57e186f659b0b5492a7eae91b460a7a.jpeg","isOnSale":1,"skuId":479338450380,"quantity":1000,"weight":0,"isOnsale":1,"skuExpansionPrice":"0","defaultQuantity":100,"groupSkuUnitPrice":"","normalSkuUnitPrice":"","groupTip":"下单立减2元","normalPrice":"53.9","endTime":0,"staticLimitQuantity":10,"initQuantity":0,"skuID":479338450380},{"marketPrice":0,"previewPriority":5,"goodsId":91240910838,"yellowEndTime":1601481599,"batchSn":"A0054VC-547046617653543628","limitQuantity":999999,"spec":"3159716265,3160108408","soldQuantity":0,"yellowLabel":"再售25338件后恢复¥29.9","specs":[{"spec_value_id":3159716265,"spec_key":"款式","spec_value":"【店长推荐】旗舰款","spec_key_id":1218},{"spec_value_id":3160108408,"spec_key":"套餐","spec_value":"清洗桶+拖把+1块布（赠刮刀）","spec_key_id":20711}],"price":0,"groupPrice":"22.79","oldGroupPrice":2990,"startTime":0,"attribute":"{}","thumbUrl":"https://t00img.yangkeduo.com/goods/images/2020-03-07/f9cbbaba0ac4b7e54bc4468c12ebef98.jpeg","isOnSale":1,"skuId":479334501668,"quantity":1000,"weight":0,"isOnsale":1,"skuExpansionPrice":"0","defaultQuantity":100,"groupSkuUnitPrice":"","normalSkuUnitPrice":"","groupTip":"下单立减2元","normalPrice":"34.9","endTime":0,"staticLimitQuantity":10,"initQuantity":0,"skuID":479334501668},{"marketPrice":0,"previewPriority":6,"goodsId":91240910838,"yellowEndTime":1601481599,"batchSn":"A0054VC-547046617653543628","limitQuantity":999999,"spec":"3159716265,2986825252","soldQuantity":0,"yellowLabel":"再售25338件后恢复¥33.9","specs":[{"spec_value_id":3159716265,"spec_key":"款式","spec_value":"【店长推荐】旗舰款","spec_key_id":1218},{"spec_value_id":2986825252,"spec_key":"套餐","spec_value":"清洗桶+拖把+2块布（赠刮刀）","spec_key_id":20711}],"price":0,"groupPrice":"26.11","oldGroupPrice":3390,"startTime":0,"attribute":"{}","thumbUrl":"https://t00img.yangkeduo.com/goods/images/2020-03-07/521b9ca44f3b6be47a05944d920d7334.jpeg","isOnSale":1,"skuId":479334501669,"quantity":1000,"weight":0,"isOnsale":1,"skuExpansionPrice":"0","defaultQuantity":100,"groupSkuUnitPrice":"","normalSkuUnitPrice":"","groupTip":"下单立减2元","normalPrice":"38.9","endTime":0,"staticLimitQuantity":10,"initQuantity":0,"skuID":479334501669},{"marketPrice":0,"previewPriority":7,"goodsId":91240910838,"yellowEndTime":1601481599,"batchSn":"A0054VC-547046617653543628","limitQuantity":999999,"spec":"3159716265,2986822406","soldQuantity":0,"yellowLabel":"再售25338件后恢复¥37.9","specs":[{"spec_value_id":3159716265,"spec_key":"款式","spec_value":"【店长推荐】旗舰款","spec_key_id":1218},{"spec_value_id":2986822406,"spec_key":"套餐","spec_value":"清洗桶+拖把+4块布（赠刮刀）","spec_key_id":20711}],"price":0,"groupPrice":"29.43","oldGroupPrice":3790,"startTime":0,"attribute":"{}","thumbUrl":"https://t00img.yangkeduo.com/goods/images/2020-03-07/2bd41b1433c25ca1c08b64641996af22.jpeg","isOnSale":1,"skuId":479410079987,"quantity":1000,"weight":0,"isOnsale":1,"skuExpansionPrice":"0","defaultQuantity":100,"groupSkuUnitPrice":"","normalSkuUnitPrice":"","groupTip":"下单立减2元","normalPrice":"42.9","endTime":0,"staticLimitQuantity":10,"initQuantity":0,"skuID":479410079987},{"marketPrice":0,"previewPriority":8,"goodsId":91240910838,"yellowEndTime":1601481599,"batchSn":"A0054VC-547046617653543628","limitQuantity":999999,"spec":"3159716265,2986812951","soldQuantity":0,"yellowLabel":"再售25338件后恢复¥41.9","specs":[{"spec_value_id":3159716265,"spec_key":"款式","spec_value":"【店长推荐】旗舰款","spec_key_id":1218},{"spec_value_id":2986812951,"spec_key":"套餐","spec_value":"清洗桶+拖把+6块布（赠刮刀）","spec_key_id":20711}],"price":0,"groupPrice":"32.75","oldGroupPrice":4190,"startTime":0,"attribute":"{}","thumbUrl":"https://t00img.yangkeduo.com/goods/images/2020-03-07/ba548af2ad3fd1497e52e1925f4b07b3.jpeg","isOnSale":1,"skuId":479410079988,"quantity":1000,"weight":0,"isOnsale":1,"skuExpansionPrice":"0","defaultQuantity":100,"groupSkuUnitPrice":"","normalSkuUnitPrice":"","groupTip":"下单立减2元","normalPrice":"47.9","endTime":0,"staticLimitQuantity":10,"initQuantity":0,"skuID":479410079988},{"marketPrice":0,"previewPriority":9,"goodsId":91240910838,"yellowEndTime":1601481599,"batchSn":"A0054VC-547046617653543628","limitQuantity":999999,"spec":"3159716265,2986814846","soldQuantity":0,"yellowLabel":"再售25338件后恢复¥56.9","specs":[{"spec_value_id":3159716265,"spec_key":"款式","spec_value":"【店长推荐】旗舰款","spec_key_id":1218},{"spec_value_id":2986814846,"spec_key":"套餐","spec_value":"清洗桶+2套拖把+10块布（赠刮刀）","spec_key_id":20711}],"price":0,"groupPrice":"45.19","oldGroupPrice":5690,"startTime":0,"attribute":"{}","thumbUrl":"https://t00img.yangkeduo.com/goods/images/2020-03-07/6c7c7eed1338b8dd831540ee6f6b3246.jpeg","isOnSale":1,"skuId":479338450381,"quantity":1000,"weight":0,"isOnsale":1,"skuExpansionPrice":"0","defaultQuantity":100,"groupSkuUnitPrice":"","normalSkuUnitPrice":"","groupTip":"下单立减2元","normalPrice":"62.9","endTime":0,"staticLimitQuantity":10,"initQuantity":0,"skuID":479338450381}],"content_images":[{"width":750,"url":"https://t00img.yangkeduo.com/goods/images/2020-03-07/8b205539-d61d-4e2b-9b91-bf7844bcf63b.jpg","height":1349},{"width":750,"url":"https://t00img.yangkeduo.com/goods/images/2020-03-07/a9501f0d-07f8-44d0-87f3-e390fbe2ce72.jpg","height":1306},{"width":790,"url":"https://t00img.yangkeduo.com/goods/images/2020-03-07/31da3f6f-3a9c-4f46-a282-99b58cf0db65.jpg","height":1200},{"width":750,"url":"https://t00img.yangkeduo.com/goods/images/2020-03-07/c7843eca-ddee-4053-b87f-3a591739a9e1.jpg","height":848},{"width":800,"url":"https://t00img.yangkeduo.com/goods/images/2020-03-07/e9fbc40c-5254-40cd-96e7-090ec234b0a4.jpg","height":1074},{"width":790,"url":"https://t00img.yangkeduo.com/goods/images/2020-03-07/c4abd939-ebb7-44d1-a462-6ad78f7d280e.jpg","height":1059},{"width":750,"url":"https://t00img.yangkeduo.com/goods/images/2020-03-07/888b1e68-af11-40ef-ad20-9e8972571d19.jpg","height":996},{"width":750,"url":"https://t00img.yangkeduo.com/goods/images/2020-03-07/9a67cefa-08c2-405e-8d33-83e7bb3dc8a5.jpg","height":1066}]}}';
        $mes = json_decode($res['content'],true);
        return $mes;

    }

    /*
     *  小红书商品处理
     *  @author xzx
     */
    public function get_item_redbook($data,$cates)
    {
        global $_W;
        $type = 'xhs';
        $goods = array();

        /*$spec_option['option'] = $data['options'];
        $spec_option['spec'] = $data['specs'];

        $goods = array_merge($this->get_spec_option($spec_option,$type));*/

        $goods['title'] = $data['title'];

        $goods = array_merge($goods,$this->get_thumb($data['thumbs'],$type));

        $goods['productprice'] = $data['price']['price'];

        $goods = array_merge($goods,$this->get_cates($cates));

        $goods['content'] = $this->get_content($data['content_images'],$type);

        return $this->save_goods($goods);

    }

    /*
     *  淘宝商品处理
     *  @author xzx
     */
    public function get_item_taobao($data,$cates)
    {
        global $_W;
        $type = 'tb';
        $goods = array();
        //处理规格
        $spec_option['option'] = $data['options'];
        $spec_option['spec'] = $data['specs'];

        $goods = array_merge($this->get_spec_option($spec_option,$type));
        //处理商品参数
        $goods['params']['title'] = array();
        $goods['params']['value'] = array();
        foreach($data['params'][0]['基本信息'] as $k=>$v){
            $key = array_keys($v);
            $value = array_values($v);
            array_push($goods['params']['title'],$key[0]);
            array_push($goods['params']['value'],$value[0]);
        }

        $goods['title'] = $data['title'];
        $goods['sales'] = $data['virtual_sales_count'];

        //获取最低价格
        $minprice = explode('-', $data['market_price_ange']);
        $minprice = $minprice[0];
        $goods['productprice'] = $minprice;

        //获取商品缩略图
        $goods = array_merge($goods,$this->get_thumb($data['thumbs'],$type));

        //处理商品分类
        $goods = array_merge($goods,$this->get_cates($cates));

        //处理商品详情
        $goods['content'] = $this->get_content($data['content'],$type);

        return $this->save_goods($goods);

    }

    /*
    *  拼多多商品处理
    *  @author xzx
    */
    public function get_item_pdd($data,$cates)
    {
        global $_W;
        $type = 'pdd';

        $goods = array();
        //处理商品规格
        $goods = array_merge($this->get_spec_option($data['options'],$type));
      
      

        //处理商品参数
        $goods['params']['title'] = array();
        $goods['params']['value'] = array();

        foreach($goods['spec'] as $title=>$value){
            array_push($goods['params']['title'],$value['title']);
            array_push($goods['params']['value'],$value['valueid']);
        }

      
        //获取商品缩略图
        $goods = array_merge($goods,$this->get_thumb($data['thumbs'],$type));

        //处理商品价格
        $goods['productprice'] = $data['price'];
        //处理商品详情
        $goods['content'] = $this->get_content($data['content_images'],$type);
        //处理商品分类
        $goods = array_merge($goods,$this->get_cates($cates));
        //处理商品标题
        $goods['title'] = $data['title'];



        return $this->save_goods($goods);

    }


    /*
     *  苏宁商品处理
     *  @author xzx
     */
    public function get_item_suning($data,$cates)
    {
        global $_W;
        $type = 'suning';


        $goods = array();



       /* $spec_option['option'] = $data['params'];
        $spec_option['spec'] = $data['options'];*/

        //苏宁没有SKU
        //$goods = array_merge($this->get_spec_option($spec_option,$type));


        $goods = array_merge($this->get_spec_option($data['options'],$type));

        //保存商品图片
        $goods = array_merge($goods,$this->get_thumb($data['thumbs']));

        //处理价格
        $goods['productprice'] = $data['price'];

        $goods['title'] = $data['title'];


        $goods['content'] = $this->get_content($data['content'],$type);

        /* //处理商品详情里的标签
         $content = str_replace('data-lazyload','src',$data['content']);
        $content = preg_replace('/href=\"https:\/\/item\.jd\.com\/\d+\.html/','',$content);
         $goods['content'] = $content;*/

        $goods = array_merge($goods,$this->get_cates($cates));

        return $this->save_goods($goods);
    }

    /*
     *  获取京东商品信息
     *  @author xzx
     */
    public function get_item_jd($data,$cates)
    {
        global $_W;
        $type = 'jd';

        $goods = array();
        //保存商品图片
        $goods = array_merge($goods,$this->get_thumb($data['thumbs']));

        //处理价格
        $goods['productprice'] = $data['price'];

        $goods['title'] = $data['title'];

        //处理参数
        $goods['params']['title'] = array();
        $goods['params']['value'] = array();
        foreach ($data['params'] as $v){
            foreach ($v['atts'] as $vv){
                array_push($goods['params']['title'],$vv['attName']);
                array_push($goods['params']['value'],$vv['vals'][0]);
            }
        }
        $goods['content'] = $this->get_content($data['content'],$type);

       /* //处理商品详情里的标签
        $content = str_replace('data-lazyload','src',$data['content']);
       $content = preg_replace('/href=\"https:\/\/item\.jd\.com\/\d+\.html/','',$content);
        $goods['content'] = $content;*/

        $goods = array_merge($goods,$this->get_cates($cates));

        return $this->save_goods($goods);

    }

    /*
     *  获取1688商品信息
     *  @author xzx
     */
    public function get_item_on688($data,$cates)
    {
        global $_W;
        $type = 'one688';


        $goods = array();
        $spec_option['option'] = $data['options'];
        $spec_option['spec'] = $data['specs'];

        $goods = array_merge($this->get_spec_option($spec_option,$type));


        //处理商品参数
        $goods['params']['title'] = array();
        $goods['params']['value'] = array();

        foreach($data['params'] as $k=>$v){
            $tit = explode(':',$v);
            array_push($goods['params']['title'],$tit[0]);
            array_push($goods['params']['value'],$tit[1]);
        }

        $goods['title'] = $data['title'];
        $goods['sales'] = $data['sales_count'];

        //获取最低价格
        $goods['productprice'] = $data['show_price_ranges'][0]['price'];

        //获取商品缩略图
        $goods = array_merge($goods,$this->get_thumb($data['thumbs'],$type));

        //处理商品分类
        $goods = array_merge($goods,$this->get_cates($cates));

        //处理商品详情

        $goods['content'] = $this->get_content($data['content_images'],$type);


        return $this->save_goods($goods);
    }


    /*
     *  保存商品信息
     *  @author xzx
     */
    function save_goods($data)
    {
        global $_W;

        $goods = array(
            'title' => $data['title'],
            'sales' => $data['sales'],
            'productprice' => $data['productprice'],
            'thumb' => $data['thumb'],
            'thumb_url' => $data['thumb_url'],
            'cates' => $data['cates'],
            'pcate' => $data['pcate'],
            'ccate' => $data['ccate'],
            'tcate' => $data['tcate'],
            'pcates' => $data['pcates'],
            'ccates' => $data['ccates'],
            'tcates' => $data['tcates'],
            'status' => 0,
            'deleted' => 0,
            'buylevels' => '',
            'showlevels' => '',
            'buygroups' => '',
            'showgroups' => '',
            'noticeopenid' => '',
            'storeids' => '',
            'newgoods' => 1,
            'createtime' => time(),
            'updatetime' => time(),
            'uniacid' => $_W['uniacid'],
            'content' => $data['content'],
            'hasoption' => empty($data['spec']) ? '0' : '1'
        );

        if (!empty($data)) {
            pdo_insert("ewei_shop_goods", $goods);

            $goodsid = pdo_insertid();
        }



        //商品参数处理
        $displayorder = 0;

        foreach ($data['params']['title'] as $kk => $vv){
            $d = array(
                "uniacid" => $_W['uniacid'],
                "goodsid" => $goodsid,
                "title" => $vv,
                "value" => $data['params']['value'][$kk],
                "displayorder" => $displayorder
            );
            $displayorder++;

            pdo_insert("ewei_shop_goods_param", $d);
        }

        //商品规格处理
        $orderspec = 0;
        foreach ($data['spec'] as $k=>&$v){
            $spec = array(
              'title' => $k,
              'goodsid' => $goodsid,
              'uniacid' => $_W['uniacid']
            );
            pdo_insert("ewei_shop_goods_spec", $spec);

            $specid = pdo_insertid();

            foreach ($v as $kkk=>&$vvv){
                $specitem = array(
                  'specid' => $specid,
                  'uniacid' => $_W['uniacid'],
                  'title' => $vvv['title'],
                  'show' => 1,
                  'displayorder' => $orderspec,
                  'valueid' => $vvv['valueid']
                );

                pdo_insert("ewei_shop_goods_spec_item", $specitem);

                $insertSpecId = pdo_insertid();
                $vvv['specid'] = $insertSpecId;

                $orderspec++;

            }
            unset($vvv);

        }
        unset($v);


        //商品规格组合入库
        $optionMap = array();
        foreach ($data['spec'] as $item){
            $optionMap  = array_merge($item, $optionMap);
        }
        $optionMap = array_column($optionMap, NULL, 'valueid');

        foreach ($data['option'] as &$v){

           $valueIdItems = explode('+',$v['valueid']);

           $valueIdStr = '';
           foreach ($valueIdItems as $valueItem){
               $valueIdStr .= $optionMap[$valueItem]['specid'].'_';
           }

           $v['valueid'] = $valueIdStr;
           $v['valueid'] = substr($v['valueid'],0,-1);

        }

        foreach ($data['option'] as $optionRow){
            $optionData = array(
              'uniacid' => $_W['uniacid'],
              'goodsid' => $goodsid,
              'title' => $optionRow['title'],
              'marketprice' => $optionRow['price'],
              'specs' => $optionRow['valueid']
            );
            pdo_insert("ewei_shop_goods_option", $optionData);

        }

        if (empty($goodsid)){
            return false;
        }else{
            return true;
        }


    }

    /*
    *  商品规格处理
    *  @author xzx
    */
    function get_spec_option($data,$type)
    {


        if ($type == 'pdd'){


            foreach ($data as $k=>$v){

               // foreach ($v as $kk=>$vv){

                    foreach ($v['specs'] as $kkk=>$vvv){
                        $option[$k]['title'] .= $vvv['spec_value'].'+';
                        $option[$k]['valueid'] .= $vvv['spec_value_id'].'+';

                        $option[$k]['price'] = $data[$k]['normalPrice'];


                        if (isset($spec[$vvv['spec_key']])){
                            $spec[$vvv['spec_key']][$vvv['spec_value_id']] = [
                                'title' => $vvv['spec_value'],
                                'valueid' => $vvv['spec_value_id'],
                            ];

                        } else {
                            $spec[$vvv['spec_key']][$vvv['spec_value_id']] = [
                                'title' => $vvv['spec_value'],
                                'valueid' => $vvv['spec_value_id'],
                            ];
                        }
                    }

                //}
                $option[$k]['title'] = substr($option[$k]['title'],0,-1);
                $option[$k]['valueid'] = substr($option[$k]['valueid'],0,-1);
            }

            foreach ($spec as &$row){
                $row = array_values($row);
            }

        }elseif ($type == 'tb'){


            $spec = array();
            $option = array();

            foreach ($data['spec'] as $v){
                foreach ($v['values'] as $vv){
                    $spec[$v['name']][] = [
                            'title' => $vv['name'],
                            'valueid' => $vv['vid']
                        ];

                }

            }

            unset($data['option'][0]);

            foreach ($data['option'] as $k=>&$v){
                
                $speId = explode(';',$v['propPath']);
                foreach ($speId as $vv){
                    $speIds = explode(':',$vv);
                    foreach ($spec as $row){
                        foreach ($row as $item){

                            if ($speIds[1] == $item['valueid']){
                                $option[$k]['title'] .= $item['title'].'+';
                                $option[$k]['valueid'] .= $item['valueid'].'+';
                                $option[$k]['price'] = $data['option'][$k]['price'];

                            }
                        }

                    }

                }
                $option[$k]['title'] = substr($option[$k]['title'],0,-1);
                $option[$k]['valueid'] = substr($option[$k]['valueid'],0,-1);

            }
            unset($v);

        }elseif ($type == 'suning'){

            $spec = array();
            foreach ($data['option'] as $k=>$v){
                foreach ($v as $kk=>$vv){
                    foreach ($vv as $kkk=>$vvv){

                        $spec[$kk][$kkk]['title'] = $vvv['characterValueDisplayName'];
                        $spec[$kk][$kkk]['valueid'] = $vvv['characterValueId'];

                    }
                }
            }
            $option = array();
            foreach ($data['spec'] as $k=>$v){
                //这层的K做下标，14种组合就是14个k
                foreach ($v as $kk=>$vv){

                }
            }

        }elseif ($type == 'one688'){
            $spec = array();
            foreach ($data['spec'] as $v){
                foreach ($v['value'] as $kk => $vv){
                    $spec[$v['prop']][$kk]['title'] = $vv['name'];
                    $spec[$v['prop']][$kk]['valueid'] = rand(100000,999999);

                }
            }

            $option = array();
               $countKey = 0;
               foreach ($data['option'] as $k=>$v){

                   $option[$countKey]['title'] = str_replace(';','+',$k);
                   $option[$countKey]['price'] = $v['price'];
                   $option[$countKey]['price'] = $v['price'];
                   foreach ($spec as $item){
                       foreach ($item as $row){

                            if (strstr($k,$row['title'])){
                                $option[$countKey]['valueid'] .= $row['valueid'].'+';
                            }
                       }

                   }
                   $option[$countKey]['valueid'] = substr($option[$countKey]['valueid'],0,-1);

                   $countKey++;
               }

        }elseif ($type == 'xhs'){

        }

        return ['spec' => $spec,'option' => $option];

    }

    /*
     *  商品首页图缩略图处理
     *  @author xzx
     */
    function get_thumb($pics,$type)
    {

        if ($type == 'tb'){
           foreach ($pics as &$v){
               $v = substr($v,2);
               $v = 'https://'.$v;
           }
           unset($v);
        }elseif ($type == 'xhs'){
            $img = array();
            foreach ($pics as $k => $v){
                $img[$k] = substr($v['thumbnail'],2);

                $img[$k] = 'https://'.$img[$k];
            }
            $pics = $img;


        }elseif ($type == 'pdd'){
        	 foreach ($pics as $k => $v){
                $pics[$k] =$v['url'];
            }
        
        }

        $piclen = count($pics);
        if ($piclen > 0) {

            $img = $this->save_image($pics[0], false);

            if (empty($img)) {
                $img = $pics[0];
            }
            $info =  getimagesize("../attachment/".$img);

            $srcFileExtImg=$info['mime'];
            if($srcFileExtImg=='image/x-ms-bmp') {
                $mig = $this->changeBMPtoJPG("../attachment/" . $img);
            }else{
                $mig = $img;
            }

            $data['thumb'] = $mig;
            //其他图片
            if ($piclen > 1) {
                for ($i = 1; $i < $piclen; $i++) {

                    $img = $this->save_image($pics[$i], false);
                    if (empty($img)) {
                        $img = $pics[$i];
                    }
                    $thumb_url[] = $img;
                }
            }
        }
        $mi = array();
        foreach ($thumb_url as $thumb_Info){
            $info =  getimagesize("../attachment/".$thumb_Info);
            $srcFileExt=$info['mime'];
            if($srcFileExt=='image/x-ms-bmp') {
                $mi[] = $this->changeBMPtoJPG("../attachment/" . $thumb_Info);
            }else{
                $mi[] =    $thumb_Info;
            }
        }
        $data['thumb_url'] = serialize($mi);

        return $data;
    }

    /*
     *  处理商品详情
     *  @author xzx
     */
    function get_content($data,$type)
    {

        if ($type == 'tb'){
            $pre = '/\/\/img\.(.*?)\.jpg/';
            preg_match_all($pre,$data,$pics);
            $pic = $pics[0];

            foreach ($pic as &$v){
                $v = substr($v,2);
                $v = 'https://'.$v;
            }
            unset($v);

        }elseif ($type == 'jd'){
            
            $pre = '/background-image:url\((.*?)\)/i';
            preg_match_all($pre,$data,$pics);

            if (empty($pics[0])) {
                $pattern = '/data-lazyload=[\"|\'](.*?)[\"|\']/i';
                preg_match_all($pattern, $data, $pics);
            }
            $pic = $pics[1];


            foreach ($pic as &$v){
                if(strstr($v, 'http')){
                    continue;
                }else{
                    $v = 'https:'.$v;
                }
            }
            unset($v);


        }elseif ($type == 'one688'){
            $pic = $data;
        }elseif ($type == 'suning'){
            $pre = '/src2=[\"|\'](.*?)[\"|\']/i';
            preg_match_all($pre,$data,$pics);
            $pic = $pics[1];
        }elseif ($type == 'pdd'){
            $pic = array();
            foreach ($data as $k=>$v){
                $pic[$k] = $v['url'];
            }

        }elseif ($type == 'xhs'){
            $pic = array();
            foreach ($data as $k=>$v){

                $pic[$k] = 'https://'.substr($v['link'],2);
            }
        }
        $piclen = count($pic);

        if ($piclen > 0) {
            if ($piclen > 1) {
                for ($i = 0; $i < $piclen; $i++) {
                    $img = $this->save_image($pic[$i], false);
                    if (empty($img)) {
                        $img = $pics[$i];
                    }
                    $thumb_url[] = $img;
                }
            }
        }
        $cont = '';
        foreach ($thumb_url as $v){
            $cont .= '<p><img src="'.$v.'"alt="timg.jpg" style="max-width: 100%"/></p>';
        }

       return $cont;
    }

    /*
     *  处理商品分类
     *  @author xzx
     */
    public function get_cates($data)
    {
        //处理商品分类
        $pcates = array();
        $ccates = array();
        $tcates = array();
        $pcateid = 0;
        $ccateid = 0;
        $tcateid = 0;

        if (is_array($data)) {

            foreach ($data as $key => $cid) {

                $c = pdo_fetch('select level from ' . tablename('ewei_shop_category') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $cid, ':uniacid' => $_W['uniacid']));

                if ($c['level'] == 1) { //一级
                    $pcates[] = $cid;
                } else if ($c['level'] == 2) {  //二级
                    $ccates[] = $cid;
                } else if ($c['level'] == 3) {  //三级
                    $tcates[] = $cid;
                }

                if ($key == 0) {
                    //兼容 1.x
                    if ($c['level'] == 1) { //一级
                        $pcateid = $cid;
                    } else if ($c['level'] == 2) {
                        $crow = pdo_fetch('select parentid from ' . tablename('ewei_shop_category') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $cid, ':uniacid' => $_W['uniacid']));
                        $pcateid = $crow['parentid'];
                        $ccateid = $cid;

                    } else if ($c['level'] == 3) {
                        $tcateid = $cid;
                        $tcate = pdo_fetch('select id,parentid from ' . tablename('ewei_shop_category') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $cid, ':uniacid' => $_W['uniacid']));
                        $ccateid = $tcate['parentid'];
                        $ccate = pdo_fetch('select id,parentid from ' . tablename('ewei_shop_category') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $ccateid, ':uniacid' => $_W['uniacid']));
                        $pcateid = $ccate['parentid'];
                    }
                }
            }
        }

        $cates['pcate'] = $pcateid;
        $cates['ccate'] = $ccateid;
        $cates['tcate'] = $tcateid;
        if (!empty($data)) {
            $cates['cates'] = implode(',', $data);

        }

        $cates['pcates'] = implode(',', $pcates);
        $cates['ccates'] = implode(',', $ccates);
        $cates['tcates'] = implode(',', $tcates);


        return $cates;
    }


    /*
     *  保存商品图片
     *  @author xzx
     */
    function save_image($url, $iscontent)
    {
        global $_W;


        load()->func('communication');
        $ext = strrchr($url, ".");


        if ($ext != ".jpeg" && $ext != ".gif" && $ext != ".jpg" && $ext != ".png") {
            return $url;
        }
        if (trim($url) == '') {
            return $url;
        }

        $filename = random(32) . $ext;

        $save_dir = ATTACHMENT_ROOT . 'images/' . $_W['uniacid'] . '/' . date('Y') . '/' . date('m') . '/';


        //创建保存目录

        if (!file_exists($save_dir) && !mkdir($save_dir, 0777, true)) {
            return $url;
        }


        $img = ihttp_get($url);
        if (is_error($img)) {
            return "";
        }


        $img = $img['content'];
        if (strlen($img) != 0) {
            file_put_contents($save_dir . $filename, $img);
            $imgdir = 'images/' . $_W['uniacid'] . '/' . date('Y') . '/' . date('m') . '/';
            $saveurl = save_media($imgdir . $filename, true);
            return $saveurl;
        } else {
            return '';
        }

    }



    //保存淘宝CSV商品
    function save_taobaocsv_goods($item = array(), $merchid = 0)
    {

        global $_W;
        $data = array(
            "uniacid" => $_W['uniacid'],
            "merchid" => $merchid,
            "catch_source" => 'taobaocsv',
            "catch_id" => '',
            "catch_url" => '',
            "title" => $item['title'],
            "total" => $item['total'],
            "marketprice" => $item['marketprice'],
            "pcate" => '',
            "ccate" => '',
            "tcate" => '',
            "goodssn" =>$item['goodssn'],
            "cates" => '',
            "sales" => 0,
            "createtime" => time(),
            "updatetime" => time(),
            'hasoption' => 0,
            'status' => 0,
            'deleted' => 0,
            'buylevels' => '',
            'showlevels' => '',
            'buygroups' => '',
            'showgroups' => '',
            'noticeopenid' => '',
            'storeids' => '',
            'minprice' => $item['marketprice'],
            'maxprice' => $item['marketprice'],
            'merchsale' => $item['merchid'] == 0 ? 0 : 1,

        );
        if (empty($item['merchid'])) {
            $data['discounts'] = '{"type":"0","default":"","default_pay":""}';
        }
        if (!empty($merchid)) {
            if (empty($_W['merch_user']['goodschecked'])) {
                $data['checked'] = 1;
            } else {
                $data['checked'] = 0;
            }
        }

        //图片
        $thumb_url = array();
        $pics = $item['pics'];
        $piclen = count($pics);

        if ($piclen > 0) {

            $data['thumb'] = $this->save_image($pics[0], false);

            //其他图片
            if ($piclen > 1) {
                for ($i = 1; $i < $piclen; $i++) {
                    $img = $this->save_image($pics[$i], false);
                    $thumb_url[] = $img;
                }
            }
        }
        $data['thumb_url'] = serialize($thumb_url);

        pdo_insert("ewei_shop_goods", $data);
        $goodsid = pdo_insertid();

        //保存详情
        $content = $item['content'];
        preg_match_all("/<img.*?src=[\\\'| \\\"](.*?(?:[\.gif|\.jpg]?))[\\\'|\\\"].*?[\/]?>/", $content, $imgs);

        if (isset($imgs[1])) {
            foreach ($imgs[1] as $img) {

                $catchimg = $img;
                if (substr($catchimg, 0, 2) == "//") {
                    $img = "http://" . substr($img, 2);
                }
                $im = array(
                    "catchimg" => $catchimg,
                    "system" => $this->save_image($img, true)
                );

                $images[] = $im;
            }
        }
        $html = $content;
        //$html = iconv("GBK", "UTF-8", $html[1]);

        if (isset($images)) {
            foreach ($images as $img) {
                if (!empty($img['system'])) {
                    $html = str_replace($img['catchimg'], $img['system'], $html);
                }
            }
        }
        $html = m('common')->html_to_images($html);

        if(isset($images[0])) {
            $d['thumb_url'] = serialize($images[0]);
            $d['thumb'] = $images[0]['catchimg'];
        }
        $d['content'] = $html;
        pdo_update("ewei_shop_goods", $d, array("id" => $goodsid));

        return array("result" => '1', "goodsid" => $goodsid);
    }

}
