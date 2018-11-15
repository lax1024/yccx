var json_data_car;
// if(getReferrUrl() != ""){
var take_type_value = localStorage.getItem("take_type_value");
if (parseInt(take_type_value) == 1) {
    $("#js_store_take_choose").show();
    $("input[name='take_type'][value='1']").attr("checked", "checked");
} else {
    $("#js_store_take_choose").hide();
    $("input[name='take_type'][value='0']").attr("checked", "checked");
}
var return_type_value = localStorage.getItem("return_type_value");
if (parseInt(return_type_value) == 1) {
    $("#js_store_return_choose").show();
    $("input[name='return_type'][value='1']").attr("checked", "checked");
} else {
    $("#js_store_return_choose").hide();
    $("input[name='return_type'][value='0']").attr("checked", "checked");
}
// }

if (localStorage.getItem("point_take") == null) {
    $("#js_store_return").text("点击“修改”选择还车点");
} else {
    var point_take = JSON.parse(localStorage.getItem("point_take"));
    $("#js_store_take").text(point_take.name);
    $("#js_store_take").attr("data-lat",point_take.lat);
    $("#js_store_take").attr("data-lng",point_take.lng);
}

if (localStorage.getItem("point_return") == null) {
    $("#js_store_return").text("点击“修改”选择还车点");
} else {
    var point_return = JSON.parse(localStorage.getItem("point_return"));
    $("#js_store_return").text(point_return.name);
    $("#js_store_return").attr("data-lat",point_return.lat);
    $("#js_store_return").attr("data-lng",point_return.lng);
}

//创建时间变量，获取当前时间，以做界面初始化
var now = new Date();
var year = now.getFullYear();//获取当前年
var mouth = now.getMonth();//获取当前月(减1)
var date = now.getDate();//获取当前日
var hour = now.getHours();//获取当前小时

//将月日小于10的拼接成01,02等形式
var s_m = mouth + 1 < 10 ? "0" + (mouth + 1) : (mouth + 1);
var s_d = date < 10 ? "0" + date : date;
var s_h = hour < 10 ? "0" + hour : hour;

//初始化取车时间控件
$("#js_take_time").text(s_m + "-" + s_d);
$("#js_take_time").attr("data-time", year + "-" + s_m + "-" + s_d + " " + s_h + ":00:00");
var s_w = getWeek(year + "-" + (mouth + 1) + "-" + date);
$("#js_take_week").text(s_w + " " + s_h + ":00");

//将当前时间加1天以作为还车时间
var end_time = new Date(getLocalTime(year + "-" + (mouth + 1) + "-" + date, 1));

var e_m = (end_time.getMonth() + 1) < 10 ? "0" + (end_time.getMonth() + 1) : (end_time.getMonth() + 1);
var e_d = end_time.getDate() < 10 ? "0" + (end_time.getDate()) : (end_time.getDate());
var e_h = s_h;

//初始化还车时间控件
$("#js_return_time").text(e_m + "-" + e_d);
$("#js_return_time").attr("data-time", getLocalTime(year + "-" + parseInt(mouth + 1) + "-" + date, 1) + " " + e_h + ":00:00");

var e_w = getWeek(getLocalTime(year + "-" + parseInt(mouth + 1) + "-" + date, 1));
$("#js_return_week").text(e_w + " " + e_h + ":00");

//创建并初始化时间选择器控件
var dtpicker = new mui.DtPicker({
    type: "hour",//设置日历初始视图模式
    beginDate: new Date(year, mouth, date, hour),//设置开始日期
    endDate: new Date(year + 1, 11, 31, hour),//设置结束日期
    labels: ['年', '月', '日', "时"]//设置默认标签区域提示语
});

