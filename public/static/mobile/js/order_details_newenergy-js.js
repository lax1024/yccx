var timer_wait = null;
$(document).ready(function () {

    // localStorage.setItem("goods_id","231");

    var pic_error = ["左前", "右前", "后方"];
    var pic_url = new Array();
    var use_pic_img = $("#js_pic_parent").find("img");
    var driverId = localStorage.getItem("driverId");
    var goods_id = localStorage.getItem("goods_id");
    var order_id = "";
    getDriver(driverId);
    //获取预定信息,做界面初始化
    //记录上一个页面(或者返回时到达的页面)
    if (getReferrUrl() != "contactlist.html" && getReferrUrl() != "map_choose_store.html") {
        //如果上一个页面是驾驶员信息列表页，那么就不需要返回到驾驶员信息页
        localStorage.setItem("lastPage", getReferrUrl());
    }

    //“立即用车”按钮的点击事件
    $("#gopay").on("click", function (ev) {
        submit_order();
    });

    //提交订单(立即用车)
    //显示协议：如果驾驶员信息为空，提示完善驾驶员，否则显示用户协议
    function submit_order() {
        var flag = false;
        for (var i = 0; i < use_pic_img.length; i++) {
            if ($(use_pic_img[i]).attr("data-picUrl") == "") {
                mui.alert(pic_error[i] + "图片为空", '提示');
                flag = false;
                return;
            } else {
                pic_url[i] = $(use_pic_img[i]).attr("data-picUrl");
                flag = true;
            }
        }

        pic_url[3] = $("#js_pic_invoice").attr("data-picUrl");

        var car_pic_add = $(".car_pic_add");
        for (var i = 0; i < car_pic_add.length; i++) {
            pic_url[4] += $(car_pic_add[i]).attr("data-picUrl") + ",";
        }

        if (!flag) {
            mui.alert('使用该车辆，请先拍照上传', '提示');
        }
        if ($("#user_protocol").hasClass('user_protocol_no')) {
            mui.alert('使用该车辆，需要同意优车出行服务协议', '提示');
            return;
        }
        var vehicle_drivers = $("#uname").attr('data-value');
        var mobile_phone = $("#iponenumber").attr('data-value');
        var id_number = $("#cardnumber").attr('data-value');
        localStorage.setItem("vehicle_drivers", vehicle_drivers);
        localStorage.setItem("mobile_phone", mobile_phone);
        localStorage.setItem("id_number", id_number);

        if (vehicle_drivers == "" || mobile_phone == "" || id_number == "") {
            mui.alert('请完善驾驶员信息', '提示');
            setTimeout(function () {
                location.href = "add_user_info.html";
            }, 2000);
        } else {
            var goods_id = localStorage.getItem("goods_id");
            var acquire_store_id = localStorage.getItem("choose_store_id_take");
            $.ajax({
                url: website + "/api/Order/add_car_order.html",
                type: "POST",
                async: true,
                data: {
                    goods_id: goods_id,
                    goods_type: "2",
                    vehicle_drivers: vehicle_drivers,
                    mobile_phone: mobile_phone,
                    id_number: id_number,
                    acquire_store_id: acquire_store_id,
                    return_store_id: acquire_store_id,
                    left_front: pic_url[0],
                    right_front: pic_url[1],
                    car_back: pic_url[2],
                    ticket: pic_url[3],
                    other_pic: pic_url[4]
                },
                outTime: 5000,
                dataType: "json",
                success: function (data) {
                    if (parseInt(data.code) == 0) {
                        order_id = data.order_id;
                        $("#order_examine_model").show();
                        //开始计时器
                        timedCount(order_id);
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
                }
            });
        }
    }

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

    //根据驾驶员id获取驾驶员信息
    function getDriver(driverId) {
        $.ajax({
            url: website + '/api/Customer/get_customer_drive.html',//请求地址
            type: 'POST', //请求方式
            async: true,    //或false,是否异步
            data: {//请求参数
                drive_id: driverId,
                goods_type: "2"
            },
            timeout: 5000,    //超时时间
            dataType: 'json',    //返回的数据格式：json/xml/html/script/jsonp/text
            success: function (data) {//请求成功，做数据处理
                /**
                 * 数据(json类型)一级遍历
                 * data是请求到的数据
                 * index“键”
                 * value是一级“值”
                 */
                if (parseInt(data.code) == 0) {
                    $("#uname").text(nama_hide(data.data.vehicle_drivers));
                    $("#uname").attr('data-value', data.data.vehicle_drivers);
                    $("#cardnumber").text(idcard_hide(data.data.id_number));
                    $("#cardnumber").attr('data-value', data.data.id_number);
                    $("#iponenumber").text(phone_hide(data.data.mobile_phone));
                    $("#iponenumber").attr('data-value', data.data.mobile_phone);
                    getOrderinfo(goods_id);
                } else if (parseInt(data.code) == 3) {
                    mui.alert(data.info + "实名制信息", "温馨提示", "确定", function () {
                        window.location.href = "add_user_info.html";
                    });
                } else if (parseInt(data.code) == 2) {
                    mui.alert(data.info, "温馨提示", "确定", function () {
                        window.history.back();
                    });
                }
            }
        });
    }

    //获取订单基本信息:根据用户提交的参数信息
    function getOrderinfo(goods_id) {
        //判断是否可以提交订单
        $.ajax({
            url: website + "/api/Order/is_pay_order.html",
            type: "POST",
            async: true,
            data: {
                goods_type: "2"
            },
            outTime: 5000,
            dataType: "json",
            success: function (data) {
                if (parseInt(data.code) == 0) {
                    get_reserve("http://www.youchedongli.cn/api/customer/get_reserve.html", goods_id);
                } else if (parseInt(data.code) == 5) {
                    Toast(data.info, 2000);
                    setTimeout(function () {
                        location.href = "user_cash.html";
                    }, 3000);
                } else if (parseInt(data.code) == 3) {
                    Toast(data.info, 2000);
                    setTimeout(function () {
                        location.href = "add_user_info.html";
                    }, 3000);
                } else if (parseInt(data.code) == 1112) {
                    Toast(data.info, 2000);
                    setTimeout(function () {
                        location.href = "order_mine.html";
                    }, 3000);
                }
            }
        });
        get_car_info(goods_id);
    }

    var scond = 0;
    var timer;

    function get_car_info(goods_id) {
        $.ajax({
            url: "http://www.youchedongli.cn/api/Car/get_car_id.html",
            type: "POST",
            async: true,
            data: {
                id: goods_id
            },
            outTime: 5000,
            dataType: "json",
            success: function (data) {
                // console.log("成功");
                if (parseInt(data.code) == 0) {
                    // console.log(data);
                    var data = data.data;
                    var brand = $("<p style='text-align: right' class=\"vehDesc colorF\">\n" +
                        "                <span class=\"medi size16\" style='position: absolute;right: 10px;bottom: 26px;background: #FFFFFE;color: #333;border-radius: 3px;'>\n" +
                        data.cartype_name +
                        "                </span>\n" +
                        "            </p>");

                    $(".vehImgMod").find("p").html(brand);
                    $(".vehImgMod").find("img").attr("src", "http://img.youchedongli.cn/public/" + data.series_img + "?x-oss-process=image/resize,w_500");
                    $("#js-total-price").text(parseFloat(data.day_price).toFixed(1) + "元/时");
                    $("#js-km-price").text("+" + parseFloat(data.km_price).toFixed(1) + "元/公里");
                    $(".js_return_car_name").html("车辆门店: " + data.store.store_name);
                    $(".js_return_car_address").html("门店位置: " + data.store.address);
                    $(".js_car_plate").text(data.licence_plate);

                    var store_park_price = data.store.store_park_price;

                    if (parseFloat(store_park_price) > 0) {
                        $("#js_remark").show();
                        $("#js_take_park_remark").text(data.store.take_park_remark);
                    } else {
                        $("#js_remark").hide();
                    }

                    var imgs = data.store.store_imgs;
                    if (imgs.length > 0) {
                        $(".js_store_img").attr("src", "http://img.youchedongli.cn" + imgs[0]);
                        $(".js_store_img").attr("data-preview-src", "http://img.youchedongli.cn" + imgs[0]);
                        $(".js_store_img").attr("data-preview-group", "return_car_place");
                        for (var i = 1; i < imgs.length; i++) {
                            $("#js_return_car_place_img").append("<img class='js_store_img' " +
                                "src='http://img.youchedongli.cn'" + imgs[i] + " style='display: none'" +
                                "data-preview-src='' data-preview-group='store_img'>");
                        }
                    } else {
                        $(".js_store_img").attr("src", "http://img.youchedongli.cn/public/static/mobile/images/img_return_car_no.png");
                    }
                }
            }
        });
        getPicLastOrder(goods_id);
    }

    //计时器（下单前审核框）
    function timedCount(order_id) {
        $("#js_examine_timer").text("(" + scond + "秒)");
        scond = scond + 1;
        timer = setTimeout(function () {
            timedCount(order_id);
            if (scond % 3 == 0) {
                $.ajax({
                    url: website + "/api/Order/wait_order.html",
                    type: "POST",
                    async: true,
                    data: {
                        order_id: order_id
                    },
                    timeout: 5000,
                    dataType: "json",
                    success: function (data) {
                        Toast(data.info, 2000);
                        if (parseInt(data.code) == 0) {
                            // Toast("审核通过，立即用车", 2000);
                            $("#order_examine_model").hide();
                            window.location.href = "order_ele_key.html?order_id=" + order_id;
                            //完成(结束)预定
                            //end_reserve("http://www.youchedongli.cn/api/customer/end_reserve.html", goods_id);
                        } else if (parseInt(data.code) == 901) {//等待审核
                            $("#js_examine_timer_wait").text(data.info);
                        } else if (parseInt(data.code) == 101) {//车机掉线
                            location.href = "map_choose_car.html";
                        } else {
                            $("#js_examine_timer_wait").text(data.info);
                            stopCount();
                        }
                    },
                    errer: function () {

                    }
                });
            }
            //审核超时
            if (scond == 20) {
                stopCount();
                Toast("审核未通过，下单失败", 2000);
                $("#order_examine_model").hide();
            }
        }, 1000);
    }

    //结束计时器
    function stopCount() {
        clearTimeout(timer);
    }

    //下单审核框中的取消按钮点击事件
    $("#js-cancel-btn").on("click", function () {
        stopCount();
        scond = 0;
        $("#order_examine_model").hide();
    });

    //免费等待倒计时
    function wait_timer(times) {
        timer_wait = setInterval(function () {
            var minute = 15,
                second = 0;//时间默认值
            if (times > 0) {
                minute = Math.floor(times / 60);
                second = Math.floor(times % 60);
            }
            if (minute <= 9) minute = '0' + minute;
            if (second <= 9) second = '0' + second;
            //
            $("#js_order_wait_timer").text("00:" + minute + ":" + second);
            times--;
            if (times <= 0) {
                clearInterval(timer_wait);
                $("#js_order_wait_timer").text("00:00:00");
                cancel_reserve("http://www.youchedongli.cn/api/customer/cancel_reserve.html", goods_id);
                location.href = "map_choose_car.html";
            }
        }, 1000);
    }

    //取消预定点击事件
    $("#js_reserve_cancel").on("click", function () {
        cancel_reserve("http://www.youchedongli.cn/api/customer/cancel_reserve.html", goods_id);
    });

    //寻车点击事件
    $("#js_find_car_by_id").on("click", function () {
        control_car_by_id("http://www.youchedongli.cn/api/customer/find_car.html", goods_id);
    });

    //添加订单预定
    function add_reserve(url, car_id) {
        ajax_function(url, car_id, 1);
    }

    //取消订单预定
    function cancel_reserve(url, car_id) {
        ajax_function(url, car_id, 2);
    }

    //获取预定信息
    function get_reserve(url, car_id) {
        ajax_function(url, car_id, 3);
    }

    //完成预定信息
    function end_reserve(url, car_id) {
        ajax_function(url, car_id, 4);
    }

    //车辆控制(鸣笛/找车)
    function control_car_by_id(url, car_id) {
        ajax_function(url, car_id, 5);
    }

    //预定处理函数
    function add_reserve_handle(data, car_id) {
        if (data.code == 0) {
            wait_timer(900);
            goods_id = data.data.goods_id;
            get_car_info(goods_id);
        } else {
            Toast(data.info, 2000);
            $("#js_order_wait_timer").css("font-size", '14px');
            $("#js_order_wait_timer").text("无预定信息");
        }
    }

    //取消预定处理函数
    function cancel_reserve_handle(data) {
        Toast(data.info, 2000);
        if (data.code == 0) {
            clearInterval(timer_wait);
            location.href = "map_choose_car.html";
        }
    }

    //获取预定信息处理函数
    function get_reserve_handle(data, car_id) {
        if (data.code == 0) {
            var data = data.data;
            wait_timer(data.surplus_time);
            goods_id = data.goods_id;
            get_car_info(goods_id);
        } else {
            add_reserve("http://www.youchedongli.cn/api/customer/add_reserve.html", car_id);
        }
    }

    function update_reserve_handle(data) {
        if (data.code == 0) {
            var data = data.data;
            clearTimeout(timer_wait);
            wait_timer(data.surplus_time);
        } else {
            clearTimeout(timer_wait);
            $("#js_order_wait_timer").css("font-size", '14px');
            $("#js_order_wait_timer").text("无预定信息");
        }
    }

    //完成预定信息处理函数
    function end_reserve_handle(data, car_id) {
        if (data.code == 0) {
            window.location.href = "order_ele_key.html?order_id=" + order_id;
        }
    }

    //操作车辆处理函数
    function control_car_handle(data) {
        Toast(data.info, 2000);
    }

    //车辆预定请求函数
    function ajax_function(url, car_id, index) {
        $.ajax({
            url: url,
            type: "GET",
            async: true,
            data: {
                car_id: car_id
            },
            timeout: 5000,
            dataType: "json",
            success: function (data) {
                if (index == 1) {
                    add_reserve_handle(data, car_id);
                } else if (index == 2) {
                    cancel_reserve_handle(data);
                } else if (index == 3) {
                    get_reserve_handle(data, car_id);
                } else if (index == 4) {
                    end_reserve_handle(data, car_id);
                } else if (index == 5) {
                    control_car_handle(data);
                } else if (index == 6) {
                    update_reserve_handle(data);
                }
            }
        });
    }

    // mui.back = function () {
    //     alert("back");
    //     if ($("#popover").css('display') == 'block') {
    //         $("#popover").hide();
    //         pushHistory();
    //     } else if (document.querySelector(".mui-preview-in")) {
    //         mui.previewImage().close();
    //     } else {
    //         window.history.back();
    //     }
    // };


    $("#openPopover").on("click", function () {
        pushHistory();//进栈
    });


    //物理返回按钮
    // pushHistory();//进栈
    //物理返回键的点击事件监听
    window.addEventListener("popstate", function (e) {
        // alert("1");
        if ($("#popover").css('display') == 'block') {
            $("#popover").hide();
            window.history.back();
            // pushHistory();
        } else if (document.querySelector(".mui-preview-in")) {
            mui.previewImage().close();
            // pushHistory();
        }else {
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

    document.addEventListener('visibilitychange', function () {
        var isHidden = document.hidden;
        if (isHidden) {
        } else {
            ajax_function("http://www.youchedongli.cn/api/customer/get_reserve.html", goods_id, 6);
        }
    });

    //获取上一个订单还车时拍的照片
    function getPicLastOrder(goods_id) {
        $.ajax({
            url: website + "/api/Order/get_car_order_img.html",
            type: "GET",
            async: true,
            data: {
                goods_id: goods_id
            },
            timeout: 5000,
            dataType: "json",
            success: function (data) {
                if (parseInt(data.code) == 0) {
                    var data = data.data;
                    $("#js_last_pic").show();
                    $("#js_last_pic_inner").attr("src", data.left_front);
                    $("#js_last_pic_inner").attr("data-preview-src", data.left_front);
                    $("#js_last_pic_inner").attr("data-preview-group", "pic_last_order");

                    $("#js_last_pic_left_front").attr("src", data.right_front);
                    $("#js_last_pic_left_front").attr("data-preview-src", data.right_front);
                    $("#js_last_pic_left_front").attr("data-preview-group", "pic_last_order");

                    $("#js_last_pic_right_front").attr("src", data.car_back);
                    $("#js_last_pic_right_front").attr("data-preview-src", data.car_back);
                    $("#js_last_pic_right_front").attr("data-preview-group", "pic_last_order");

                    $("#js_last_pic_back").attr("src", data.interior);
                    $("#js_last_pic_back").attr("data-preview-src", data.interior);
                    $("#js_last_pic_back").attr("data-preview-group", "pic_last_order");

                    if (data.ticket != "" && data.ticket != undefined) {
                        $("#js_invoice").show();
                        $("#js_last_pic_invoice").attr("src", data.ticket);
                        $("#js_last_pic_invoice").attr("data-preview-src", data.ticket);
                        $("#js_last_pic_invoice").attr("data-preview-group", "pic_last_order");

                    } else {
                        $("#js_invoice").hide();
                    }
                } else {
                    $("#js_last_pic").hide();
                    // Toast(data.info, 2000);
                }
            }
        });
    }
});
