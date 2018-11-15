/**
 *  定义(声明)变量
 */
var bar_coupon_list = $(".bar-coupon-list");//标题栏中的两个按钮
var gallery = mui('.mui-slider');//获得slider插件对象(滑动)
var active_list = $(".mui-active");

bar_coupon_list.on("click",function () {
    var data_index = $(this).attr("data-index");
    for(var i = 0; i < 2; i ++){
        if(data_index == i){
            $(bar_coupon_list[i]).addClass("list-selected");
            gallery.slider().gotoItem(i);
        }else {
            $(bar_coupon_list[i]).removeClass("list-selected");
        }
    }
});

//滑动监听事件
$('.mui-slider').on('slide', function (event) {
    // var index = event.detail.slideNumber;//获得当前显示项的索引
    var index = mui(".mui-slider").slider().getSlideNumber();//获得当前显示项的索引
    for(var i = 0; i < 2; i ++){
        if(index == i){
            $(bar_coupon_list[i]).addClass("list-selected");
        }else {
            $(bar_coupon_list[i]).removeClass("list-selected");
        }
    }
});