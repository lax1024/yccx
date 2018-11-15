$(document).ready(function () {
    //定义变量
    //标题栏中的“返回键”
    $("#goto_back").on("click", function () {
        location.href = "map_choose_car.html";
    });
    //标题栏中右边的“二维码”扫描图标-->扫码租车
    $("#js_rqcode_car").on("click", function () {
        location.href = "qrcode_or_license.html";
    });

    //
   /*  if(getReferrUrl() == null || getReferrUrl() != "map_choose_car.html"){
        $("#goto_back").hide();
    } */

    //头像点击事件：显示用户基本信息：姓名、电话、昵称
    $("#js_user_wechar_headimgurl").on("click", function () {
        $("#js_user_info").fadeIn(1500);
    });
    $("#js_user_info").on("click", function () {
        $("#js_user_info").fadeOut(1000);
    });
});