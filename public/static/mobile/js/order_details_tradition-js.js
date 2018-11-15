//详情框中的返回按钮
$("#goback").on("click", function () {
    window.history.back();
});

var year_last = "";//获取订单结束时间的年
var mouth_last = "";//获取订单结束时间的月(减1)
var date_last = "";//获取订单结束时间的日
var hour_last = "";//获取订单结束时间的小时

var goods_amount;
var goods_basic;
var goods_procedure;
var total_pricr;
//是否是申请还车状态中(0：不是；1：是)
var return_expect;

sn = getValueByParameter(location.href, 'order_sn');

goods_type = getValueByParameter(location.href, 'goods_type');
var order_id = 0;
//根据order_sn获取订单基本信息
$.ajax({
    url: website + '/api/Order/get_ordersn_info.html',
    type: "post",
    data: {
        order_sn: sn,
        myrand: new Date().getTime()
    },
    dataType: "json",
    success: function (data) {
        if (parseInt(data.code) == 0) {
            var order = data.data;
            $(".js-order-amount").text('¥' + parseFloat(order.order_amount).toFixed(2));

            order_id = order.id;

            $("#js_order_time").text(order.interval_time_str.timestr);
            $("#js_order_km").text(order.all_mileage + "公里");
            $("#js_order_licence_plate").text(order.order_goods.licence_plate);
            $("#js_order_take_store").text(order.acquire_store_name);
            $("#js_order_return_store").text(order.return_store_name);
            $("#js_order_take_store_tel").text(order.acquire_store_tel + ")");
            $("#js_order_return_store_tel").text(order.return_store_tel + ")");
            $("#js_order_take_time").text(order.acquire_time_str);
            $("#js_order_return_time").text(order.return_time_str);
            $("#js_order_cost_midway").text(order.pd_amount + "元");
            $("#js_order_coupon_type").text(order.coupon_class);
            $("#js_order_coupon_cost").text(parseFloat(order.coupon_type).toFixed(2) + "元");

            $("#js_goods_name").text(order.order_goods.goods_name);

            year_last = order.return_time_data.year;
            mouth_last = order.return_time_data.month;
            date_last = order.return_time_data.day;
            hour_last = order.return_time_data.hour;

            //初始化取车时间控件
            $("#js_time_choose_continue").attr("data-time", year_last + "-" + mouth_last + "-" + date_last + " " + hour_last + ":00:00");
            $("#js_time_hour_take").text(hour_last + ":00");
            $("#js_time_date_take").text(year_last + "/" + mouth_last + "/" + date_last);

            //将当前时间加1天以作为还车时间
            var end_time = new Date(getLocalTime(year_last + "-" + mouth_last + "-" + date_last, 1));
            var e_m = (end_time.getMonth() + 1) < 10 ? "0" + (end_time.getMonth() + 1) : (end_time.getMonth() + 1);
            var e_d = end_time.getDate() < 10 ? "0" + (end_time.getDate()) : (end_time.getDate());
            var e_h = hour_last;

            //初始化还车时间控件
            $("#js_time_choose_return").attr("data-time", year_last + "-" + e_m + "-" + e_d + " " + e_h + ":00:00");
            $("#js_time_hour_return").text(e_h + ":00");
            $("#js_time_date_return").text(year_last + "/" + e_m + "/" + e_d);

            $("#popover_detail_days").text("1天");
            $("#js_use_car_time").text(1 + "天");
            $("#js_use_car_time").attr("data-days", "1");

            goods_amount = parseFloat(order.goods_amount);
            goods_basic = parseFloat(order.goods_amount_detail.goods_basic);
            goods_procedure = parseFloat(order.goods_amount_detail.goods_procedure);
            total_pricr = parseFloat(1 * (goods_amount + goods_basic) + goods_procedure);

            $("#popover_detail_price").text(goods_amount.toFixed(2));
            $("#popover_detail_basic").text(goods_basic.toFixed(2));
            $("#popover_detail_procedure").text(goods_procedure.toFixed(2));

            $("#popover_detail_amount").text("￥"+total_pricr.toFixed(2));
            $("#js_total_price").text("￥"+total_pricr.toFixed(2));


            var return_type_value = localStorage.getItem("return_type_value");
            if(parseInt(return_type_value) == 1){
                $("#js_store_return_choose").show();
                $("input[name='return_type'][value='1']").attr("checked","checked");

                $("#js_store_return_choose_continue").show();
                $("input[name='return_type_continue'][value='1']").attr("checked","checked");

                if (localStorage.getItem("point_return") == null) {
                    $("#js_store_return").text("点击“修改”选择还车点");
                    $("#js_point_return_continue").text("点击“修改”选择还车点");
                } else {
                    var point_take = JSON.parse(localStorage.getItem("point_return"));
                    $("#js_store_return").text(point_take.name);
                    $("#js_point_return_continue").text(point_take.name);
                }
            }else {
                $("#js_store_return_choose").hide();
                $("input[name='return_type'][value='0']").attr("checked","checked");
                $("#js_store_return_choose_continue").hide();
                $("input[name='return_type_continue'][value='0']").attr("checked","checked");

                $("#js_store_return").text($("#js_order_take_store").text());
                $("#js_point_return_continue").text($("#js_order_take_store").text());
            }
            return_expect = order.return_expect;
            if(parseInt(return_expect) == 0){
                $("#js_use_car_continue").click();
                $(".js_return_handle_choose").show();
                $(".js_return_handle_wait").hide();
            }else {
                $("#js_use_car_return").click();
                $("#js_store_return_choose").hide();
                $("#js_return_car").text("等待店家确认还车");
                $("#js_store_return_show").text(order.return_expect_address.name);
                $(".js_return_handle_choose").hide();
                $(".js_return_handle_wait").show();
            }

        } else {
            Toast(data.info, 4000);
            $("#js-pay-btn").hide();
        }
        loadend = true;
    },
    error: function (XMLHttpRequest, textStatus, errorThrown) {
        Toast(textStatus, 2000);
    },
    complete: function (XMLHttpRequest, textStatus) {
        // 调用本次AJAX请求时传递的options参数
    }
});

