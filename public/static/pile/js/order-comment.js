var TOTAL_HEIGHT = $(window).height();
$(".order_comment_button").css("top",TOTAL_HEIGHT - 50);

//左上角返回键点击事件
$("#js_goback").on("click", function () {
    window.history.back();
});

var START_CHECKED = "http://img.youchedongli.cn/public/static/mobile/images/order_comment_start.png";
var START_UNCHECKED = "http://img.youchedongli.cn/public/static/mobile/images/order_nocomment_start.png";
//5星图片
var COMMENT_STARTS = $("#js_comment_starts").find("img");
//5星图片的点击事件
COMMENT_STARTS.on("click", function () {
    //先清掉所有的五星评价
    $(COMMENT_STARTS).attr("src",START_UNCHECKED);
    //找到所点击的星级对象
    var index = $(this).attr("data-index");
    //根据所点击的来改变对应的星级(所点的星级之前的全部改为已选星级)
    for (var i = 0; i <= index; i++) {
        $(COMMENT_STARTS[i]).attr("src", START_CHECKED);
    }
});
//评价标签
var COMMENT_TAG = $(".comment_tag");
//评价标签的点击事件
COMMENT_TAG.on("click",function () {
    //comment_select:标签选中状态
    //如果为选中，则清除选择状态，否则添加选中状态
    if($(this).hasClass("comment_select")){
        $(this).removeClass("comment_select");
    }else {
        $(this).addClass("comment_select");
    }
});


$("#js_voice_comment_flag").on("click",function () {
    $("#js_voice_comment_model").fadeIn(500);
});

$("#js_close_voice_comment_model").on("click",function () {
    $("#js_voice_comment_model").fadeOut(500);
});


//提交评价
$("#js_submit_comment").on("click", function () {

});