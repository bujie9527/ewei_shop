define(['core', 'tpl'], function (core, tpl) {
    var modal = {page: 1, type: 0, loaded: false};
    modal.init = function (params) {
        if(modal.loaded) {
            return;
        }
        modal.loaded = true;

        $('.container').empty();
        modal.type = params.type;
        modal.listType = params.listType
        FoxUI.tab({
            container: $('#tab'), handlers: {
                tab1: function () {
                    modal.changeTab('in')
                    $(" input[ name='keywords' ] ").val('')
                }, tab2: function () {
                    modal.changeTab('out')
                    $(" input[ name='keywords' ] ").val('')
                }
            }
        });
        $('.fui-content').infinite({
            onLoading: function () {
                modal.getList()
            }
        });
        if (modal.page == 1) {
            if ($(".mitem").length <= 0) {
                $(".container").html('');
                modal.getList();
            }
        }

        // 搜索
        $(".keywords").keypress(function (e) {
            if (e.which == 13) {
                var value = $(" input[ name='keywords' ] ").val()
                if (value == '') {
                    FoxUI.alert('请输入关键词')
                } else {
                    modal.getList(value)
                }

            }
        });
    };
    modal.changeTab = function (listType) {
        $('.container').html(''), $('.infinite-loading').show(), $('.content-empty').hide(), modal.page = 1, modal.listType = listType, modal.getList()
    };
    modal.getList = function (keyword = '') {
        core.json('transfer_account/getList', {page: modal.page, type: modal.type, listType: modal.listType, keyword: keyword}, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.container').hide();
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.container').show();
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }

            core.tpl('.container', 'tpl_transfer_log_list', result, modal.page > 1);
            // if($('.mitem').length > result.pagesize) {
                modal.page++;
            // }
        })
    };
    return modal
});