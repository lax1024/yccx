//返回按钮
$("#goback").on("click", function () {
    window.history.back();
});

var second;//订单秒数
var minute;//订单分钟
var hour;//订单小时
var timer;//订单计时器

var price_total;//订单总价

var licence_plate;//车牌号
var goods_id;//车辆id
var goods_amount;//每小时单价
var km_amount;//每公里单价
var acquire_time_str;//取车时间字符串
var order_sn;//订单sn
var order_amount;//订单总价

var return_picture_is;//取车图片是否上传

var all_mileage;//历程
var energy;//电量

var service_phone; //客服电话

var timer_s;//审核倒计时

var info_car;//车辆提示信息
var video_url_car;//车辆操作视频

//根据地址获取订单id
var order_id = getValueByParameter(location.href, "order_id");
//调用获取订单信息方法
getOrderDetailsById(order_id);

document.addEventListener('visibilitychange', function () {
    var isHidden = document.hidden;
    if (isHidden) {
    } else {
        getOrderDetailsById(order_id);
    }
});

/**
 * 根据订单id获取订单详情
 * @param order_id  订单id
 */
function getOrderDetailsById(order_id) {
    $.ajax({
        url: website + "/api/Order/get_order_info.html",
        type: "GET",
        async: true,
        data: {
            id: order_id
        },
        timeout: 15000,
        dataType: "json",
        success: function (data) {
            if (data.code == 0) {
                var data = data.data;
                stopCount();
                timedCount();
                order_amount = data.order_amount;
                goods_amount = data.goods_amount;
                km_amount = data.goods_km_amount;
                $("#is_taximeter").text(order_amount.toFixed(2) + "元");
                return_picture_is = data.return_picture_is;
                second = data.interval_time_str.second;
                minute = data.interval_time_str.minute;
                hour = data.interval_time_str.hour;
                goods_id = data.goods_id;
                acquire_time_str = data.acquire_time_str;
                licence_plate = data.order_goods.licence_plate;
                order_sn = data.order_sn;
                all_mileage = data.all_mileage;
                energy = data.energy;
                service_phone = data.acquire_store_tel;

                info_car = data.car.info;
                video_url_car = data.car.url;
                initUI();
            }
        },
        error: function () {
            location.reload();
        }
    });
}


//初始化界面:将订单信息显示到界面
function initUI() {
    $("#js_take_car_time").text(acquire_time_str);

    $("#js_licence_plate").text(licence_plate);

    $("#js_order_number").text(order_sn);

    $("#js_timer").text(hour + "时" + minute + "钟" + second + "秒");

    $("#js_all_mileage").text(all_mileage + "Km");
    $("#js_energy").text(energy + "%");

    $("#js_call_service_tel").attr("href", "tel:" + service_phone);

    $("#js_info_handle_car").text(info_car);
    $("#js_video_handle_car").attr("href", video_url_car);
}

//计时器
function timedCount() {
    $("#js_timer").text(hour + "时" + minute + "钟" + second + "秒");

    second = second + 1;

    if (second == 60) {
        minute = minute + 1;
        second = 0;
    }
    if (minute == 60) {
        hour = hour + 1;
        minute = 0;
    }
    price_total = (hour * 3600 + minute * 60 + second) * (parseFloat(goods_amount) / 3600);
    price_total = price_total + parseFloat(km_amount) * parseInt(all_mileage);
    $("#is_taximeter").text(price_total.toFixed(2) + "元");

    timer = setTimeout("timedCount()", 1000);
}

//结束计时器
function stopCount() {
    clearTimeout(timer);
}

$("#js_order_menu_fast").on("click", function () {
    loadMessageBox("正在检测是否可以还车", 10);
    setTimeout(function () {
        fast_return_car(order_id, goods_id);
    }, 2000);
});

