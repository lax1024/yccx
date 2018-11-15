$(document).ready(function () {
    var now_page = 1;
    //获取界面左上角的返回按钮并设置相应的返回功能
    $("#back").on("click", function (ev) {
        window.history.back();
    });

    var order_item = $("#js-order-ul").find('.js-order-item').clone(true);
    $("#js-order-ul").find('.js-order-item').remove();

    $(function () {
        get_order_list();
        function get_order_list() {
            $.ajax({
                url: website + '/api/Order/get_list.html',//请求地址
                type: 'GET', //请求方式
                async: true,    //或false,是否异步
                data: {//请求参数
                    page: now_page
                },
                timeout: 5000,    //超时时间
                dataType: 'json',    //返回的数据格式：json/xml/html/script/jsonp/text
                success: function (data) {//请求成功，做数据处理
                    if (parseInt(data.code) == 0) {
                        now_page++;
                        $.each(data.data, function (index, value) {
                            add_order_item(value, "js-order-ul");
                        });
                    } else {
                        Toast(data.info, 2000);
                    }
                },
                error: function (xhr, textStatus) {
                },
                complete: function () {
//                    console.log('结束');
                }
            });
        }

        var nScrollHight = 0; //滚动距离总长(注意不是滚动条的长度)
        var nScrollTop = 0;   //滚动到的当前位置
        var nDivHight = $("#order").height();
        //滚动事件触发
        $("#order").scroll(function () {
            nScrollHight = $(this)[0].scrollHeight;
            nScrollTop = $(this)[0].scrollTop;
            if (nScrollTop + nDivHight >= nScrollHight - 0.5) {
                $.ajax({
                    url: website + '/api/Order/get_list.html',//请求地址
                    type: 'GET', //请求方式
                    async: true,    //或false,是否异步
                    data: {//请求参数
                        page: now_page
                    },
                    timeout: 5000,    //超时时间
                    dataType: 'json',    //返回的数据格式：json/xml/html/script/jsonp/text
                    beforeSend: function (xhr) {
//                    console.log(xhr);
//                    console.log('发送前');
                    },
                    success: function (data) {//请求成功，做数据处理
                        if (parseInt(data.code) == 0) {
                            now_page++;
                            $.each(data.data, function (index, value) {
                                add_order_item(value, "js-order-ul");
                            });
                            $("#order").scrollTop(nScrollTop);
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
            }
        });


        //添加未支付的订单
        function add_order_item(json_data, ids) {
//            console.log(json_data);
            var node = order_item.clone(true);
            node.show();
            if (json_data.goods_type == "1") {
                node.find('.js-remain-time').attr('data-time', json_data.remain_time);
                node.find('.js-remain-time').text(json_data.remain_time_str);
                node.find('.js-order-goods-type').text("常规租车");
                if (parseInt(json_data.order_status) != 10) {
                    node.addClass('invalid');
                    node.find('.strTime').remove();
                    node.find('.js-pay-btn').remove();
                } else {
                    node.find('.js-pay-btn').on('click', function () {
                        var data_ordersn = $(this).attr('data-ordersn');
                        window.location.href = "order_pay.html?sn=" + data_ordersn + "&goods_type=1";
                        return false;
                    });
                }
                if (parseInt(json_data.order_status) == 20 || parseInt(json_data.order_status) == 30) {
                    node.removeClass('invalid');
                }
            }
            if (json_data.goods_type == "2") {
                node.find(".js-remain").remove();
                node.find('.js-order-goods-type').text("纯电动车");
                if (parseInt(json_data.order_status) == 10 || parseInt(json_data.order_status) == 40) {
                    node.find('.js-pay-btn').on('click', function () {
                        var data_ordersn = $(this).attr('data-ordersn');
                        var data_id = $(this).attr('data-id');
                        window.location.href = "order_pay.html?sn=" + data_ordersn + "&order_id=" + data_id + "&goods_type=2";
                        return false;
                    });
                } else {
                    node.addClass('invalid');
                    node.find('.strTime').remove();
                    node.find('.js-pay-btn').remove();
                }
                if (parseInt(json_data.order_status) == 20 || parseInt(json_data.order_status) == 30) {
                    node.removeClass('invalid');
                }

            }
            node.attr('data-order_status', json_data.order_status);
            node.attr('data-id', json_data.id);
            node.attr('data-goods-id', json_data.goods_id);
            node.attr('data-goods-type', json_data.goods_type);
            node.attr('data-ordersn', json_data.order_sn);
            node.find('.js-goods-name').text(json_data.goods_name);
            node.find('.js-order-status').text(json_data.order_status_str);
            node.find('.js-acquire-time').text(json_data.acquire_time_str);
            node.find('.js-car-licence-plate').text(json_data.goods_licence_plate);
            node.find('.js-acquire-store-name').text(json_data.acquire_store_name);
            node.find('.js-return-time').text(json_data.return_time_str);
            node.find('.js-return-store-name').text(json_data.return_store_name);
            node.find('.js-store-key-name').text(json_data.store_key_name);
            node.find('.js-order-amount').text("￥" + json_data.order_amount);
            if (parseFloat(json_data.extra_cost) <= 0) {
                node.find('.js-extra-cost').parent().hide();
            } else {
                node.find('.js-extra-cost').text(json_data.extra_cost);
            }
            if (parseInt(json_data.evaluation_status) == 0 && parseInt(json_data.order_status) == 50) {
                node.find('.js-comment-btn').text("立即评价");
            } else if (parseInt(json_data.evaluation_status) == 1) {
                node.find('.js-comment-btn').text("查看评价");
            }else {
                node.find('.js-comment-btn').remove();
            }
            node.find('.js-comment-btn').on('click', function () {
                if (parseInt(json_data.evaluation_status) == 0) {
                    var data_id = $(this).attr('data-id');
                    window.location.href = "order_comment.html?order_id=" + data_id;
                }else {
                    var data_id = $(this).attr('data-id');
                    window.location.href = "order_comment_show.html?order_id=" + data_id;
                }
                return false;
            });
            node.find('.js-order-info').attr('data-id', json_data.id);
            node.find('.js-order-info').attr('data-ordersn', json_data.order_sn);
            node.find('.js-consult-btn').on('click', function () {
                window.location.href = 'tel:085188628700';
                return false;
            });

            node.on('click', function () {
                var data_id = $(this).attr('data-id');
                var goods_type = $(this).attr("data-goods-type");
                var order_status = $(this).attr('data-order_status');
                if (goods_type == "2") {
                    var sn = $(this).attr('data-ordersn');
                    if (parseInt(order_status) == 30) {
                        window.location.href = "order_ele_key.html?order_id=" + data_id;
                    } else if (parseInt(order_status) == 40) {
                        window.location.href = "order_pay.html?sn=" + sn + "&goods_type=2";
                    } else {
                        window.location.href = "order_details_newenergy.html?sn=" + sn;
                    }
                }
                if (goods_type == "1") {
                    var sn = $(this).attr('data-ordersn');
                    window.location.href = "order_details_tradition.html?order_sn=" + sn;
                }

                return false;
            });
            node.appendTo($("#" + ids));
        }

        setInterval(function () {
            setTimeout(function () {
                var nodes = $(".js-remain-time");
                $.each(nodes, function (index, value) {
                    update_time(nodes.eq(index));
                });
            }, 1000);
        }, 1000);
        //刷新倒计时
        function update_time(node) {
            var data_time = node.attr('data-time');
            data_time = parseInt(data_time) - 1;
            if (node.parents("li").attr("data-goods-type") == "1") {
                if (data_time < 0) {
                    var pnode = node.parents('.js-order-item');
                    pnode.addClass('invalid');
                    pnode.find('.js-order-status').text('已取消');
                    pnode.find('.js-pay-btn').remove();
                    node.parent().remove();
                }
                var data_time_str = timeToStr(data_time);
                node.attr('data-time', data_time);
                node.text(data_time_str);
            }

            if (node.parents("li").attr("data-goods-type") == "2") {
                node.parent().remove();
            }
        }
    });
});