//协议是否同意选择
$("#user_protocol").on('click', function () {
    if ($(this).hasClass('user_protocol_no')) {
        $(this).removeClass('user_protocol_no');
        $(this).addClass('user_protocol_yes');
    } else {
        $(this).removeClass('user_protocol_yes');
        $(this).addClass('user_protocol_no');
    }
});

$("#openPopover").on("click", function () {
    pushHistory();//进栈
});
$("#openPopover_detail").on("click", function () {
    pushHistory();//进栈
});


//物理返回按钮
// pushHistory();//进栈
//物理返回键的点击事件监听
window.addEventListener("popstate", function (e) {
    if ($("#popover").css('display') == 'block') {
        $("#popover").fadeOut(300);
        window.history.back();
    } else if ($("#popover_detail").css('display') == 'block') {
        $("#popover_detail").fadeOut(300);
        window.history.back();
    } else {
        window.history.back();
    }
}, false);

//将上一个页面进栈
function pushHistory() {
    var state = {
        title: "title",
        url: "#"
    };
    window.history.pushState(state, "title", "#");
}

//协议窗口的返回键点击事件
$("#goto_back").on('click', function () {
    $("#popover").fadeOut(300);
});
//协议窗口的点击事件
$("#openPopover").on('click', function () {
    $("#popover").fadeIn(250);
});
//明细窗口的返回键点击事件
$("#goto_back_detail").on('click', function () {
    $("#popover_detail").fadeOut(300);
});
//明细窗口的点击事件
$("#openPopover_detail").on('click', function () {
    $("#popover_detail").fadeIn(250);
});

//还车操作的还车方式的监听
$("input[name='return_type']").on("change",function () {
    if($(this).val() == 1){
        localStorage.setItem("return_type_value","1");
        $("#js_store_return_choose").show();
        if (localStorage.getItem("point_return") == null) {
            $("#js_store_return").text("点击“修改”选择还车点");
        } else {
            var point_return = JSON.parse(localStorage.getItem("point_return"));
            $("#js_store_return").text(point_return.name);
        }
    }else {
        localStorage.setItem("return_type_value","0");
        $("#js_store_return_choose").hide();
        $("#js_store_return").text($("#js_order_take_store").text());
    }
});
//续租操作的还车方式的监听
$("input[name='return_type_continue']").on("change",function () {
    if($(this).val() == 1){
        localStorage.setItem("return_type_value","1");
        $("#js_store_return_choose_continue").show();

        if (localStorage.getItem("point_return") == null) {
            $("#js_point_return_continue").text("点击“修改”选择还车点");
        } else {
            var point_return = JSON.parse(localStorage.getItem("point_return"));
            $("#js_point_return_continue").text(point_return.name);
        }
    }else {
        localStorage.setItem("return_type_value","0");
        $("#js_store_return_choose_continue").hide();
        $("#js_point_return_continue").text($("#js_order_take_store").text());
    }
});
//“我要还车”的按钮点击事件
$("#js_use_car_return").on("click",function () {
    localStorage.setItem("default_user_handle","0");
    $(this).css("background","#ffd31c");
    $("#js_use_car_continue").css("background","white");
    $("#js_use_car_return_handle").show();
    $("#js_use_car_continue_handle").hide();
});
//“我要续租”的按钮点击事件
$("#js_use_car_continue").on("click",function () {
    if(parseInt(return_expect) === 1){
        Toast("已提交申请，不能申请续租！");
        return;
    }
    localStorage.setItem("default_user_handle","1");
    $(this).css("background","rgb(0,153,255)");
    $("#js_use_car_return").css("background","white");
    $("#js_use_car_continue_handle").show();
    $("#js_use_car_return_handle").hide();
});
//设置默认的用车操作
var default_user_handle = localStorage.getItem("default_user_handle");
if(default_user_handle === "1"){
    $("#js_use_car_continue").click();
}else {
    $("#js_use_car_return").click();
}