//快速还车方法
function fast_return_car(order_id, goods_id) {
    $.ajax({
        url: website + "/api/Order/quick_elesite.html",
        type: "GET",
        async: true,
        data: {
            order_id: order_id,
            car_id: goods_id
        },
        timeOut: 10000,
        dataType: "json",
        success: function (data) {
            closeLoadMessageBox();
            var code = data.code;
            if (code == 0) {
                location.href = "return_car_pic.html?order_id=" + order_id + "&sn=" + order_sn;
            } else {
                Toast("" + data.info, 4000);
            }
        },
        error: function () {
            // Toast("请求失败",2000);
            closeLoadMessageBox();
        }
    });
}

//选点还车
$("#js_goto_map_choose_store").on("click", function () {
    //location.href = "map_choose_store.html?order_id=" + order_id + "&goods_id=" + goods_id;
    location.href = "map_return_car.html?order_id=" + order_id + "&order_sn=" + order_sn + "&goods_id=" + goods_id;
});

//检查车辆状态是否满足还车要求
function check_car_status() {
    $("#js-sure-btn").attr("disabled", "disabled");//将立即还车按钮(重新审核)初始化不可点击
    $("#js_examine_timer_wait").html("正在审核中...</span><span id=\"js_examine_timer\" style=\"color: #ff0000\">");
    $.ajax({
        url: website + "/api/Order/get_order_car_info.html",
        type: "POST",
        async: true,
        data: {
            order_id: order_id
        },
        timeOut: 5000,
        dataType: "json",
        beforeSend: function () {

        },
        success: function (data) {
            $(".hysdk-ucapi-dialog").show();
            var i = 0;
            timer_s = setInterval(function () {
                i++;
                $("#js_examine_timer").text("(" + i + "秒)");
                if (i >= 4) {
                    window.clearInterval(timer_s);
                    $("#js_examine_timer").remove();
                    if (data.info == "验证通过") {
                        $("#js_examine_timer_wait").text(data.info + "，可以还车");
                        $("#js-sure-btn").text("确认还车");
                    } else {
                        $("#js_examine_timer_wait").text(data.info + "，请核实...");
                        $("#js-sure-btn").text("重新审核");
                    }

                    $("#js-sure-btn").removeAttr("disabled");
                }
            }, 1000);
        }
    });
}

//审核框中的“取消”按钮
$("#js-cancel-btn").on("click", function () {
    $(".hysdk-ucapi-dialog").hide();
});

//确认还车(审核框中)
$("#js-sure-btn").on("click", function () {
    $(".hysdk-ucapi-dialog").hide();//隐藏审核框

    if ($("#js-sure-btn").text() == "重新审核") {
        check_car_status();
    } else {
        $.ajax({
            url: website + "/api/Order/wait_return_order.html",
            type: "POST",
            async: true,
            data: {
                order_id: order_id
            },
            timeOut: 5000,
            dataType: "json",
            beforeSend: function () {

            },
            success: function (data) {
                if (data.code == 0) {
                    location.reload();//刷新页面
                    $("#js_order_menu_sure").text("立即支付");
                } else {
                    Toast(data.info + "", 2000);
                }
            }
        });
    }
});

//寻车
$("#js_ele_lock_find").on("click", function () {
    control_car_by_cmd(order_id, "1", "寻车");
});
//开门
$("#js_ele_lock_open").on("click", function () {
    control_car_by_cmd(order_id, "2", "开门");
});
//关门
$("#js_ele_lock_close").on("click", function () {
    control_car_by_cmd(order_id, "3", "关门");
});
//点火
$("#js_ele_lock_fireup").on("click", function () {
    control_car_by_cmd(order_id, "4", "点火");
});
//熄火
$("#js_ele_lock_misfire").on("click", function () {
    control_car_by_cmd(order_id, "5", "熄火");
});

//电子锁：通过发送相应的指令来实现电子锁功能
function control_car_by_cmd(order_id, cmd, str) {
    loadMessageBox("正在下发" + str + "指令", 10);
    $.ajax({
        url: website + "/api/Order/order_cmd.html",
        type: "POST",
        async: true,
        data: {
            order_id: order_id,
            cmd: cmd
        },
        timeOut: 10000,
        dataType: "json",
        success: function (data) {
            closeLoadMessageBox();
            if (data.code == 0) {
                Toast(str + "指令下发成功！", 2000);
            } else {
                Toast(data.info, 2000);
            }
        }
    });
}