$(document).ready(function () {
    //标题栏中的“返回键”
    $("#goto_back").on("click",function () {
        window.history.back();
        // location.href = "user_center.html";
    });

    $(".user_cash_bg").on("click",function () {
        return false;
    });

});