//“我要还车”模块的还车点点击事件
$("#js_store_return_choose").on("click",function () {
   Toast("即将进行还车点的选择",2000);
   setTimeout(function () {
       localStorage.setItem("order_sn_details_tradition",sn);
       location.href = "store_return.html";
   },2000);
});

//“我要续租”模块的还车点点击事件
$("#js_store_return_choose_continue").on("click",function () {
   Toast("即将进行还车点的选择",2000);
   setTimeout(function () {
       localStorage.setItem("order_sn_details_tradition",getValueByParameter(location.href, 'sn'));
       location.href = "store_return.html";
   },2000);
});

//“我要续租”模块的还车时间点击事件
$("#js_time_choose_return").on("click",function () {
    //将取车时间的第二天作为还车时间的起始时间，即至少为一天
    var dtpicker = new mui.DtPicker({
        type: "hour",//设置日历初始视图模式
        beginDate: new Date(parseInt(year_last), parseInt(mouth_last) - 1, parseInt(date_last), parseInt(hour_last)),//设置开始日期
        endDate: new Date(parseInt(year_last) + 1, 11, 31, parseInt(hour_last)),//设置结束日期
        labels: ['年', '月', '日', "时"]//设置默认标签区域提示语
    });

    dtpicker.show(function (e) {
        var take_time = $("#js_time_choose_continue").attr("data-time");
        var return_time = e.text + ":00:00";
        var days = getDays(take_time.substring(0, 10), return_time.substring(0, 10), take_time.substring(11, 13), return_time.substring(11, 13));
        if (days < 0) {
            Toast("不能选择过去的时间", 2000);
        } else {
            $("#js_time_choose_return").attr("data-time", e.y.value + "-" + e.m.value + "-" + e.d.value + " " + e.h.value + ":00:00");
            $("#js_time_hour_return").text(e.h.value + ":00");
            $("#js_time_date_return").text(e.y.value + "/" + e.m.value + "/" + e.d.value);

            $("#js_use_car_time").text(days + "天");
            $("#js_use_car_time").attr("data-days", days);


            $("#popover_detail_days").text(days + "天");

            total_pricr = parseFloat(days * (goods_amount + goods_basic) + goods_procedure);

            $("#popover_detail_price").text(goods_amount.toFixed(2));
            $("#popover_detail_basic").text(goods_basic.toFixed(2));
            $("#popover_detail_procedure").text(goods_procedure.toFixed(2));

            $("#popover_detail_amount").text("￥"+total_pricr.toFixed(2));
            $("#js_total_price").text("￥"+total_pricr.toFixed(2));
        }
    });
});
//续租提交订单
$("#js_submit_order").on("click",function () {
    // if ($("#user_protocol").hasClass('user_protocol_no')) {
    //     mui.alert('使用该车辆，需要同意优车出行服务协议', '提示');
    //     return;
    // }
    //取还车时间
    var etime = $("#js_time_choose_return").attr("data-time");

    var return_visit = $("input[name='return_type_continue']:checked").val();
    var return_address;
    if(return_visit == 1){
        return_address = localStorage.getItem("point_return");
        if(return_address == null){
            Toast("请先选择还车点",2000);
            // setTimeout(function () {
            //     location.href = "store_return.html";
            // },2000);
            return;
        }else {
            return_address = JSON.parse(return_address);
        }
    }else {
        return_address = "";
    }

    // alert(order_id + "\n" + etime + "\n" + JSON.stringify(return_address) + "\n" + sn);

    loadMessageBox("正在提交订单", 10);
    $.ajax({
        url: website + "/api/Order/add_lease_order",
        type: "POST",
        async: true,
        data: {
            order_id: order_id,
            return_time: etime,
            return_address:return_address
        },
        timeout: 5000,
        dataType: "json",
        success: function (data) {
            closeLoadMessageBox();
            if (parseInt(data.code) == 0) {
                location.href = "order_pay.html?sn=" + sn + "&goods_type=1";
            }

        },
        fail: function () {
            Toast("请求失败", 2000);
        }
    });
});


$("#js_return_car").on("click",function () {
    if(parseInt(return_expect) === 1){
        Toast("已提交申请，不能重复提交！");
        return;
    }

    var return_visit = $("input[name='return_type']:checked").val();
    var return_address;
    if(return_visit == 1){
        return_address = localStorage.getItem("point_return");
        if(return_address == null){
            Toast("请先选择还车点",2000);
            // setTimeout(function () {
            //     location.href = "store_return.html";
            // },2000);
            return;
        } else {
            return_address = JSON.parse(return_address);
        }
    }else {
        return_address = "";
    }

    // alert(order_id + "\n" + JSON.stringify(return_address) + "\n" + sn);

    loadMessageBox("正在申请还车", 10);
    $.ajax({
        url: website + "/api/Order/return_reserve_order",
        type: "POST",
        async: true,
        data: {
            order_id: order_id,
            return_address:return_address
        },
        timeout: 5000,
        dataType: "json",
        success: function (data) {
            closeLoadMessageBox();
            if (parseInt(data.code) == 0) {
                location.reload();
            }
        },
        fail: function () {
            Toast("请求失败", 2000);
        }
    });
});