define(['jquery', 'jquery.gcjs', 'foxui'], function ($, gc, FoxUI) {
    var modal = {};
    modal.formatSeconds = function (value) {
        var theTime = parseInt(value);
        var theTime1 = 0;
        var theTime2 = 0;
        if (theTime > 60) {
            theTime1 = parseInt(theTime / 60);
            theTime = parseInt(theTime % 60);
            if (theTime1 > 60) {
                theTime2 = parseInt(theTime1 / 60);
                theTime1 = parseInt(theTime1 % 60)
            }
        }
        return {
            'hour': theTime2 < 10 ? '0' + theTime2 : theTime2,
            'min': theTime1 < 10 ? '0' + theTime1 : theTime1,
            'sec': theTime < 10 ? '0' + theTime : theTime
        }
    };

    modal.useEndtime = false;

    modal.setTimer = function (obj) {
        var lasttime = obj.attr('data-timer-lasttime') || 0;

        if (modal.useEndtime){
            var $datas = obj.data('time-end') || '', $datas = $datas.split('|');
        }else{
            var $datas = obj.data('timer') || '', $datas = $datas.split('|');
        }
        var hourcss = $datas[1], mincss = $datas[2], seccss = $datas[3];
        var callback = $datas[4];
        var times = modal.formatSeconds(lasttime);
        obj.find(hourcss).html(times.hour);
        obj.find(mincss).html(times.min);
        obj.find(seccss).html(times.sec);
        if (lasttime <= 0) {
            if (callback) {
                eval("(" + callback + ")")(obj)
            }else{
                if (modal.useEndtime) location.reload();
                modal.useEndtime = true;
            }
        }else{

            //每十秒请求一次服务器，获取时间
            if(lasttime%10==0){
                $.ajax({
                    url: '../addons/ewei_shopv2/map.json',cache:false,complete: function (x) {
                        var now_time = +new Date(x.getResponseHeader("Date")) / 1000;

                        if (modal.useEndtime){
                            var datas = obj.data('time-end') || '';
                        }else{
                            var datas = obj.data('timer') || '';
                        }

                        if (datas == '') {
                            return false
                        }

                        datas = datas.split('|');
                        if (datas.length != 5) {
                            return false
                        }
                        var status = $(obj).data('status') || 0;
                        var time0 = datas[0] - now_time;
                        var time1 = datas[4] - now_time;
                        if(status==0) {
                            if (time0 > 0) {
                                obj.attr('data-timer-lasttime', time0);
                                return;
                            }
                        }else{
                            if (time1 > 0) {
                                obj.attr('data-timer-lasttime', time1);
                                return;
                            }
                        }
                    }
                })
            }

            lasttime--;
            obj.attr('data-timer-lasttime', lasttime);
        }
    };

    modal.setTimerInterval = function (obj) {
        $(this).attr('data-timer-interval', setInterval(function () {
            modal.setTimer(obj)
        }, 1000))
    };

    modal.initTimers = function (obj) {
        if (typeof(obj) === 'undefined') {
            obj = '[data-toggle="timer"]'
        }
        $.ajax({
            url: '../addons/ewei_shopv2/map.json', complete: function (x) {
                try{
                    var currenttime = +new Date(x.getResponseHeader("Date")) / 1000;
                }catch (e) {
                    var currenttime = +parseInt(new Date() / 1000);
                }
                $(obj).each(function () {
                    var obj = $(this);
                    modal.lasttime(obj,currenttime);
                    modal.setTimer(obj);
                    modal.setTimerInterval(obj)
                })
            }
        })
    };

    modal.lasttime = function (obj,currenttime) {
        if (modal.useEndtime){
            var datas = obj.data('time-end') || '';
        }else{
            var datas = obj.data('timer') || '';
        }

        if (datas == '') {
            return false
        }

        datas = datas.split('|');
        if (datas.length != 5) {
            return false
        }
        obj.attr('data-timer-interval', 0);
        var status = $(obj).data('status') || 0;
        var time0 = datas[0] - currenttime;
        var time1 = datas[4] - currenttime;
        if(status==0) {
            if (time0 > 0) {
                obj.attr('data-timer-lasttime', time0);
            }else{
                modal.useEndtime = true;
                modal.lasttime(obj,currenttime);
            }
        }else{
            if (time1 > 0) {
                obj.attr('data-timer-lasttime', time1);
            }else{
                modal.useEndtime = true;
                modal.lasttime(obj,currenttime);
            }
        }
    };
    return modal
});