$("#goto_back").on("click",function () {
    location.href = "order_mine.html"
});
//五颗星星
var  js_comment_list = $(".js_comment_list");
//星星选中与否的样式
var comment_checked = "http://img.youchedongli.cn/public/static/mobile/images/order_comment_start.png";

//评价标签
var js_comment_nature = $(".js_comment_nature");
//根据url参数获取订单id和车辆id
var order_id = getValueByParameter(location.href,"order_id");

//根据车辆id获取车辆信息
getOrderDetailsById(order_id);
/**
 * 根据订单id获取订单详情
 * @param order_id  订单id
 */
function getOrderDetailsById(order_id) {
    $.ajax({
        url: website + "/api/Order/get_comment.html",
        type: "GET",
        async: true,
        data: {
            order_id: order_id
        },
        timeout: 5000,
        dataType: "json",
        beforeSend: function () {

        },
        success: function (data) {
            console.log(data);
            if (parseInt(data.code) == 0) {
                var data = data.data;
                $(".js_goods_img").attr("src","http://img.youchedongli.cn/public/" + data.car_info.goods_img);
                $(".js_goods_name").text(data.car_info.goods_name);
                $(".js_goods_licence_plate").text("(" + data.car_info.licence_plate + ")");
                $(".js_store_key_name").text(data.store_key_name);
                $(".js_interval_time").text(data.interval_time_str.timestr);
                $(".js_all_mileage").text(data.all_mileage + "公里");
                $(".js_order_amount").text(data.order_amount);

                var star_level = data.star_level;
                //星星级别
                for(var i = 0; i < star_level; i ++){
                    $(js_comment_list[i]).attr("src",comment_checked);
                }

                var tag = data.tag;
                //标签
                for (var i = 0; i < tag.length;i ++){
                    var temp = tag[i];
                    for(var j = 0; j < js_comment_nature.length;j ++){
                        if($(js_comment_nature[j]).text() == temp){
                            $(js_comment_nature[j]).addClass("comment_select");
                        }
                    }
                }

                $("#js_text_comment").text(data.content);

                if(parseInt(data.content) == 0){
                    $("#js_anonymity_comment").attr("checked","disabled");
                }else {
                    $("#js_anonymity_comment").removeAttr("checked");
                }

                voice = data.voice;

            }

        },
        error: function () {

        },
        complete: function () {

        }
    });
}