//取车时间控件的点击事件
$(".js_take_time").on("click", function () {
    dtpicker = new mui.DtPicker({
        type: "hour",//设置日历初始视图模式
        beginDate: new Date(year, mouth, date, hour),//设置开始日期
        endDate: new Date(year + 1, 11, 31, hour),//设置结束日期
        labels: ['年', '月', '日', "时"]//设置默认标签区域提示语
    });

    //时间选择器上方的时间类型标题
    $(".mui-dtpicker-title").text("取车时间");

    //选择时间后的处理
    dtpicker.show(function (e) {
        //修改取车时间界面显示
        $("#js_take_time").text(e.m.text + "-" + e.d.text);
        $("#js_take_time").attr("data-time", e.text + ":00:00");
        s_w = getWeek(e.y.value + "-" + e.m.value + "-" + e.d.value);
        s_h = e.h.value;
        $("#js_take_week").text(s_w + " " + s_h + ":00");

        //将所选时间加1作为还车时间
        var end_time = new Date(getLocalTime(e.y.value + "-" + e.m.value + "-" + e.d.value, 1));
        var e_m = (end_time.getMonth() + 1) < 10 ? "0" + (end_time.getMonth() + 1) : (end_time.getMonth() + 1);
        var e_d = end_time.getDate() < 10 ? "0" + end_time.getDate() : end_time.getDate();

        //修改还车时间界面显示
        $("#js_return_time").text(e_m + "-" + e_d);
        $("#js_return_time").attr("data-time", getLocalTime(e.y.value + "-" + e.m.value + "-" + e.d.value, 1) + " " + e.h.value + ":00");
        e_w = getWeek(end_time.getFullYear() + "-" + e_m + "-" + e_d);
        e_h = s_h;
        $("#js_return_week").text(e_w + " " + e_h + ":00");
        //修改用车时长界面显示
        $("#js_use_car_time").text(1 + "天");
        $("#js_use_car_time").attr("data-days", 1);

        $("#segmentedControlContents").find(".js_store_list_li").eq(0).click();

    });
});

//还车时间控件的点击事件
$(".js_return_time").on("click", function () {
    //获取取车时间加1的时间数组
    var temp_array = getLocalTime(year + "-" + (mouth + 1) + "-" + date, 1, "array");
    //将取车时间的第二天作为还车时间的起始时间，即至少为一天
    dtpicker = new mui.DtPicker({
        type: "hour",//设置日历初始视图模式
        beginDate: new Date(temp_array.y, temp_array.m, temp_array.d, e_h),//设置开始日期
        endDate: new Date(year + 1, 11, 31, e_h),//设置结束日期
        labels: ['年', '月', '日', "时"]//设置默认标签区域提示语
    });

    //修改时间选择器的标题
    $(".mui-dtpicker-title").text("还车时间");

    //时间选择器选择还车时间后的处理
    dtpicker.show(function (e) {
        var take_time = $("#js_take_time").attr("data-time");
        var return_time = e.text + ":00:00";
        var days = getDays(take_time.substring(0, 10), return_time.substring(0, 10), take_time.substring(11, 13), return_time.substring(11, 13));
        if (days < 0) {
            Toast("不能选择过去的时间", 2000);
        } else {
            $("#js_return_time").text(e.m.text + "-" + e.d.text);
            $("#js_return_time").attr("data-time", e.text + ":00:00");

            e_w = getWeek(e.y.value + "-" + e.m.value + "-" + e.d.value);
            $("#js_return_week").text(e_w + " " + e.h.value + ":00");
            $("#js_use_car_time").text(days + "天");
            $("#js_use_car_time").attr("data-days", days);

            $("#segmentedControlContents").find(".js_store_list_li").eq(0).click();
        }
    });
});

//取车门店的选择
$("#js_store_take_choose").on("click", function () {
    location.href = "store_take.html";
});

//还车门店的选择
$("#js_store_return_choose").on("click", function () {
    location.href = "store_return.html";
})

// get_car_list_ajax("26.40847866","106.632648");
//获取车辆列表
function get_car_list_ajax(latitude, longitude) {

    loadMessageBox("正在获取车辆数据", 10);

    var stime = $("#js_take_time").attr("data-time");
    var etime = $("#js_return_time").attr("data-time");

    $.ajax({
        url: website + "/api/Car/get_carcommon_list",
        type: "POST",
        async: true,
        data: {
            goods_type: 1,
            // slng:"106.632648",
            // slat:"26.40847866",
            // elng:"106.632648",
            // elat:"26.40847866",
            // acquire_time:"2018-07-25 00:00:00",
            // return_time:"2018-07-28 00:00:00",

            slng: longitude,
            slat: latitude,
            elng: longitude,
            elat: latitude,
            acquire_time: stime,
            return_time: etime,
            is_map: 2,
            km: 150

        },
        timeout: 5000,
        dataType: "json",
        success: function (data) {
            closeLoadMessageBox();
            if (data.code == 0) {
                json_data_car = data.data;
                add_car_list(json_data_car, "day_price");
            } else {
                Toast(data.info, 2000);
            }
        },
        fail: function () {
            Toast("请求失败", 2000);
            closeLoadMessageBox();
        }
    });
}

