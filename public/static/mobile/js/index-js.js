$(document).ready(function () {
    var pcity = localStorage.getItem("pcity");
    if (pcity == null) {
        localStorage.setItem("pcity", "贵阳");
        localStorage.setItem("parea", "头桥公交车站");
        localStorage.setItem("slng", "106.682134");
        localStorage.setItem("slat", "26.62631");
        localStorage.setItem("rcity", "贵阳");
        localStorage.setItem("rarea", "头桥公交车站");
        localStorage.setItem("elng", "106.682134");
        localStorage.setItem("elat", "26.62631");
        // // 百度地图API功能
        // var map = new BMap.Map("allmap");
        // var point = new BMap.Point(106.717801, 26.58804);
        // map.centerAndZoom(point, 12);
        // var geolocation = new BMap.Geolocation();
        // geolocation.getCurrentPosition(function (r) {
        //     if (this.getStatus() == BMAP_STATUS_SUCCESS) {
        //         // console.log(r);
        //         // alert('您的位置：'+r.point.lng+','+r.point.lat);
        //         $.ajax({
        //             url: website + "/api/area/get_city_lng_lat.html",
        //             type: "GET",
        //             async: true,
        //             data: {
        //                 // $lng = '', $lat = ''
        //                 lng: r.point.lng,
        //                 lat: r.point.lat
        //             },
        //             timeout: 5000,
        //             dataType: "json",
        //             beforeSend: function (xhr) {
        //             },
        //             success: function (data) {
        //                 if (parseInt(data.code) == 0) {
        //                     localStorage.setItem("pcity", data.data.city);
        //                     localStorage.setItem("parea", data.data.description);
        //                     localStorage.setItem("slng", data.data.lng);
        //                     localStorage.setItem("slat", data.data.lat);
        //                     localStorage.setItem("rcity", data.data.city);
        //                     localStorage.setItem("rarea", data.data.description);
        //                     localStorage.setItem("elng", data.data.lng);
        //                     localStorage.setItem("elat", data.data.lat);
        //                     window.location.reload();
        //                 }
        //             },
        //             error: function (xhr, textStatus) {
        //             },
        //             complete: function () {
        //             }
        //         });
        //     }
        //     else {
        //         alert('failed' + this.getStatus());
        //     }
        // }, {enableHighAccuracy: true});
    }
    var acquire_time = localStorage.getItem("acquire_time");
    var return_time = localStorage.getItem("return_time");
    var nowtimestamp = Date.parse(new Date());
    if (acquire_time == null || (Date.parse(acquire_time) < nowtimestamp)) {
        acquire_time = getNowFormatDate(0);
        return_time = getNowFormatDate(2);
        localStorage.setItem("acquire_time", acquire_time);
        localStorage.setItem("return_time", return_time);
    }
    //时间选择器选择初始化及界面初始化
    $("#j_pdata_ymd").html(getMD(acquire_time));
    $("#j_pdata_ymd").attr("data-time", acquire_time);
    $("#j_pdata_whm").html(getWHM(acquire_time));

    $("#j_rdata_ymd").html(getMD(return_time));
    $("#j_rdata_ymd").attr("data-time", return_time);
    $("#j_rdata_whm").html(getWHM(return_time));

    $("#j_day").html(getDaysHoursMinutes(acquire_time, return_time));

    var is_return = localStorage.getItem('is_return');
    if (is_return == 1) {
        $("#isReturn-flag").html("");
        $("#isReturn-flag").addClass("colorGre");
        //“异地还车”的城市、区域的选择
        $("#rCityDiv").css({"display": "block"});
    } else {
        $("#isReturn-flag").html("");
        $("#isReturn-flag").removeClass("colorGre");
        $("#rCityDiv").css({"display": "none"});
        localStorage.setItem('is_return', 0);
    }
    //“异地还车”选择按钮
    $("#isReturn").on("click", function () {
        //“异地还车”前面的图标
        if ($("#rCityDiv").css("display") == "none") {
            localStorage.setItem('is_return', 1);
            $("#isReturn-flag").html("");
            $("#isReturn-flag").addClass("colorGre");
            //“异地还车”的城市、区域的选择
            $("#rCityDiv").css({"display": "block"});
        } else {
            localStorage.setItem('is_return', 0);
            $("#isReturn-flag").html("");
            $("#isReturn-flag").removeClass("colorGre");
            $("#rCityDiv").css({"display": "none"});
        }
    });

    //获取取还车的城市和区域值
    var j_pcity_text = $("#j_pcity_text").text();
    var j_parea_text = $("#j_parea_text").text();
    var j_rcity_text = $("#j_rcity_text").text();
    var j_rarea_text = $("#j_rarea_text").text();

    //对取还车城市与区域的控件做初始化或选择数据的接收
    $("#j_pcity_text").html(localStorage.getItem("pcity"));
    $("#j_parea_text").html(localStorage.getItem("parea"));
    $("#j_rcity_text").html(localStorage.getItem("rcity"));
    $("#j_rarea_text").html(localStorage.getItem("rarea"));

    //按钮“立即选择”
    $("#btnSearch").on("click", function () {
        if($(".js-cartype-tradition").hasClass("curr")){
            localStorage.setItem("goods_type", "1");
            location.href = "carsearch_tradition.html";
        }else {
            localStorage.setItem("goods_type", "2");
            location.href = "carsearch_newenergy.html";
        }
    });

    //跳转至取车城市界面
    $("#j_pcity").on("click", function (ev) {
        j_pcity_text = $("#j_pcity_text").text();
        localStorage.setItem("pcity", j_pcity_text);
        location.href = "city_takecar.html?pcity=" + j_pcity_text;
    });

    //跳转至取车区域界面
    $("#j_parea").on("click", function (ev) {
        j_parea_text = $("#j_parea_text").text();
        j_pcity_text = $("#j_pcity_text").text();
        localStorage.setItem("pcity", j_pcity_text);
        localStorage.setItem("parea", j_parea_text);
        location.href = "area_takecar.html?pcity=" + j_pcity_text + "&parea" + j_parea_text;
    });

    //跳转至还车城市界面
    $("#j_rcity").on("click", function (ev) {
        j_rcity_text = $("#j_rcity_text").text();
        localStorage.setItem("rcity", j_rcity_text);
        location.href = "city_returncar.html?rcity=" + j_rcity_text;
    });

    //跳转至还车区域界面
    $("#j_rarea").on("click", function (ev) {
        j_rarea_text = $("#j_rarea_text").text();
        j_rcity_text = $("#j_rcity_text").text();
        localStorage.setItem("rcity", j_rcity_text);
        localStorage.setItem("rarea", j_rarea_text);

        location.href = "area_returncar.html?rcity=" + j_rcity_text + "&rarea=" + j_rarea_text;
    });

    //跳转至我的订单界面
    $("#index_order").on("click", function () {
        location.href = 'order_mine.html';
    });

    //跳转至我的订单界面
    $("#index_map").on("click", function () {
        location.href = 'map_choose_car.html';
    });

    //常规租车按钮
    $(".js-cartype-tradition").on("click",function () {
        $(".js-cartype-tradition").addClass("colorVi");
        $(".js-cartype-tradition").addClass("curr");
        $(".js-cartype-newenergy").removeClass("colorVi");
        $(".js-cartype-newenergy").removeClass("curr");
        $(this).parent().removeClass("searTabxt1");
        $(this).parent().addClass("searTabxt");
        localStorage.setItem("goods_type", "1");
        $(".timer").show();
        $("#js_goto_qrcode_or_license").hide();
    });
    //纯电动车按钮
    $(".js-cartype-newenergy").on("click",function () {
        $(".js-cartype-newenergy").addClass("colorVi");
        $(".js-cartype-newenergy").addClass("curr");
        $(".js-cartype-tradition").removeClass("colorVi");
        $(".js-cartype-tradition").removeClass("curr");
        $(this).parent().addClass("searTabxt1");
        $(this).parent().removeClass("searTabxt");
        $(".timer").hide();
        $("#js_goto_qrcode_or_license").show();
        localStorage.setItem("goods_type", "2");
    });
});


