define(['core', 'tpl'], function (core, tpl) {
    var modal = {page: 1, cate: '', loaded: false};
    modal.init = function (params) {

        if(modal.loaded) {
            return;
        }
        modal.loaded = true;


        $('#container').empty();
        modal.page = 1;
        $("#cateTab a").click(function () {
            var cate = $(this).data('cate');
            modal.cate = cate;
            modal.page = 1;
            $(this).addClass('active').siblings().removeClass('active');
            FoxUI.loader.show('mini');
            $("#container").html('');
            modal.getList(modal.cate)
        });
        $('.fui-content').infinite({
            onLoading: function () {
                modal.getList(modal.cate)
            }
        });
        if (modal.page == 1) {
            if ($(".coupon-item").length <= 0) {
                $("#container").html('');
                modal.getList(modal.cate);
            }
        }
        $("#changelot").click(function () {
            modal.getrm()
        })
    };
    modal.getList = function (cateid) {
        core.json('sale/coupon/my/getlist', {page: modal.page, cate: cateid}, function (ret) {
            $('.infinite-loading').hide();
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            FoxUI.loader.hide();
            core.tpl('#container', 'tpl_list_coupon_my', result, modal.page > 1);
            if($('.coupon-item').length < result.total) {
                modal.page++;
            }
        })
    };
    modal.getrm = function () {
        core.json('sale/coupon/detail/recommand', {}, function (r) {
            if (r.result.list.list.length < 1) {
                $("#rmgoods").html("<center>暂时没有同店推荐</center>")
            } else {
                core.tpl('#rmgoods', 'tpl_list_rmgoods', r.result.list)
            }
        }, true, true)
    };
    return modal
});