var add_store_item = $(".js_store_list").find('li').clone(true);
$(".js_store_list_li").remove();
var add_car_item = $("#content1").find(".js_car_list").clone(true);
$("#content1").find(".js_car_list").remove();

function add_car_list(json_data, type_str) {
    // alert(JSON.stringify(json_data_car));
    $("#content1").find(".js_car_list").remove();
    $("#content2").find(".js_car_list").remove();
    $("#content3").find(".js_car_list").remove();
    $("#content4").find(".js_car_list").remove();
    $.each(json_data, function (data_index, data_value) {
        if (data_index == type_str) {
            $.each(data_value, function (index, value) {
                var item_temp = add_car_item.clone(true);
                item_temp.show();
                item_temp.find("img").attr("src", "http://img.youchedongli.cn/public/" + value.car.series_img);
                item_temp.find(".js_car_series_name").text(value.car.series_name);
                item_temp.find(".js_car_cartype_name").text(value.car.cartype_name);

                var store_list = value.store;

                for (var i = 0; i < store_list.length; i++) {
                    var item_temp_store = add_store_item.clone(true);

                    item_temp_store.attr("data-store-id", store_list[i].store_id);
                    item_temp_store.attr("data-car-id", store_list[i].id);
                    item_temp_store.attr("data-day_price", store_list[i].day_price);
                    item_temp_store.attr("data-day_basic", store_list[i].day_basic);
                    item_temp_store.attr("data-day_procedure", store_list[i].day_procedure);

                    item_temp_store.find(".js_store_name").text(store_list[i].store_name);
                    item_temp_store.find(".js_store_name").attr("data-lat",store_list[i].location_latitude);
                    item_temp_store.find(".js_store_name").attr("data-lng",store_list[i].location_longitude);
                    item_temp_store.find(".js_store_price").text("￥" + store_list[i].day_price);
                    item_temp_store.find(".js_store_distance").text(store_list[i].distance + "Km");
                    item_temp_store.appendTo(item_temp.find('.js_store_list'));
                    item_temp_store.on("click", function () {
                        $("#segmentedControlContents").find(".js_store_list_li").removeClass("store_list_li_checked");
                        $(this).addClass("store_list_li_checked");

                        var day_price = parseFloat($(this).attr("data-day_price"));
                        var day_basic = parseFloat($(this).attr("data-day_basic"));
                        var day_procedure = parseFloat($(this).attr("data-day_procedure"));
                        cal_price_total(day_price, day_basic, day_procedure);

                        var acquire_visit = $("input[name='take_type']:checked").val();
                        if (acquire_visit == 0) {
                            $("#js_store_take").text($("#segmentedControlContents").find(".store_list_li_checked").find(".js_store_name").text());
                            $("#js_store_take").attr("data-lat",$("#segmentedControlContents").find(".store_list_li_checked").find(".js_store_name").attr("data-lat"));
                            $("#js_store_take").attr("data-lng",$("#segmentedControlContents").find(".store_list_li_checked").find(".js_store_name").attr("data-lng"));
                        }
                        var return_visit = $("input[name='return_type']:checked").val();
                        if (return_visit == 0) {
                            $("#js_store_return").text($("#segmentedControlContents").find(".store_list_li_checked").find(".js_store_name").text());
                            $("#js_store_return").attr("data-lat",$("#segmentedControlContents").find(".store_list_li_checked").find(".js_store_name").attr("data-lat"));
                            $("#js_store_return").attr("data-lng",$("#segmentedControlContents").find(".store_list_li_checked").find(".js_store_name").attr("data-lng"));
                        }


                        var take_type_value = localStorage.getItem("take_type_value");
                        if (parseInt(take_type_value) == 1) {
                            $("#js_store_take_choose").show();
                            $("input[name='take_type'][value='1']").attr("checked", "checked");
                        } else {
                            $("#js_store_take_choose").hide();
                            $("input[name='take_type'][value='0']").attr("checked", "checked");
                        }

                        var return_type_value = localStorage.getItem("return_type_value");
                        if (parseInt(return_type_value) == 1) {
                            $("#js_store_return_choose").show();
                            $("input[name='return_type'][value='1']").attr("checked", "checked");
                        } else {
                            $("#js_store_return_choose").hide();
                            $("input[name='return_type'][value='0']").attr("checked", "checked");
                        }

                    });
                }
                item_temp.appendTo("#content" + index);
            });
        }
    });
    //设置默认第一条车辆
    $("#segmentedControlContents").find(".js_store_list_li").eq(0).click();

}

