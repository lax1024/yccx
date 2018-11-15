$(document).ready(function () {
    //返回控件
    $(".topBack").on("click", function () {
        localStorage.setItem("licence_plate", "");
        localStorage.setItem("choose_store_id_take", "");
        localStorage.setItem("choose_store_id_return", "");
        location.href = getReferrUrl();
    });

    //控制页面高度不被输入法顶起
    $('body').height($('body')[0].clientHeight);

    //获取用户选择的车辆车牌号并做进一步操作
    var license_location = localStorage.getItem("license_map_car");
    if (isLicenseNo(license_location)) {
        $('#input_license').val(license_location);
        getStoreByLicense(license_location);
    }

    //根据车牌号查询车辆相关信息：店铺(取车)，车型图片，车辆品牌等
    function getStoreByLicense(license) {
        $.ajax({
            url: website + "/api/car/get_qrcode_car.html",
            type: "GET",
            async: true,
            data: {
                licence_plate: license
            },
            timeout: 5000,
            dataType: "json",
            beforeSend: function (xhr) {
            },
            success: function (data) {
                // Toast("获取数据成功！",2000);
                if (parseInt(data.code) == 0) {
                    localStorage.setItem("licence_plate", license);
                    localStorage.setItem("order_id_new", data.data.id);
                    localStorage.setItem("choose_store_id_take", data.data.store_key_id);
                    var data = data.data;
                    $("#js_info_sure").show();
                    $("#js_info_error").hide();

                    $("#js_series_img").attr("src", "http://img.youchedongli.cn/public/" + data.series_img + "?x-oss-process=image/resize,h_280");
                    $("#js_brand_name").text(data.series_name);
                    $("#js_cartype_name").text(data.cartype_name);
                    $("#js_day_price").text(data.day_price + "元/小时");
                    $("#js_license_plate").text(data.licence_plate);

                    $("#js_take_store_site_name").text(data.store_site_name);
                } else {

                    $("#js_info_sure").hide();
                    $("#js_info_error").show();

                    $("#js_info_error").find(".car_info").text(data.info);
                }
            },
            error: function (xhr, textStatus) {
            },
            complete: function () {
            }
        });
    }

    //取消用车
    $("#js_info_sure_cancel").on("click", function () {
        localStorage.setItem("licence_plate", "");
        localStorage.setItem("choose_store_id_take", "");
        localStorage.setItem("choose_store_id_return", "");
        // location.href = "map_choose_car.html";
        location.href = 'map_choose_car.html';
    });
    //确认用车
    $("#js_info_sure_sure").on("click", function () {
        // if ($("#js_return_store_site_name").text() == "点击选择还车门店") {
        //     Toast("请先选择还车门店");
        // } else {
            location.href = "order_fillIn_newenergy.html";
        // }
    });
    //选择还车门店(已屏蔽)
    $("#js_return_store_site_name").on("click", function () {
        location.href = "map_choose_store.html";
    });
    //车辆车牌号校验
    function isLicenseNo(str) {
        return /^[京津沪渝冀豫云辽黑湘皖鲁新苏浙赣鄂桂甘晋蒙陕吉闽贵粤青藏川宁琼使领A-Z]{1}[A-Z]{1}[A-Z0-9]{4}[A-Z0-9挂学警港澳]{1}$/.test(str);
    }

    //获取门店(还车)信息：这里只需要门店名称
    /*$.ajax({
        url: website + "/api/common/get_store_id.html",
        type: "GET",
        async: true,
        data: {
            id: localStorage.getItem("choose_store_id_return")
        },
        timeout: 5000,
        dataType: "json",
        beforeSend: function (xhr) {
        },
        success: function (data) {
            if (parseInt(data.code) == 0) {
                $("#js_return_store_site_name").text(data.data.data.store_name);
            }
        },
        error: function (xhr, textStatus) {
        },
        complete: function () {
        }
    });*/

});
