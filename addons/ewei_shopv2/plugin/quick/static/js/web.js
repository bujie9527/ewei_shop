define(['jquery.ui', '../../../../static/js/web/goods_selector.js'], function (ui, gSelector) {
    var modal = {
        default: {
            template: 1,
            style: [{
                catebg: '#f8f8f8',
                catecolor: '#666666',
                cateactivebg: '#ffffff',
                cateactivecolor: '#ff5555',
                goodsbg: '#ffffff',
                goodstitle: '#333333',
                goodssubtitle: '#9f9f9f',
                goodsprice: '#ff6500',
                goodssales: '#888888',
                goodscart: '#ff5555',
                righttitle: '#666666',
                righttitlebg: '#f8f8f8',
                righttitleborder: '#efefef',
                footerbg: '#474749',
                footertext: '#ffffff',
                footercart: '#ff5555',
                footercarticon: '#ffffff',
                footerbtn: '#ff9d55',
                footerbtntext: '#ffffff'
            }, {
                shopstyle: '1',
                logostyle: '',
                notice: '1',
                noticenum: '5',
                hidelogo: '0',
                shopbg: '../addons/ewei_shopv2/plugin/quick/static/images/shop-1.jpg',
                namecolor: '#000000',
                shoplogo: '',
                shopname: '',
                menubg: '#ffffff',
                menuicon: '#ff5555',
                menutext: '#141414',
                noticeicon: '#f19b59',
                noticecolor: '#676a6c',
                catebg: '#e6e6e6',
                catecolor: '#676a6c',
                cateactivebg: '#ffffff',
                cateactivecolor: '#ff5555',
                goodsbg: '#ffffff',
                goodstitile: '#333333',
                goodssubtitile: '#9f9f9f',
                goodsprice: '#ff5555',
                goodssales: '#cbcbcb',
                goodscart: '#ff5555',
                righttitle: '#666666'
            },],
            datas: [{
                title: '分组名称',
                icon: '',
                desc: '这是介绍',
                datatype: 0,
                catename: '',
                cateid: 0,
                groupname: '',
                groupid: 0,
                data: [],
                goodsids: [],
                goodssort: 0,
                goodsnum: 5
            }],
            cartdata: 0,
            showadv: 0,
            advs: [{
                imgurl: "../addons/ewei_shopv2/plugin/quick/static/images/banner-1.jpg",
                linkurl: ""
            }, {imgurl: "../addons/ewei_shopv2/plugin/quick/static/images/banner-2.jpg", linkurl: "",}],
            notices: [{title: "公告一标题", linkurl: ""}, {title: "公告二标题", linkurl: "",}],
            shopmenu: [{text: "按钮名称", icon: "icon-shop", linkurl: ""}, {
                text: "按钮名称",
                icon: "icon-shop",
                linkurl: ""
            }, {text: "按钮名称", icon: "icon-shop", linkurl: ""}, {text: "按钮名称", icon: "icon-shop", linkurl: "",}],
            dataIndex: 0,
            selected: 2
        },
        default_goods: {
            thumb: '../addons/ewei_shopv2/plugin/quick/static/images/goods-1.jpg',
            title: '商品标题',
            gid: 0,
            price: '9.99',
            total: '10',
            sales: '0'
        }
    };
    modal.init = function (params) {
        window.tpl = params.tpl;
        modal.attachurl = params.attachurl;
        modal.merchid = params.merchid;
        modal.page = params.page;
        modal.type = params.type;

        if(modal.type == 1){
            modal.default.shopmenu = [{text: "按钮名称", icon: "icox-shop", linkurl: ""}, {
                text: "按钮名称",
                icon: "icox-shop",
                linkurl: ""
            }, {text: "按钮名称", icon: "icox-shop", linkurl: ""}, {text: "按钮名称", icon: "icox-shop", linkurl: "",}]
        }
        modal.diymenu = params.diymenu;
        if (!modal.page) {
            modal.page = $.extend(true, {}, modal.default);
        }
        modal.page.selected = 0;
        modal.page.dataIndex = 0;
        modal.initTpl();
        modal.initShow();
        modal.initEditor();
        modal.initClick();
    };
    modal.initTpl = function () {
        tpl.helper("imgsrc", function (src) {
            if (typeof src != 'string') {
                return ''
            }
            if (src.indexOf('http://') == 0 || src.indexOf('https://') == 0 || src.indexOf('../addons/ewei_shopv2/') == 0) {
                return src
            } else if (src.indexOf('images/') == 0 || src.indexOf('audios/') == 0) {
                return modal.attachurl + src
            }
        });
        tpl.helper("count", function (data) {
            if (!$.isArray(data)) {
                return 0;
            }
            return modal.length(data)
        });
    };
    modal.length = function (json) {
        if (typeof(json) === 'undefined') {
            return 0
        }
        var jsonlen = 0;
        for (var item in json) {
            jsonlen++
        }
        return jsonlen
    };
    modal.initShow = function () {
        var tempName = modal.page.template == '1' ? 'show_temp_1' : 'show_temp_0';
        var item = $.extend(true, {}, modal.page);
        item.style = modal.page.style[modal.page.template];
        var html = tpl(tempName, item);
        $("#phone").html(html);
        modal.initSortable();
    };
    modal.initEditor = function () {
        var tempName = modal.page.template == '1' ? 'show_temp_1' : 'show_temp_0';
        var item = $.extend(true, {}, modal.page);
        item.style = modal.page.style[modal.page.template];
        item.diymenu_data = modal.diymenu||[];
        var html = tpl("show_editor", item);
        $("#editor").html(html);
        $("#editor").find(".bind").bind('input propertychange change', function () {
            var _this = $(this);
            var element = _this.data('bind-element');
            var parent = _this.data('bind-parent');
            var bind = _this.data('bind');
            var init = _this.data('bind-init');
            var value = _this.val();
            var tag = this.tagName;
            if (tag == 'INPUT') {
                var type = _this.attr('type');
                if (type == 'checkbox') {
                } else {
                }
            } else if (tag == 'SELECT') {
                value = _this.find('option:selected').val();
            }

            if (parent) {
                if (parent == 'style') {
                    var styleIndex = modal.page.template;
                    modal.page[parent][styleIndex][bind] = value;
                } else if (parent == 'shopmenu' || parent == 'advs' || parent == 'notices') {
                    var childIndex = _this.closest(".item").data("index");
                    modal.page[parent][childIndex][bind] = value;
                } else if (parent == 'datas') {
                    var childIndex = modal.page.dataIndex;
                    if (!modal.page[parent][childIndex]) {
                        return;
                    }
                    modal.page[parent][childIndex][bind] = value;
                } else {
                    modal.page[parent][bind] = value;
                }
            } else {
                console.warn(bind);
                modal.page[bind] = value;
            }

            if($(element).length > 0) {
                $(element).text(value);
            }

            console.log(modal.page);

            modal.initShow();
            if (init) {
                modal.initEditor();
            }
        });
        modal.initSortableChild();
    };
    modal.initSortableChild = function () {
        $(".diy-editor .form-items .inner").sortable({
            opacity: 0.8,
            placeholder: "highlight",
            items: '.item',
            revert: 100,
            scroll: false,
            cancel: '.goods-selector,input,select,.btn,.btn-del,.three',
            start: function (event, ui) {
                var height = ui.item.height();
                $(".highlight").css({"height": height + 22 + "px"});
                $(".highlight").html('<div><i class="fa fa-plus"></i> 放置此处</div>');
                $(".highlight div").css({"line-height": height + 16 + "px"})
            },
            update: function (event, ui) {
                var childType = ui.item.closest(".form-items").data('type');
                modal.sortChildItems(childType)
            }
        })
    };
    modal.sortChildItems = function (type) {
        if (!type) {
            return;
        }
        var index = modal.page.dataIndex;
        var child = modal.page[type];
        if (!child) {
            return;
        }
        var newData = [];
        $("#form-items-" + type).find(".item").each(function (i) {
            var childIndex = $(this).data('index');
            if (type == 'datas') {
                var item = modal.page.datas[index].data[childIndex];
            } else {
                var item = modal.page[type][childIndex];
            }
            if (item) {
                newData[i] = item;
            }
        });

        if (type == 'datas') {
            modal.page.datas[index].data = newData;
        } else {
            modal.page[type] = newData;
        }
        modal.setGids(index);
        modal.initEditor();
        modal.initShow();
    };
    modal.initClick = function () {
        $(document).on('click', '.diy-editor .title', function () {
            var editor = $(this).closest(".diy-editor");
            if (!editor.hasClass("active")) {
                $(".diy-editor").find('.editor-body').slideUp();
                $(".diy-editor").removeClass("active");
                editor.find('.editor-body').stop(true, false).slideDown();
                editor.addClass('active');
            }
            modal.page.selected = parseInt(editor.data('id'));
        });
        $(document).on('click', '.btn-add', function () {
            var max = $(this).closest('.form-items').data('max');
            var type = $(this).data('type');
            if (type) {
                if (type == 'datas') {
                    modal.childid = 'new';
                    var gUrl = modal.merchid ?  biz.url('goods/goods_selector', null, modal.merchid): '';
                    gSelector.open('callbackGoods', 'quick', modal.merchid, null, gUrl,null,1);
                    /*
                    $(this).attr({
                        'id': 'goods_selector',
                        'data-url': biz.url('goods/query', null, modal.merchid),
                        'data-callback': 'callbackGoods'
                    });
                    biz.selector.select({name: 'goods'});*/
                    return;
                } else {
                    var newChild = $.extend(true, {}, modal.default[type][0]);
                    if (!modal.page[type]) {
                        modal.page[type] = [];
                    }
                    if (modal.page[type].length >= max) {
                        tip.msgbox.err('此元素最大添加 ' + max + ' 个');
                        return;
                    }
                    modal.page[type].push(newChild);
                }
            } else {
                var newChild = $.extend(true, {}, modal.default.datas[0]);
                if (!modal.page.datas) {
                    modal.page.datas = [];
                }
                modal.page.datas.push(newChild);
                modal.page.dataIndex = modal.page.datas.length - 1;
            }
            modal.initShow();
            modal.initEditor();
        });
        $(document).on('click', '.btn-del-child', function () {
            var type = $(this).closest('.form-items').data('type');
            var min = $(this).closest('.form-items').data('min');
            var index = $(this).closest('.item').data('index');
            if (type == 'datas') {
                var dataIndex = modal.page.dataIndex;
                var item = modal.page.datas[dataIndex].data;
            } else {
                var item = modal.page[type];
            }
            if (!item || item.length <= 0) {
                return;
            }
            if (item.length <= min) {
                tip.msgbox.err('此元素最少保留 ' + min + ' 个');
                return;
            }
            var child = item[index];
            if (!child) {
                return;
            }
            tip.confirm("确定删除吗", function () {
                if (type == 'datas') {
                    modal.page.datas[dataIndex].data.splice(index, 1);
                } else {
                    modal.page[type].splice(index, 1);
                }
                modal.setGids(modal.page.dataIndex);
                modal.initShow();
                modal.initEditor();
            });
        });
        $(document).on('click', '.btn-del-item', function () {
            var index = modal.page.dataIndex;
            if (!modal.page.datas || modal.page.datas.length < 1) {
                return;
            }
            if (modal.page.datas.length == 1) {
                tip.msgbox.err('最少保留 1 个分组');
                return;
            }
            var item = modal.page.datas[index];
            if (!item) {
                return;
            }
            tip.confirm("确定删除吗", function () {
                modal.page.datas.splice(index, 1);
                if (modal.page.datas[index]) {
                    modal.page.dataIndex = index;
                } else {
                    modal.page.dataIndex = index - 1;
                }
                modal.initShow();
                modal.initEditor();
            });
        });
        $(document).on('click', '.diy-editor .goods-selector', function () {
            modal.childid = $(this).closest(".item").data('index');
            var gUrl = modal.merchid?  biz.url('goods/goods_selector', null, modal.merchid): '';
            gSelector.open('callbackGoods', 'quick', modal.merchid, null, gUrl);
            /*
            $(this).attr({
                'id': 'goods_selector',
                'data-url': biz.url('goods/query', null, modal.merchid),
                'data-callback': 'callbackGoods'
            });
            biz.selector.select({name: 'goods'});*/
        });
        $(document).on('click', '.category-selector', function () {
            $(this).attr({
                'id': 'category_selector',
                'data-url': biz.url('goods/category/query', null, modal.merchid),
                'data-callback': 'callbackCategory'
            });
            biz.selector.select({name: 'category'})
        });
        $(document).on('click', '.group-selector', function () {
            $(this).attr({
                'id': 'group_selector',
                'data-url': biz.url('goods/group/query', null, modal.merchid),
                'data-callback': 'callbackGroup'
            });
            biz.selector.select({name: 'group'})
        });
        $("#btn-save").unbind('click').click(function () {
            var title = $.trim($("#name").val());
            var type = $("input[name='type']").val();
            if(type == '1' ) {
                if (!title || title == '') {
                    tip.msgbox.err("请先填写页面名称");
                    $('.diy-editor[data-id="0"] .title').trigger('click');
                    $('#name').focus();
                    return false;
                }
            }
            if (modal.page) {
                var datas = JSON.stringify(modal.page);
                $("#datas").val(datas);
            }
        })
    };
    modal.addItem = function () {
        var newItem = $.extend(true, {}, modal.default[0]);
        modal.data.push(newItem);
        var index = modal.data.length - 1;
        modal.selected = index;
        modal.initShow();
        modal.initEditor();
    };
    modal.initSortable = function () {
        $("#list").sortable({
            opacity: 0.8,
            placeholder: "highlight",
            items: '.nav',
            revert: 100,
            scroll: false,
            start: function (event, ui) {
                var height = ui.item.height();
                $(".highlight").css({"height": height + "px"});
                $(".highlight").html('<div><i class="fa fa-plus"></i> 放置此处</div>');
                $(".highlight div").css({"line-height": height - 4 + "px"})
            },
            update: function (event, ui) {
                modal.sortItems()
            }
        });
        $("#list").disableSelection();
        $(document).on('mousedown', "#list .nav", function () {
            var index = $(this).data('index');
            if (!Number(index)) {
                index = 0;
            }
            if (modal.page.dataIndex == index) {
                return;
            }
            modal.page.dataIndex = index;
            modal.page.selected = 4;
            modal.initShow();
            modal.initEditor();
        });
    };
    modal.sortItems = function () {
        var index = modal.page.dataIndex;
        var newData = [];
        $("#list .nav").each(function (i) {
            var newIndex = $(this).data('index');
            var item = modal.page.datas[newIndex];
            if (item) {
                newData[i] = item;
            }
            if (newIndex == index) {
                modal.page.dataIndex = i;
            }
        });
        modal.page.datas = newData;
        modal.initShow();
    };
    modal.setGids = function (index) {
        var item = modal.page.datas[index];
        if (!item) {
            return;
        }
        if (!item.data) {
            modal.page.datas[index].goodsids = [];
            return;
        }
        var goodsids = [];
        $.each(modal.page.datas[index].data, function (i, child) {
            var gid = parseInt(child.gid);
            if ($.inArray(gid, goodsids)) {
                goodsids.push(gid);
            }
        });
        modal.page.datas[index].goodsids = goodsids;
    };
    modal.callbackGoods = function (ret) {
        if (!ret) {
            tip.msgbox.err("回调数据错误，请重试！");
            return
        }
        var index = modal.page.dataIndex;
        var childid = modal.childid;
        if (childid == 'new') {
            var newChild = $.extend(true, {}, modal.default_goods);
            newChild.gid = ret.id;
            newChild.title = ret.title;
            newChild.price = ret.minprice;
            newChild.thumb = ret.thumb;
            newChild.total = ret.total;
            newChild.sales = ret.sales;
            var index = modal.page.dataIndex;
            if (index < 0) {
                return;
            }
            var item = modal.page.datas[index];
            if (!item) {
                return;
            }
            if (!item.data) {
                modal.page.datas[index].data = [];
            }

            if(typeof modal.page.datas[index].data == 'object' && modal.page.datas[index].data ) {
                try {
                    var tmp_check =  Array.from(modal.page.datas[index].data);
                    modal.page.datas[index].data = tmp_check
                }
                catch (e)
                {
                    window.location.href = window.location.href;
                    return ;
                }
            }
            modal.page.datas[index].data.push(newChild);
            modal.setGids(index);
            modal.initShow();
            modal.initEditor();
            return;
        } else if (childid < 0) {
            return
        }
        if (modal.page.datas[index]) {
            if (!modal.page.datas[index].data) {
                modal.page.datas[index].data = [];
            }
            modal.page.datas[index].data[childid] = {
                gid: ret.id,
                title: ret.title,
                price: ret.minprice,
                thumb: ret.thumb,
                total: ret.total,
                sales: ret.sales
            };
            modal.setGids(index);
            modal.initShow();
            modal.initEditor();
        }
    };
    modal.callbackCategory = function (ret) {
        if (!ret) {
            tip.msgbox.err("回调数据错误，请重试！");
            return
        }
        var index = modal.page.dataIndex;
        if (modal.page.datas[index]) {
            modal.page.datas[index].catename = ret.name;
            modal.page.datas[index].cateid = ret.id;
            modal.page.datas[index].groupname = '';
            modal.page.datas[index].groupid = '';
            modal.initShow();
            modal.initEditor();
        }
    };
    modal.callbackGroup = function (ret) {
        if (!ret) {
            tip.msgbox.err("回调数据错误，请重试！");
            return
        }
        var index = modal.page.dataIndex;
        if (modal.page.datas[index]) {
            modal.page.datas[index].groupname = ret.name;
            modal.page.datas[index].groupid = ret.id;
            modal.page.datas[index].catename = '';
            modal.page.datas[index].cateid = '';
            modal.initShow();
            modal.initEditor();
        }
    };
    return modal
});