//计算价格等
function cal_price_total(day_price, day_basic, day_procedure) {
    var days_total = $("#js_use_car_time").attr("data-days");

    var total_price = days_total * (day_price + day_basic) + day_procedure;

    $("#js_total_price").text("￥" + total_price.toFixed(2));
    $("#popover_detail_price").text("￥" + day_price.toFixed(2));
    $("#popover_detail_basic").text("￥" + day_basic.toFixed(2));
    $("#popover_detail_procedure").text("￥" + day_procedure.toFixed(2));
    $("#popover_detail_days").text(days_total + " 天");
    $("#popover_detail_amount").text("￥" + total_price.toFixed(2));

}

//车型的切换
$(".mui-control-item").on("click",function () {
    $("#segmentedControlContents").find(".js_store_list_li").removeClass("store_list_li_checked");
    var target_id = $(this).attr("data-href");
    var contents = $(".mui-control-content");
    for(var i = 0; i < contents.length; i ++){
        console.log($(contents[i]).attr("id"));
        if(target_id === $(contents[i]).attr("id")){
            $(contents[i]).addClass("mui-active");
        }else {
            $(contents[i]).removeClass("mui-active");
        }
    }
    $("#" + target_id).find(".js_store_list_li").eq(0).click();
});

//取车方式的监听
$("input[name='take_type']").on("change", function () {
    if ($(this).val() == 1) {
        localStorage.setItem("take_type_value", "1");
        $("#js_store_take_choose").show();
        if (localStorage.getItem("point_take") == null) {
            $("#js_store_take").text("点击选择取车门店");
        } else {
            var point_take = JSON.parse(localStorage.getItem("point_take"));
            $("#js_store_take").text(point_take.name);
            $("#js_store_take").attr("data-lat",point_take.lat);
            $("#js_store_take").attr("data-lng",point_take.lng);
        }
    } else {
        localStorage.setItem("take_type_value", "0");
        $("#js_store_take_choose").hide();
        $("#js_store_take").text($("#segmentedControlContents").find(".store_list_li_checked").find(".js_store_name").text());
        $("#js_store_take").attr("data-lat",$("#segmentedControlContents").find(".store_list_li_checked").find(".js_store_name").attr("data-lat"));
        $("#js_store_take").attr("data-lng",$("#segmentedControlContents").find(".store_list_li_checked").find(".js_store_name").attr("data-lng"));
    }
});

//还车方式的监听
$("input[name='return_type']").on("change", function () {
    if ($(this).val() == 1) {
        localStorage.setItem("return_type_value", "1");
        $("#js_store_return_choose").show();
        if (localStorage.getItem("point_return") == null) {
            $("#js_store_return").text("点击选择取车门店");
        } else {
            var point_return = JSON.parse(localStorage.getItem("point_return"));
            $("#js_store_return").text(point_return.name);
            $("#js_store_return").attr("data-lat",point_return.lat);
            $("#js_store_return").attr("data-lng",point_return.lng);
        }
    } else {
        localStorage.setItem("return_type_value", "0");
        $("#js_store_return_choose").hide();
        $("#js_store_return").text($("#segmentedControlContents").find(".store_list_li_checked").find(".js_store_name").text());
        $("#js_store_return").attr("data-lat",$("#segmentedControlContents").find(".store_list_li_checked").find(".js_store_name").attr("data-lat"));
        $("#js_store_return").attr("data-lng",$("#segmentedControlContents").find(".store_list_li_checked").find(".js_store_name").attr("data-lng"));
    }
});

