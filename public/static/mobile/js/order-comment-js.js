$("#goto_back").on("click", function () {
    location.href = "order_mine.html"
});
//五颗星星
var js_comment_list = $(".js_comment_list");
//星星选中与否的样式
var comment_checked = "http://img.youchedongli.cn/public/static/mobile/images/order_comment_start.png";
var comment_unchecked = "http://img.youchedongli.cn/public/static/mobile/images/order_nocomment_start.png";
/**星星的点击事件
 * 点击时：先清空所有的选中样式，
 * 再根据点击的位置来设置选中星星的个数
 */
js_comment_list.on("click", function () {
    //星星的索引
    var index = $(this).attr("data-index");
    //全部设为未选中
    for (var i = 0; i <= 4; i++) {
        $(js_comment_list[i]).attr("src", comment_unchecked);
    }
    //根据索引设置选中星星的个数
    for (var i = 0; i <= index; i++) {
        $(js_comment_list[i]).attr("src", comment_checked);
    }
});
//评价标签
var js_comment_nature = $(".js_comment_nature");
/**
 * 标签的点击事件
 * 点击以后：判断是否为已选状态，是-->取消选中，否-->选中
 * 点击事件：即状态的切换
 */
js_comment_nature.on("click", function () {
    if ($(this).hasClass("comment_select")) {
        $(this).removeClass("comment_select");
    } else {
        $(this).addClass("comment_select");
    }
});

//根据url参数获取订单id和车辆id
var order_id = getValueByParameter(location.href, "order_id");
var goods_id = getValueByParameter(location.href, "goods_id");

//根据车辆id获取车辆信息
getOrderDetailsById(order_id);

/**
 * 根据订单id获取订单详情
 * @param order_id  订单id
 */
function getOrderDetailsById(order_id) {
    $.ajax({
        url: website + "/api/Order/get_order_info.html",
        type: "GET",
        async: true,
        data: {
            id: order_id
        },
        timeout: 5000,
        dataType: "json",
        beforeSend: function () {

        },
        success: function (data) {
            if (parseInt(data.code) == 0) {
                var data = data.data;
                $(".js_goods_img").attr("src", "http://img.youchedongli.cn/public/" + data.order_goods.goods_img);
                $(".js_goods_name").text(data.order_goods.goods_name);
                $(".js_goods_licence_plate").text("(" + data.order_goods.licence_plate + ")");
                $(".js_store_key_name").text(data.store_key_name);
                $(".js_interval_time").text(data.interval_time_str.timestr);
                $(".js_all_mileage").text(data.all_mileage + "公里");
                $(".js_order_amount").text(data.order_amount);
            }

        },
        error: function () {

        },
        complete: function () {

        }
    });
}

//提交评价按钮的点击事件监听
$("#js_submit_comment").on("click", function () {
    submit_comment();
});

//评价提交方法
function submit_comment() {
    //星星个数
    var stars = 0;
    //根据索引设置选中星星的个数
    for (var i = 0; i <= 4; i++) {
        if ($(js_comment_list[i]).attr("src") == comment_checked) {
            stars++;
        } else {
            break;
        }
    }

    //评价标签的内容(数组)
    var natire = new Array();
    for (var i = 0; i < js_comment_nature.length; i++) {
        if ($(js_comment_nature[i]).hasClass("comment_select")) {
            natire.push($(js_comment_nature[i]).text());
        }
    }

    //用户评价文字
    var text_comment = $("#js_text_comment").val();
    //是否匿名评论
    var is_checked = $("#js_anonymity_comment").is(":checked");
    var is_hide = 1;
    if (is_checked) {
        is_hide = 0;
    } else {
        is_hide = 1;
    }
    if (natire.length === 0 && stars === 0 && text_comment === "" && (voice_url === ""||voice_url === undefined)) {
        alert("请填写相应评论信息");
        return;
    }
    $.ajax({
        url: website + "/api/Order/add_comment.html",
        type: "POST",
        async: true,
        data: {
            order_id: order_id,
            star_level: stars,
            tag: natire,
            content: text_comment,
            voice: voice_url,
            is_hide: is_hide
        },
        timeout: 5000,
        dataType: "json",
        beforeSend: function () {

        },
        success: function (data) {
            if (parseInt(data.code) == 0) {
                Toast(data.info, 2000);
                setTimeout(function () {
                    location.href = "order_mine.html";
                });
            } else {
                Toast(data.info, 2000);
            }

        },
        error: function () {

        },
        complete: function () {

        }
    });
}


$("#js_voice_comment_flag").on("click", function () {
    $("#js_voice_comment_model").fadeIn(500);
});

$("#js_close_voice_comment_model").on("click", function () {
    $("#js_voice_comment_model").fadeOut(500);
});