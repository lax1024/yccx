/**
 * plugin-name:picker.js
 *      author:Van
 *      e-mail:zheng_jinfan@126.com
 *   demo-site:http://m.zhengjinfan.cn/picker/index.html
 *    homepage:http://blog.zheng_jinfan.cn
 *  createtime:2017-07-25 16:55:36
 *  MIT License
 */
layui.define(['laytpl', 'form'], function (exports) {
    "use strict";
    var $ = layui.jquery,
        layer = parent.layer === undefined ? layui.layer : parent.layer,
        form = layui.form(),
        BRAND = 'brand',
        SERIES = 'series',
        CARTYPE = 'cartype',
        BRAND_TIPS = '请选择品牌',
        SERIES_TIPS = '请选择车系',
        CARTYPE_TIPS = '请选择车型',
        pickerType = {
            brand: 1, //品牌
            series: 2, //车系
            cartype: 3 //车型
        };
    var Picker = function () {
        //默认设置
        this.config = {
            elem: undefined, //存在的容器 支持类选择器和ID选择器  e.g: [.class]  or  [#id]
            codeConfig: {
                "default": true,
                "type": 3,
                "brand": 152964,
                "series": 152965,
                "cartype": 153288
            }, //初始值 e.g:{ code:440104,type:3 } 说明：code 为代码，type为类型 1：省、2：市、3：区/县
            /**
             * {
                    "code": "654325",                   //代码
                    "name": "青河县",                    //名称
                    "type": 3,                          //类型，1：省、2：市、3：区/县
                    "path": "650000,654300,654325",     //路径： 省,市,区/县
                    "parentCode": "654300"              //父代码
                }
             */
            data: undefined, //数据源，需要特定的数据结构,
            canSearch: true,//是否支持搜索
            url: "/api/Car/get_brand_list.html",                          //远程地址，未用到
            type: 'GET'                              //请求方式,未用到
        };
        this.v = '1.0.0';
        //渲染数据
    };
    Picker.fn = Picker.prototype;
    //设置
    Picker.fn.set = function (options) {
        var that = this;
        $.extend(true, that.config, options);
        return that;
    };
    //渲染
    Picker.fn.render = function () {
        var that = this;
        var config = that.config,
            $elem = $(config.elem),
            getDatas = function (type, parentCode, selectCode) {
                var data = [];
                var result = [];
                $.ajax({
                    type: config.type,
                    url: config.url,
                    data: {
                        'type': type,
                        'pid': parentCode
                    },
                    dataType: "json",
                    async: false,
                    success: function (results) {
                        data = results.data
                    }
                });
                for (var i = 0; i < data.length; i++) {
                    var e = data[i];
                    if (type == 1) {
                        var isSelected = selectCode == e.brand_id;
                        result.push({
                            code: e.brand_id,
                            name: e.brand_name,
                            isSelected: isSelected
                        });
                    } else if (type == 2) {
                        var isSelected = selectCode == e.series_id;
                        result.push({
                            code: e.series_id,
                            name: e.series_name,
                            isSelected: isSelected
                        });
                    } else if (type == 3) {
                        var isSelected = selectCode == e.type_id;
                        result.push({
                            code: e.type_id,
                            name: e.type_name,
                            isSelected: isSelected
                        });
                    }
                }
                return result;
            },
            tempContent = function (vid) {
                return '<form class="layui-form">' +
                    '<div class="layui-form-item" data-action="picker_' + vid + '">' +
                    '<label class="layui-form-label">选择车型</label>' +
                    '</div>' +
                    '</form>';
            },
            temp = function (filterName, tipInfo, noSearch) {
                var html = '<div class="layui-input-inline" data-action="' + filterName + '">';
                if (config.canSearch) {
                    if (noSearch == 'yes') {
                        html += '<select name="' + filterName + '" lay-filter="' + filterName + '" lay-search>';
                    } else {
                        html += '<select name="' + filterName + '" lay-filter="' + filterName + '">';
                    }
                } else {
                    html += '<select name="' + filterName + '" lay-filter="' + filterName + '">';
                }
                html += '<option value="">' + tipInfo + '</option>';
                html += '{{# layui.each(d, function(index, item){ }}';
                html += '{{# if(item.isSelected){ }}';
                html += '<option value="{{ item.code }}" selected>{{ item.name }}</option>';
                html += '{{# }else{ }}'
                html += '<option value="{{ item.code }}">{{ item.name }}</option>';
                html += '{{# }; }}';
                html += '{{#  }); }}';
                html += '</select>';
                html += '</div>';
                return html;
            },
            renderData = function (type, $picker, parentCode, selectCode, init) {
                var tempHtml = '';
                var filter = '';
                init = init === undefined ? true : init;
                var pickerFilter = {
                    brand: BRAND + that.config.vid,
                    series: SERIES + that.config.vid,
                    cartype: CARTYPE + that.config.vid
                };
                var temp_data = getDatas(type, parentCode, selectCode);
                var noSearch = "no";
                if (temp_data.length > 20) {
                    noSearch = "yes";
                }
                switch (type) {
                    case pickerType.brand:
                        tempHtml = temp(pickerFilter.brand, BRAND_TIPS, noSearch);
                        filter = pickerFilter.brand;
                        break;
                    case pickerType.series:
                        tempHtml = temp(pickerFilter.series, SERIES_TIPS, noSearch);
                        filter = pickerFilter.series;
                        break;
                    case pickerType.cartype:
                        tempHtml = temp(pickerFilter.cartype, CARTYPE_TIPS, noSearch);
                        filter = pickerFilter.cartype;
                        break;
                }
                layui.laytpl(tempHtml).render(temp_data, function (html) {
                    if (!init) {
                        var $has = $picker.find('div[data-action=' + filter + ']');
                        if ($has.length > 0) {
                            var $prev = $has.prev();
                            $prev.next().remove();
                            $prev.after(html);
                            if (filter == pickerFilter.series) {
                                var $hasArea = $picker.find('div[data-action=' + pickerFilter.cartype + ']');
                                if ($hasArea.length > 0) {
                                    $hasArea.find('select[name=' + pickerFilter.cartype + ']')
                                        .html('<option value="">请选车型</option>');
                                }
                            }
                        } else {
                            $picker.append(html);
                        }
                    } else {
                        $picker.append(html);
                    }
                    form.on('select(' + filter + ')', function (data) {
                        switch (data.elem.name) {
                            case pickerFilter.brand:
                                renderData(pickerType.series, $picker, data.value, undefined, false);
                                break;
                            case pickerFilter.series:
                                renderData(pickerType.cartype, $picker, data.value, undefined, false);
                                break;
                            case pickerFilter.cartype:
                                // console.log('车型'+data.value);
                                // renderData(pickerType.street, $picker, data.value, undefined, false);
                                break;
                        }
                    });
                    form.render('select');
                });
            };
        // config.vid = new Date().getTime();
        config.vid = "_id";
        $elem.html(tempContent(config.vid));
        var $picker = $elem.find('div[data-action=picker_' + config.vid + ']');
        //如果需要初始化
        if (config.codeConfig.default) {
            // var path = getAreaCodeByCode(config.codeConfig);
            var pType = config.codeConfig.type;
            var pCode = config.codeConfig.brand;
            var cCode = config.codeConfig.series;
            var aCode = config.codeConfig.cartype;
            switch (pType) {
                case pickerType.brand:
                    //渲染品牌
                    renderData(pickerType.brand, $picker, null, pCode);
                    break;
                case pickerType.series:
                    //渲染品牌
                    renderData(pickerType.brand, $picker, null, pCode);
                    //渲染车系
                    renderData(pickerType.series, $picker, pCode, cCode);
                    break;
                case pickerType.cartype:
                    //渲染品牌
                    renderData(pickerType.brand, $picker, null, pCode);
                    //渲染车系
                    renderData(pickerType.series, $picker, pCode, cCode);
                    //渲染车型
                    renderData(pickerType.cartype, $picker, cCode, aCode);
                    break;
            }
        } else {
            renderData(pickerType.brand, $picker, null, undefined, true);
        }
    };
    exports('picker', Picker);
});