//提交订单
$("#js_submit_order").on("click", function () {
    if ($("#user_protocol").hasClass('user_protocol_no')) {
        mui.alert('使用该车辆，需要同意优车出行服务协议', '提示');
        return;
    }
    //取还车时间
    var stime = $("#js_take_time").attr("data-time");
    var etime = $("#js_return_time").attr("data-time");

    var car_view_checked = $("#segmentedControlContents").find(".store_list_li_checked");
    var goods_id = car_view_checked.attr("data-car-id");
    var store_id_take = car_view_checked.attr("data-store-id");
    var store_id_return = store_id_take;
    var acquire_visit = $("input[name='take_type']:checked").val();
    var acquire_address;
    if (acquire_visit == 1) {
        acquire_address = localStorage.getItem("point_take");
        if (acquire_address == null) {
            Toast("请先选择取车点", 2000);
            // setTimeout(function () {
            //     location.href = "store_take.html";
            // },2000);
            return;
        } else {
            acquire_address = JSON.parse(acquire_address);
        }
    } else {
        acquire_address = "";
    }

    var return_visit = $("input[name='return_type']:checked").val();
    var return_address;
    if (return_visit == 1) {
        return_address = localStorage.getItem("point_return");
        if (return_address == null) {
            Toast("请先选择还车点", 2000);
            // setTimeout(function () {
            //     location.href = "store_return.html";
            // },2000);
            return;
        } else {
            return_address = JSON.parse(return_address);
        }
    } else {
        return_address = "";
    }

    // alert(acquire_address + "\n" + return_address);

    loadMessageBox("正在提交订单", 10);
    $.ajax({
        url: website + "/api/Order/add_car_order",
        type: "POST",
        async: true,
        data: {
            goods_id: goods_id,
            goods_type: 1,
            acquire_time: stime,
            return_time: etime,
            acquire_store_id: store_id_take,
            return_store_id: store_id_return,
            acquire_visit: acquire_visit,
            return_visit: return_visit,
            acquire_address: acquire_address,
            return_address: return_address
        },
        timeout: 5000,
        dataType: "json",
        success: function (data) {
            closeLoadMessageBox();
            if (parseInt(data.code) == 0) {
                var sn = data.data;
                location.href = "order_pay.html?sn=" + sn + "&goods_type=1";
            } else if (parseInt(data.code) == 5) {
                Toast(data.info, 2000);
                setTimeout(function () {
                    location.href = "user_cash.html";
                }, 2000);
            } else if (parseInt(data.code) == 3) {
                Toast(data.info, 2000);
                setTimeout(function () {
                    location.href = "add_user_info.html";
                }, 2000);
            } else if (parseInt(data.code) == 1112) {
                Toast(data.info, 2000);
                setTimeout(function () {
                    location.href = "order_mine.html";
                }, 2000);
            } else {
                Toast(data.info, 2000);
            }
        },
        fail: function () {
            Toast("请求失败", 2000);
        }
    });
});


$("#js_store_return").on("click",function () {
    var name = $(this).text();
    var lat = parseFloat($(this).attr("data-lat"));
    var lng = parseFloat($(this).attr("data-lng"));
    var return_type = localStorage.getItem("return_type_value");
    if(return_type === "0"){
        var ponitx = wgs2gcj(lat, lng);
        go_openLocation(ponitx[0],ponitx[1],name,name);
    }else {
        var ponitx = bd2gcj(lat, lng);
        go_openLocation(ponitx[0],ponitx[1],name,name);
    }
});

$("#js_store_take").on("click",function () {
    var name = $(this).text();
    var lat = parseFloat($(this).attr("data-lat"));
    var lng = parseFloat($(this).attr("data-lng"));
    var take_type = localStorage.getItem("take_type_value");
    if(take_type === "0"){
        var ponitx = wgs2gcj(lat, lng);
        go_openLocation(ponitx[0],ponitx[1],name,name);
    }else {
        var ponitx = bd2gcj(lat, lng);
        go_openLocation(ponitx[0],ponitx[1],name,name);
    }
});
