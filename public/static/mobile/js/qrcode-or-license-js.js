$(document).ready(function () {
    //二维码扫描按钮的点击事件
    $("#car_qrcode").on('click', function () {
        wx.scanQRCode({
            needResult: 1,
            desc: 'scanQRCode desc',
            success: function (data) {
                if(isLicenseNo(data.resultStr)){
                    localStorage.setItem("license_map_car",data.resultStr);
                    location.href = "map_usecar_newenergy.html";
                }else{
                    ToastModel("扫码提示","二维码识别有误",2000);
                }
            }
        });
    });
    //返回按钮
    $(".topBack").on("click",function () {
        localStorage.setItem("licence_plate","");
        localStorage.setItem("choose_store_id_take","");
        localStorage.setItem("choose_store_id_return","");
        location.href = getReferrUrl();
    });
    //控制页面高度不被输入法顶起
    $('body').height($('body')[0].clientHeight);
    //初始化车牌号输入框
    /*
    var license_location = localStorage.getItem("licence_plate");
    if(license_location != ""){
        $('#input_license').val(license_location);
        getStoreByLicense(license_location);
    }*/
    //输入车牌号的输入框的输入监听器
    $('#input_license').bind('input propertychange', function() {
        var license = $("#input_license").val();
        if(license.length > 0){
            if(isLicenseNo(license)){
                getStoreByLicense(license);
            }else {
                $("#js_info_error").show();
                $("#js_info_sure").hide();
                $("#js_info_error").find(".car_info").text("车牌号输入不正确，注意格式...");
            }
        }else {
            $("#js_info_error").hide();
            $("#js_info_sure").hide();
        }
    });
    //根据车牌号获取门店信息(车辆所属门店的信息)
    function getStoreByLicense(license){
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
                    console.log(data);
                    localStorage.setItem("licence_plate",license);
                    localStorage.setItem("goods_id",data.data.id);
                    localStorage.setItem("choose_store_id_take",data.data.store_site_id);
                    var data = data.data;
                    $("#js_info_sure").show();
                    $("#js_info_error").hide();
                    $("#js_series_img").attr("src", "http://img.youchedongli.cn/public/"+ data.series_img + "?x-oss-process=image/resize,h_280");
                    $("#js_brand_name").text(data.series_name);
                    $("#js_cartype_name").text(data.cartype_name);
                    $("#js_day_price").text(data.day_price + "元/小时");
                    $("#js_license_plate").text(data.licence_plate);
                    $("#js_take_store_site_name").text(data.store_site_name);
                }else {
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
    //取消用车按钮
    $("#js_info_sure_cancel").on("click",function () {
        localStorage.setItem("licence_plate", "");
        $('#input_license').val("");
        $("#js_info_error").hide();
        $("#js_info_sure").hide();
    });
    //立即用车按钮
    $("#js_info_sure_sure").on("click",function () {
        location.href = "order_fillIn_newenergy.html";
    });
    //选择还车门店(已屏蔽)
    $("#js_return_store_site_name").on("click",function () {
        location.href = "map_choose_store.html";
    });
    //七位车牌号验证
    function isLicenseNo(str) {
        return /^[京津沪渝冀豫云辽黑湘皖鲁新苏浙赣鄂桂甘晋蒙陕吉闽贵粤青藏川宁琼使领A-Z]{1}[A-Z]{1}[A-Z0-9]{4}[A-Z0-9挂学警港澳]{1}$/.test(str);
    }
//根据本店id获取门店名(用于还车门店，已屏蔽)
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
