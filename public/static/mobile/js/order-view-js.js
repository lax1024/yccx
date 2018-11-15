$(document).ready(function () {
    //获取界面左上角的返回按钮并设置相应的返回功能
    $("#goback").on("click", function (ev) {
        window.history.back();
    });

    $("#js-feedetail").on('click', function () {
        $("#js-feedetailmask").fadeIn(200);
    });

    $("#js-feedetailmask").on('click', function () {
        $("#js-feedetailmask").fadeOut(200);
    });

    $("#js-morenotice").on('click', function () {

    });
    var id = getValueByParameter(location.href, 'id');
    $.ajax({
        url: website + '/api/Order/get_order_info.html',//请求地址
        type: 'GET', //请求方式
        async: true,    //或false,是否异步
        data: {//请求参数
            id: id
        },
        timeout: 5000,    //超时时间
        dataType: 'json',    //返回的数据格式：json/xml/html/script/jsonp/text
        beforeSend: function (xhr) {
//                    console.log(xhr);
//                    console.log('发送前');
        },
        success: function (data) {//请求成功，做数据处理
            /**
             * 数据(json类型)一级遍历
             * data是请求到的数据
             * keyWord是一级“键”(A-Z)
             * value是一级“值”(城市的相关信息)
             */
            if (parseInt(data.code) == 0) {
                var json_data = data.data;
                $(".js-order-sn").text(json_data.order_sn);
                if (parseInt(json_data.order_status) == 0) {
                    $("#js-order-right-btn").on('click', function () {
                        window.location.href = "index.html";
                    });
                } else if (parseInt(json_data.order_status) == 10) {
                    $("#js-order-left-btn").text('取消订单');
                    $("#js-order-right-btn").text('立即支付');
                    $("#js-order-right-btn").on('click', function () {
                        window.location.href = "order_pay.html?sn=" + json_data.order_sn;
                    });
                } else if (parseInt(json_data.order_status) == 20) {
                    $("#js-order-left-btn").text('咨询订单');
                    $("#js-order-right-btn").text('等待取车');
                } else if (parseInt(json_data.order_status) == 30) {
                    $("#js-order-left-btn").text('咨询订单');
                    $("#js-order-right-btn").text('等待还车');
                } else if (parseInt(json_data.order_status) == 40) {
                    $("#js-order-left-btn").text('咨询订单');
                    $("#js-order-right-btn").text('等待确认');
                } else if (parseInt(json_data.order_status) == 50) {
                    $("#js-order-left-btn").text('咨询订单');
                    $("#js-order-right-btn").text('订单完成');
                }
                $(".js-order-status").text(json_data.order_status_str);
                $(".js-order-amount").text(parseFloat(json_data.order_amount).toFixed(2));
                $(".js-goods-img").attr('src', "http://img.youchedongli.cn/public/" + json_data.order_goods['goods_img']+"?x-oss-process=image/resize,h_150");
                $(".js-goods-name").text(json_data.order_goods['goods_name']);
                $(".js-acquire-time").text(json_data.acquire_time_str);
                $(".js-return-time").text(json_data.return_time_str);
                $(".js-user-time").text(numTotime(json_data.goods_sum));
                $(".js-acquire-store-name").text(json_data.store_key_name + "·" + json_data.acquire_store_name);
                $(".js-acquire-store-time").text(json_data.acquire_store['business_start'] + "-" + json_data.acquire_store['business_end']);
                $(".js-acquire-store-address").attr('data-lng', json_data.acquire_store['location_longitude']);
                $(".js-acquire-store-address").attr('data-lat', json_data.acquire_store['location_latitude']);
                $(".js-acquire-store-address-text").text(json_data.acquire_store['address_info']);
                $(".js-acquire-store-tel").text(json_data.acquire_store['store_tel']);
                $(".js-acquire-store-tel-a").attr('href', "tel:" + json_data.acquire_store['store_tel']);

                //费用明细--start
                $(".js-goods-amount").text(parseFloat(json_data.goods_amount_detail['goods_amount']).toFixed(2));
                $(".js-goods-basic").text(parseFloat(json_data.goods_amount_detail['goods_basic']).toFixed(2));
                $(".js-goods-procedure").text(parseFloat(json_data.goods_amount_detail['goods_procedure']).toFixed(2));
                $(".js-goods-remote").text(parseFloat(json_data.goods_amount_detail['extra_cost']).toFixed(2));
                $(".js-goods-all").text(parseFloat(json_data.goods_amount_detail['goods_all']).toFixed(2));
                //费用明细--end


                //驾驶员信息
                $(".js-vehicle-drivers").text(json_data.customer_information['vehicle_drivers']);
                $(".js-id-number").text("身份证:" + json_data.customer_information['id_number']);
                $(".js-mobile-phone").text(json_data.customer_information['mobile_phone']);
                //驾驶员信息
                if (json_data.acquire_store_id == json_data.return_store_id) {
                    $(".js-return-div").remove();
                    $(".js-acquire-title").text("取/还车门店");
                    return;
                }
                $(".js-return-div").show();
                $(".js-return-store-name").text(json_data.store_key_name + "·" + json_data.return_store_name);
                $(".js-return-store-time").text(json_data.return_store['business_start'] + "-" + json_data.return_store['business_end']);
                $(".js-return-store-address").attr('data-lng', json_data.return_store['location_longitude']);
                $(".js-return-store-address").attr('data-lat', json_data.return_store['location_latitude']);
                $(".js-return-store-address-text").text(json_data.return_store['address_info']);
                $(".js-return-store-tel").text(json_data.return_store['store_tel']);
                $(".js-return-store-tel-a").attr('href', "tel:" + json_data.return_store['store_tel']);
            } else {
                Toast(data.info, 2000);
            }
        },
        error: function (xhr, textStatus) {
//                    console.log('错误');
//                    console.log(xhr);
//                    console.log(textStatus)
        },
        complete: function () {
//                    console.log('结束');
        }
    });
    /**
     * 将小数天 转换成功天小数
     * @param num
     * @returns {string}
     */
    function numTotime(num) {
        var day = parseInt(num);
        var hour = parseInt((num - parseInt(num))*24);
        return day+"天"+hour+"小时";

    }
});

