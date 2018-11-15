var coupon_item = $("#js_spread_list").find('.js_coupon_item').clone(true);
$("#js_spread_list").find('.js_coupon_item').remove();

var now_page = 0;
var nScrollHight = 0; //滚动距离总长(注意不是滚动条的长度)
var nScrollTop = 0;   //滚动到的当前位置
var nDivHight = $("#js_spread_list").height();

//返回键
$("#goback").on("click", function () {
    window.history.back();
});
//用户中心(右上角按钮)
$("#js_user_center").on("click", function () {
    window.location.href = "user_generalize.html";
});

$.ajax({
    url: "http://www.youchedongli.cn/api/Customer/get_channel.html",
    type: "GET",
    async: true,
    data: {
        page: now_page
    },
    timeout: 5000,
    dataType: "json",
    success: function (data) {
        if(parseInt(data.code) === 0) {
            add_order_item(data.data, "js_spread_list");
        } else if (parseInt(data.code) === 104 || parseInt(data.code) === 101) {
            $("#js_tip").text(data.info);
            $("#js_tip").show();
        }
    }
});

function add_order_item(json_array, parent_id) {
    for (var i = 0; i < json_array.length; i++) {
        if (parseInt(json_array[i].customer_status) === 1) {
            var node = coupon_item.clone(true);
            node.find('img').attr("src", json_array[i].wechar_headimgurl);
            node.find('.js_user_nickname').text(json_array[i].wechar_nickname);
            // node.find('.js_user_phone').text(protect_phone(json_array[i].mobile_phone));
            node.find('.js_user_create_time').text(json_array[i].create_time);
            node.find('.js_user_end_time').text(json_array[i].customer_end_time);
            node.find('.js_user_status').html("<b style='color: orange'>已实名</b>");
            node.appendTo($("#" + parent_id));
        }else {
            var node = coupon_item.clone(true);
            node.find('img').attr("src", json_array[i].wechar_headimgurl);
            node.find('.js_user_nickname').text(json_array[i].wechar_nickname);
            // node.find('.js_user_phone').text(protect_phone(json_array[i].mobile_phone));
            node.find('.js_user_create_time').text(json_array[i].create_time);
            node.find('.js_user_end_time').text(" ");
            node.find('.js_user_status').text("未实名");
            node.appendTo($("#" + parent_id));
        }
    }
}

function protect_phone(mobile_phone) {
    return mobile_phone.substr(0, 3) + "***" + mobile_phone.substr(6, 4);
}

//滚动事件触发
$("#js_spread_list").scroll(function () {
    nScrollHight = $(this)[0].scrollHeight;
    nScrollTop = $(this)[0].scrollTop;
    if (nScrollTop + nDivHight >= nScrollHight - 0.5) {
        now_page++;
        $.ajax({
            url: "http://www.youchedongli.cn/api/Customer/get_channel.html",//请求地址
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
                    add_order_item(data.data, "js_spread_list");
                    $("#js_spread_list").scrollTop(nScrollTop);
                } else {
                    Toast(data.info, 2000);
                }
            },
            error: function (xhr, textStatus) {
            },
            complete: function () {
            }
        });
    }
});