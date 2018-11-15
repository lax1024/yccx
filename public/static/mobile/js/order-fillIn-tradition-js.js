$(document).ready(function () {
    $(".vehImgMod").find("img").attr("src", localStorage.getItem("chooce_car_imgUrl") + "?x-oss-process=image/resize,h_280");

    var brand = $("<p class=\"vehDesc colorF\">\n" +
        "                <span class=\"medi size16\">\n" +
        localStorage.getItem("brand_name") +
        "                </span>\n" +
        localStorage.getItem("cartype_name") +
        "            </p>");

    $(".vehImgMod").find("p").html(brand);
    var acquire_time = localStorage.getItem("acquire_time");
    var return_time = localStorage.getItem("return_time");
    $("#ptime").text(getMDHMS(acquire_time));
    $("#rtime").text(getMDHMS(return_time));
    var driverId = localStorage.getItem("driverId");
    getDriver(driverId);
    $(".pickDur").html(getDaysHoursMinutes(acquire_time, return_time));

    //记录上一个页面(或者返回时到达的页面)
    if (getReferrUrl() != "contactlist.html") {
        //如果上一个页面是驾驶员信息列表页，那么就不需要返回到驾驶员信息页
        localStorage.setItem("lastPage", getReferrUrl());
    }

    //返回上一个页面(左上角的返回按钮功能)
    $("#goback").on("click", function (ev) {
        location.href = localStorage.getItem("lastPage");
    });


//显示协议：如果驾驶员信息为空，提示完善驾驶员，否则显示用户协议
    $("#gopay").on("click", function (ev) {

        var vehicle_drivers = $("#uname").attr('data-value');
        var mobile_phone = $("#iponenumber").attr('data-value');
        var id_number = $("#cardnumber").attr('data-value');

        localStorage.setItem("vehicle_drivers", vehicle_drivers);
        localStorage.setItem("mobile_phone", mobile_phone);
        localStorage.setItem("id_number", id_number);

        if (vehicle_drivers == "" || mobile_phone == "" || id_number == "") {
            Toast("请完善驾驶员信息", 2000);
        } else {
            //显示用户协议
            $("#js_user_agreement").fadeIn(200);
        }
    });
    $("#js-protocol").on('click', function () {
        $("#js_user_agreement").fadeIn(200);
        $("#js_agreement_submit").hide();
        $("#js_agreement_cancel").css('width', '98%')
    });
    //同意用户协议后提交订单信息，生成订单
    $("#js_agreement_submit").on('click', function () {
        $("#js_user_agreement").fadeOut(200);
        var goods_id = localStorage.getItem("car_id");

        var vehicle_drivers = $("#uname").attr('data-value');
        var mobile_phone = $("#iponenumber").attr('data-value');
        var id_number = $("#cardnumber").attr('data-value');

        var acquire_store_id = localStorage.getItem("acquire_store_id");
        var return_store_id = localStorage.getItem("return_store_id");
        var acquire_time = localStorage.getItem("acquire_time");
        var return_time = localStorage.getItem("return_time");
        $.ajax({
            url: website + "/api/Order/add_car_order.html",
            type: "POST",
            async: true,
            data: {
                goods_type: "1",
                goods_id: goods_id,
                vehicle_drivers: vehicle_drivers,
                mobile_phone: mobile_phone,
                id_number: id_number,
                acquire_store_id: acquire_store_id,
                return_store_id: return_store_id,
                acquire_time: acquire_time,
                return_time: return_time
            },
            outTime: 5000,
            dataType: "json",
            beforeSend: function () {
                // console.log("发送前");
            },
            success: function (data) {
                // console.log("成功");
                if (parseInt(data.code) == 0) {
                    window.location.href = "order_pay.html?sn=" + data.data;
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
            error: function () {
                // console.log("失败");
            },
            complete: function () {
                // console.log("完成");
            }

        });
    });
    //取消协议后关闭协议窗口
    $("#js_agreement_cancel").on('click', function () {
        $("#js_user_agreement").fadeOut(200);
        $("#js_agreement_submit").show();
        $("#js_agreement_cancel").css('width', '46%')
    });


    //跳转驾驶员选择页面
    $("#contact-icon").on("click", function (ev) {
        localStorage.setItem("is_choose_contactlist", 'choose');
        localStorage.setItem("contactlist_parent", "order_fillIn_tradition.html");
        location.href = "contactlist.html";
    });

    //显示证件选择器
    $("#certificatetypes").on("click", function (ev) {
        // $("#maskbody").css({"display": "block"});
        // $("#ui-view-61").css({"display": "block"});
    });
    //隐藏证件选择器
    $("#ui-view-61").on("click", function (ev) {
        $("#maskbody").css({"display": "none"});
        $("#ui-view-61").css({"display": "none"});
    });
    //证件选择
    var ddlist = $("#maskbody").find("dd");
    ddlist.on("click", function (ev) {
        for (var i = 0; i < ddlist.length; i++) {
            if (this.getAttribute("data-index") == i) {
                $("#certificatetypes").find("span")[0].innerHTML = this.innerHTML;
                if ($(this).find("em").length < 1) {
                    $(ddlist[i]).append("<em class=\"isdIco colorGre size18\"></em>");
                }
                $("#maskbody").css({"display": "none"});
                $("#ui-view-61").css({"display": "none"});
            } else {
                $(ddlist[i]).find("em").remove();
            }
        }
    });
    //根据驾驶员id获取驾驶员信息
    function getDriver(driverId) {
        $.ajax({
            url: website + '/api/Customer/get_customer_drive.html',//请求地址
            type: 'POST', //请求方式
            async: true,    //或false,是否异步
            data: {//请求参数
                drive_id: driverId,
                goods_type: "1"
            },
            timeout: 5000,    //超时时间
            dataType: 'json',    //返回的数据格式：json/xml/html/script/jsonp/text
            beforeSend: function (xhr) {
                // console.log(xhr)
                // console.log('发送前')
            },
            success: function (data) {//请求成功，做数据处理
                /**
                 * 数据(json类型)一级遍历
                 * data是请求到的数据
                 * index“键”
                 * value是一级“值”
                 */
                if (parseInt(data.code) == 0) {
                    $("#uname").val(nama_hide(data.data.vehicle_drivers));
                    $("#uname").attr('data-value', data.data.vehicle_drivers);
                    $("#cardnumber").val(idcard_hide(data.data.id_number));
                    $("#cardnumber").attr('data-value', data.data.id_number);
                    $("#iponenumber").val(phone_hide(data.data.mobile_phone));
                    $("#iponenumber").attr('data-value', data.data.mobile_phone);
                } else {
                    // Toast(data.info, 2000);
                }

            },
            error: function (xhr, textStatus) {
                // console.log('错误');
                // console.log(xhr);
                // console.log(textStatus)
            },
            complete: function () {
                // console.log('结束');
            }
        });
    }

    //获取订单基本信息
    function getOrderinfo() {

        //判断是否可以提交订单
        $.ajax({
            url: website + "/api/Order/is_pay_order.html",
            type: "POST",
            async: true,
            data: {
                goods_type: "1"
            },
            outTime: 5000,
            dataType: "json",
            success: function (data) {
                if (parseInt(data.code) == 5) {
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
                }
            }
        });

        var goods_id = localStorage.getItem("car_id");
        var vehicle_drivers = $("#uname").val();
        var mobile_phone = $("#iponenumber").val();
        var id_number = $("#cardnumber").val();
        var acquire_store_id = localStorage.getItem("acquire_store_id");
        var return_store_id = localStorage.getItem("return_store_id");
        var acquire_time = localStorage.getItem("acquire_time");
        var return_time = localStorage.getItem("return_time");

        localStorage.setItem("vehicle_drivers", vehicle_drivers);
        localStorage.setItem("mobile_phone", mobile_phone);
        localStorage.setItem("id_number", id_number);

        // * goods_id 商品id
        // * goods_type 商品类型
        // * acquire_store_id 取车门店id
        // * acquire_time 取车时间
        // * return_store_id 还车门店id
        // * return_time 还车时间
        $.ajax({
            url: website + "/api/Common/calc_order_info.html",
            type: "POST",
            async: true,
            data: {
                goods_id: goods_id,
                acquire_store_id: acquire_store_id,
                return_store_id: return_store_id,
                acquire_time: acquire_time,
                return_time: return_time
            },
            outTime: 5000,
            dataType: "json",
            beforeSend: function () {
                // console.log("发送前");
            },

            success: function (data) {
                // console.log("成功");
                if (parseInt(data.code) == 0) {
                    var data = data.data;
                    $(".js-total-price").text("￥" + data.goods_all);
                    $(".pickAddr").html("取车门店: " + data.acquire_store.store_name + "<em class='pickMark isdIco size13 fillVi'></em>");
                    $(".returnAddr").html("还车门店: " + data.return_store.store_name + "<em class='pickMark isdIco size13 fillVi'></em>");
                    // data-lat="20.007158" data-lng
                    //
                    $(".pickAddr").attr('data-lng', data.acquire_store['location_longitude']);
                    $(".pickAddr").attr('data-lat', data.acquire_store['location_latitude']);
                    $(".returnAddr").attr('data-lng', data.return_store['location_longitude']);
                    $(".returnAddr").attr('data-lat', data.return_store['location_latitude']);

                    $(".js-detail-goods-amount").text("￥" + data.goods_amount);
                    $(".js-detail-goods-basic").text("￥" + data.goods_basic);
                    $(".js-detail-goods-procedure").text("￥" + data.goods_procedure);
                    $(".js-detail-goods-remote").text("￥" + data.goods_remote);
                    $(".js-detail-goods-all").text("￥" + data.goods_all);
                } else {
                    Toast(data.info, 2000);
                }
            },
            error: function () {
                // console.log("失败");
            },
            complete: function () {
                // console.log("完成");
            }

        });
    }

    getOrderinfo();

    $(".js-order-detail").on("click", function () {
        $("#js-order-detail-mask").fadeIn(200);
    });
    $("#js-order-detail-mask").on("click", function () {
        $("#js-order-detail-mask").fadeOut(250);
    });

});
