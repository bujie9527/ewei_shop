<?php
/*

 * @ PHP 5.6
 * @ Decoder version: 1.0.4
 * @ Release: 02/06/2020
 *
 * @ ZendGuard Decoder PHP 5.6
 */

if (!defined("IN_IA")) {
    exit("Access Denied");
}
require_once EWEI_SHOPV2_PLUGIN . "app/core/page_mobile.php";
class Index_EweiShopV2Page extends AppMobilePage
{
    /**
     * @var $model Transfer_AccountModel
     */
    public $model = NULL;
    public $gpc = NULL;
    public $w = NULL;
    public function __construct()
    {
        global $_W;
        global $_GPC;
        $this->model = p("transfer_account");
        $this->w = $_W;
        $this->gpc = $_GPC;
        parent::__construct();
    }
    /**
     * 获取设置
     * @return false|float|int|mixed|Services_JSON_Error|string
     */
    public function getSet()
    {
        if (p("transfer_account")) {
            $set = p("transfer_account")->getSet();
            $set["bg"] = tomedia($set["bg"]);
            return app_json(array("set" => $set));
        }
        return app_error(-1, "无插件权限");
    }
    /**
     * 获取收款方openid
     * @return false|float|int|mixed|Services_JSON_Error|string
     */
    public function getToUser()
    {
        $gpc = $this->gpc;
        $w = $this->w;
        $uid = $gpc["uid"];
        if (empty($uid)) {
            return app_json(-1, "uid不可为空");
        }
        if ($this->member["id"] == $uid) {
            return app_error(-1, "不能转给自己");
        }
        $user = m("member")->getMember($uid);
        if (empty($user)) {
            return app_error(-1, "非法uid,用户不存在");
        }
        if (!empty($user["mobile"])) {
            $user["mobile"] = secret($user["mobile"], false, 3, 4);
        }
        return app_json(array("user" => $user));
    }
    /**
     * 获取当前用户
     * @return false|float|int|mixed|Services_JSON_Error|string
     */
    public function getCurrentMember()
    {
        $openid = $this->w["openid"];
        $user = m("member")->getMember($openid);
        return app_json(1, array("total" => $user[$this->gpc["type"]]));
    }
    /**
     * 转账动作
     * @return false|float|int|mixed|Services_JSON_Error|string
     */
    public function transfer()
    {
        global $_W;
        global $_GPC;
        $type = $this->gpc["type"];
        $money = $this->gpc["total"];
        $member = m("member")->getMember($_W["openid"]);
        if ($member["id"] == $_GPC["to_userid"]) {
            return app_error(-1, "不能转给自己");
        }
        if ($type == 1) {
            $type = "credit1";
        } else {
            $type = "credit2";
        }
        $transfer_result = p("transfer_account")->transfer($type, $money, $member["id"], $_GPC["to_userid"]);
        if (true === is_array($transfer_result) && $transfer_result["code"] <= 0) {
            return app_error($transfer_result["code"], $transfer_result["msg"]);
        }
        return app_json("操作成功");
    }
    /**
     * 转账记录
     * @author zhurunfeng
     */
    public function transferLog()
    {
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC["page"]));
        $psize = 10;
        $listType = $_GPC["listType"];
        $condition = "  and log.uniacid=:uniacid and log.credit_type=:credit_type";
        $keyword = $_GPC["keyword"];
        $params = array(":uniacid" => $_W["uniacid"], ":credit_type" => $_GPC["type"] == 1 ? "credit1" : "credit2");
        if ($listType == "in") {
            $condition .= " and to_user=:to_user ";
            $params[":to_user"] = $_W["openid"];
            $leftJoin = " left join " . tablename("ewei_shop_member") . " m on m.openid=log.from_user";
        } else {
            $condition .= " and from_user=:from_user ";
            $params[":from_user"] = $_W["openid"];
            $leftJoin = " left join " . tablename("ewei_shop_member") . " m on m.openid=log.to_user";
        }
        if (!empty($keyword)) {
            $condition .= " and (m.nickname like :keyword or m.id like :keyword) ";
            $params[":keyword"] = "%" . $keyword . "%";
        }
        $list = pdo_fetchall("select log.*,m.nickname, m.id uid from " . tablename("ewei_shop_transfer_log") . " log " . $leftJoin . "  where 1 " . $condition . " order by log.id desc LIMIT " . ($pindex - 1) * $psize . "," . $psize, $params);
        $total = pdo_fetchcolumn("select count(*) from " . tablename("ewei_shop_transfer_log") . " log " . $leftJoin . " where 1 " . $condition, $params);
        foreach ($list as &$item) {
            $item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
            $item["surplus"] = $listType == "in" ? $item["to_surplus"] : $item["from_surplus"];
            if ($listType == "in") {
                $item["transfer_text"] = "转出方";
                $item["time_text"] = "转入时间";
            } else {
                $item["transfer_text"] = "转入方";
                $item["time_text"] = "转出时间";
            }
            if ($_GPC["type"] == "credit1") {
                $item["credit_text"] = "积分";
            } else {
                $item["credit_text"] = "余额";
            }
        }
        return app_json(array("list" => $list, "total" => $total, "pagesize" => $psize));
    }
}

?>