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

    //返回上一个页面(左上角的返回按钮功能)
    $("#goback").on("click", function (ev) {
        if(localStorage.getItem("goods_type") == "1"){
            location.href = "carsearch_tradition.html";
        }
        if(localStorage.getItem("goods_type") == "2"){
            location.href = "carsearch_newenergy.html";
        }
    });

    //生成订单并跳转至支付页面
    $("#gopay").on("click", function (ev) {
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

        $.ajax({
            url: website + "/api/Order/add_car_order.html",
            type: "POST",
            async: true,
            data: {
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
                }else {
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

    //人身及财物险的选择
    $("#switchBtn").on("click", function (ev) {
        if ($(ev.srcElement).hasClass("on")) {
            this.classList.remove("on");
        } else {
            this.classList.add("on");
        }
    });
    //支付方式的选择(在线支付 or 到店支付)
    $("#flexMa-Type").find("p").on("click", function () {
        var em = $(this).find("em")[2];
        em.classList.remove("colorEc");
        em.classList.add("fillVi");
        var otherPaytype = $(this).siblings();
        for (var i = 0; i < otherPaytype.length; i++) {
            var em2 = $(otherPaytype[i]).find("em")[2];
            em2.classList.add("colorEc");
            em2.classList.remove("fillVi");
        }
    });
    //跳转驾驶员选择页面
    $("#contact-icon").on("click", function (ev) {
        localStorage.setItem("is_choose_contactlist", 'choose');
        location.href = "contactlist.html";
    });

    //显示证件选择器
    $("#certificatetypes").on("click", function (ev) {
        $("#maskbody").css({"display": "block"});
        $("#ui-view-61").css({"display": "block"});
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

    function getDriver(driverId) {
        $.ajax({
            url: website + '/api/Customer/get_customer_drive.html',//请求地址
            type: 'POST', //请求方式
            async: true,    //或false,是否异步
            data: {//请求参数
                drive_id: driverId
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
                    $("#uname").val(data.data.vehicle_drivers);
                    $("#cardnumber").val(data.data.id_number);
                    $("#iponenumber").val(data.data.mobile_phone);

                } else {
                    Toast(data.info, 2000);
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

    $(".js-order-detail").on('click', function () {
        $("#js-order-detail-mask").fadeIn(200);
    });
    $("#js-order-detail-mask").on('click', function () {
        $("#js-order-detail-mask").fadeOut(200);
    });
    //获取订单基本信息
    function getOrderinfo() {
        var goods_id = localStorage.getItem("car_id");
        var acquire_store_id = localStorage.getItem("acquire_store_id");
        var return_store_id = localStorage.getItem("return_store_id");
        var acquire_time = localStorage.getItem("acquire_time");
        var return_time = localStorage.getItem("return_time");
        $.ajax({
            url: website + '/api/Common/calc_order_info.html',//请求地址
            type: 'POST', //请求方式
            async: true,    //或false,是否异步
            data: {//请求参数
                goods_id: goods_id,
                acquire_store_id: acquire_store_id,
                acquire_time: acquire_time,
                return_store_id: return_store_id,
                return_time: return_time
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
                    $(".js-total-price").text(data.data.goods_all);
                    $(".pickAddr").html("取车门店: " + data.data.acquire_store.store_name + "<em class='pickMark isdIco size13 fillVi'></em>");
                    $(".returnAddr").html("还车门店: " + data.data.return_store.store_name + "<em class='pickMark isdIco size13 fillVi'></em>");
                    // data-lat="20.007158" data-lng
                    $(".pickAddr").attr('data-lng', data.data.acquire_store.location_longitude);
                    $(".pickAddr").attr('data-lat', data.data.acquire_store.location_latitude);
                    $(".returnAddr").attr('data-lng', data.data.return_store.location_longitude);
                    $(".returnAddr").attr('data-lat', data.data.return_store.location_latitude);

                    $(".js-detail-goods-amount").text("￥" + data.data.goods_amount);
                    $(".js-detail-goods-basic").text("￥" + data.data.goods_basic);
                    $(".js-detail-goods-procedure").text("￥" + data.data.goods_procedure);
                    $(".js-detail-goods-remote").text("￥" + data.data.goods_remote);
                    $(".js-detail-goods-all").text("￥" + data.data.goods_all);

                } else {
                    Toast(data.info, 2000);
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

    getOrderinfo();
});
