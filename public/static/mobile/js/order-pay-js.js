$(document).ready(function () {
    //获取界面左上角的返回按钮并设置相应的返回功能
    $("#goback").click(function () {
        window.history.back();
    });
    //支付方式选择器的显示
    $(".m_pay_way").find("h1").on("click", function (ev) {
        $(".select-dialog").removeClass("hide");
        $(".keyboard-body").addClass("select-open");
        $(".mask-layer").css({"display": "block"});
    });
    //隐藏支付方式选择器
    $(".mask-layer").on("click", function (ev) {
        $(".select-dialog").addClass("hide");
        $(".keyboard-body").removeClass("select-open");
        $(".mask-layer").css({"display": "none"});
    });
    //改变支付方式选择器中的被选中的方式后面的图标
    $(".sel-close-icon").on("click", function (ev) {
        $(".select-dialog").addClass("hide");
        $(".keyboard-body").removeClass("select-open");
        $(".mask-layer").css({"display": "none"});
    });
    //支付方式的选择
    var sel_list = $(".sel-list").find("li");
    sel_list.on("click", function (ev) {
        for (var i = 0; i < sel_list.length; i++) {
            if (this == sel_list[i]) {
                $("#finish_pay_way").text($(this).find(".bank-name").text());
                $("#finish_pay_way").attr('data-pay', $(this).find(".bank-name").attr('data-type'));
                $(sel_list[i]).addClass("item-selected");
                $(sel_list[i]).find("i")[0].innerHTML = "";
                $(this).find(".m_pay_way").removeClass("hide");
                $(".select-dialog").addClass("hide");
                $(".keyboard-body").removeClass("select-open");
                $(".mask-layer").css({"display": "none"});
            } else {
                $(sel_list[i]).removeClass("item-selected");
                $(sel_list[i]).find("i")[0].innerHTML = "";
                $(this).find(".m_pay_way").addClass("hide");
            }
        }
    });
});
