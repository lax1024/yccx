var coupon_item_normal = $("#js_coupon_list").find('.js_coupon_item_normal').clone(true);
var coupon_item_used = $("#js_coupon_list").find('.js_coupon_item_used').clone(true);
var coupon_item_expire = $("#js_coupon_list").find('.js_coupon_item_expire').clone(true);
$("#js_coupon_list").find('li').remove();

$("#goback").on("click", function () {
    window.history.back();
});

$.ajax({
    url: website + "/api/Customer/get_coupon.html",
    type: "GET",
    async: true,
    data: {
        page: 0
    },
    timeout: 5000,
    dataType: "json",
    success: function (data) {
        if (parseInt(data.code) === 0) {
            add_order_item(data.data, "js_coupon_list");
        }
    }
});

function add_order_item(json_array, parent_id) {
    for (var i = 0; i < json_array.length; i++) {
        //console.log(json_array[i].coupon_type);
        var node;
        if (parseInt(json_array[i].state) === 10) {
            node = coupon_item_normal.clone(true);
            node.show();
            node.find('.js_coupon_type').text(json_array[i].coupon_type);
            node.find('.js_coupon_remark').text(json_array[i].remark);
            node.find('.js_coupon_explain').text(json_array[i].explain);
            node.find('.js_coupon_end_time').text(json_array[i].end_time_str_day);
            node.appendTo($("#" + parent_id));
        }else if(parseInt(json_array[i].state) === 20){
            node = coupon_item_used.clone(true);
            node.show();
            node.find('.js_coupon_type').text(json_array[i].coupon_type);
            node.find('.js_coupon_remark').text(json_array[i].remark);
            node.find('.js_coupon_explain').text(json_array[i].explain);
            node.find('.js_coupon_end_time').text(json_array[i].end_time_str_day);
            node.appendTo($("#" + parent_id));
        }else{
            node = coupon_item_expire.clone(true);
            node.show();
            node.find('.js_coupon_type').text(json_array[i].coupon_type);
            node.find('.js_coupon_remark').text(json_array[i].remark);
            node.find('.js_coupon_explain').text(json_array[i].explain);
            node.find('.js_coupon_end_time').text(json_array[i].end_time_str_day);
            node.appendTo($("#" + parent_id));
        }
    }
}
