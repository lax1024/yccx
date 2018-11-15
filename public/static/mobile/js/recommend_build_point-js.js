//返回按钮
$("#js_goto_back").on("click",function () {
    window.history.back();
});

//调用微信定位
$("#js_input_point_location").on("click",function () {
    weixinLocation();
});

//根据经纬度获取位置信息
function getAddress(lat,lng) {
    $.ajax({
        url: "http://www.youchedongli.cn/api/Area/get_city_lng_lat.html",
        type: "GET",
        async: true,
        data: {
            lng:lng,
            lat:lat
        },
        timeout: 5000,
        dataType: "json",
        beforeSend: function (xhr) {
        },
        success: function (data) {
            // Toast("获取数据成功！",2000);
            if (parseInt(data.code) == 0) {
                var add_data = data.data.data;
                $("#js_input_point_name").val(add_data.province + add_data.city + add_data.district + add_data.street + add_data.street_number);
                // $("#js_input_point_name").val(lat + "  ...  " + lng);
            }
        },
        error: function (xhr, textStatus) {
        },
        complete: function () {
        }
    });
}

//提交推荐点
function submit_point(lat,lng,address,remark) {
    $.ajax({
        url: "http://www.youchedongli.cn/api/Customer/add_customer_sign.html",
        type: "POST",
        async: true,
        data: {
            longitude:lng,
            latitude:lat,
            type:2,
            remark:remark,
            address:address
        },
        timeout: 5000,
        dataType: "json",
        beforeSend: function (xhr) {
        },
        success: function (data) {
            // Toast("获取数据成功！",2000);
            if (parseInt(data.code) == 0) {
                Toast("感谢您的推荐",2000);
                setTimeout(function () {
                    window.history.back();
                },2000);
            }else {
                Toast(data.info,2000);
            }
        },
        error: function (xhr, textStatus) {
        },
        complete: function () {
        }
    });
}

//推荐建点按钮
$("#js_input_point_submit").on("click",function () {
    var address = $("#js_input_point_name").val();
    var remark = $("#js_input_point_info").val();

    submit_point(latitude_point,longitude_point,address,remark);
});