/**
 * Created with JetBrains WebStorm.
 * User: LXJ
 * Date: 12-7-6
 * Time: 上午9:18
 * To change this template use File | Settings | File Templates.
 * var defaut ={
 *     "dropLayer" : ".wjh-drop-down-layer",//css选择器
 *     "appointLayer" : "",//指定下拉层的selector，这个值存在时，dropLayer值将被忽略
 *     "select" : "",//css样式名
 *     "layerWidth" : "",//layer层宽度，此值为空时则自动获取宽度
 *     "minWidth" : "",//layer层最小宽度，当js获取到的宽度小于最小宽度时，layer层最的宽度就位最小宽度
 *     "maxWidth" : "",//layer层最大宽度，当js获取到的宽度大于最大宽度时，layer层最的宽度就位最大宽度
 *     "align" : "left", //layer层相对触发层是左对齐，值为right时则为右对齐
 *     "adjustLayerWidth" : 0,//layer层宽度微调
 *     "offsetParent" : "body", //定位时相对的父层
 *     "adjustX" : 0,//left值微调
 *     "adjustY" : 0,//top值微调
 *     "layerShowBack" : function(){},//下拉层展开时的回调函数
 *     "layerClickBack" : function(){}//下拉层被点击时的回调函数
 * };
 */
$.parse_config = function (str) {
    var cache = {}, str = str.toString();
    str = /\?/.test(str) ? str.split(/\?+/)[1] : str;
    str.replace(/[?&]*((?:(?!=)(?!\?).)*)=*((?:(?!&)(?!\?).)*)/ig, function (k0, key, value) {
        if (key) {
            cache[key] = value;
        }
    });
    return cache;
};
(function ($) {
    var simulateSelect = function (elem, config) {
            var self = this;
            if (!(self instanceof simulateSelect)) {
                return new simulateSelect(elem, config);
            }
            this.init(elem, config);
            return this;
        },
        layerCache = [],
        time;
    $.extend(simulateSelect.prototype, {
        "init": function (elem, config) {
            var defaut = {
                "dropLayer": ".wjh-drop-down-layer",
                "appointLayer": "",
                "select": "",
                "layerWidth": "",
                "minWidth": "",
                "maxWidth": "",
                "align": "left",
                "adjustLayerWidth": 0,
                "offsetParent": "body",
                "adjustX": 0,
                "adjustY": 0,
                "layerShowBack": function () {
                },
                "layerClickBack": function () {
                }
            };
            this.config = $.extend(defaut, config || {});
            this.elem = $(elem);
            this.elemDom = this.elem[0];
            this.parent = this.elem.parent();
            this.layer = this.config.appointLayer ? $(this.config.appointLayer) : this.parent.find(this.config.dropLayer);
            this.select = this.config.select;
            this.close();
            this.showLayer();
            this.mouseoverType();
            this.layerDelegate();
        },
        "showLayer": function () {
            var self = this,
                config = this.config,
                offserParent = this.elem.parents(config.offsetParent).eq(0),
                layer = this.layer,
                offserParentOffset = offserParent.offset(),
                elemOffset = self.elem.offset(),
                elemHeight = self.elem.outerHeight(),
                elemWidth = self.elem.outerWidth(),
                top = elemOffset.top,
                left = elemOffset.left;
            $(layerCache).each(function (index, value) {
                if (layer[0] != $(value.layer)[0]) {
                    $(value.layer).hide();
                    self.select && $(value.elem).removeClass(self.select);
                }
            });
            if (layer.height() == 0 || layer.css('display') === 'none' || layer.css('visibility') === 'hidden') {
                var calculatorWidth = (parseInt(config.layerWidth) || this.elem.outerWidth()) + parseInt(config.adjustLayerWidth);
                (config.minWidth && calculatorWidth < config.minWidth) && (calculatorWidth = config.minWidth);
                (config.maxWidth && calculatorWidth > config.maxWidth) && (calculatorWidth = config.maxWidth);
                layer.css({
                    "width": calculatorWidth,
                    "top": top - offserParentOffset.top + elemHeight + parseInt(config.adjustY),
                    "left": (config.align === 'right' ? (left + elemWidth - calculatorWidth - offserParentOffset.left) : (left - offserParentOffset.left)) + parseInt(config.adjustX)
                }).show();
                if (!this.elem.data("layerCache")) {
                    layerCache.push({"elem": self.elem, "layer": layer});
                    this.elem.data("layerCache", 1);
                }
                ;
                this.select && this.elem.addClass(self.select);
            } else {
                layer.hide();
                this.select && this.elem.removeClass(self.select);
            }
            ;
            config.layerShowBack && config.layerShowBack.call(this);
        },
        "mouseoverType": function () {
            time && clearTimeout(time);
            if (this.config.eventType === "mouseover" && !this.elem.data("mouseoverType")) {
                var self = this;
                this.elem.bind('mouseleave', function () {
                    time && clearTimeout(time);
                    time = setTimeout(function () {
                        self.close()
                    }, 100);
                });
                this.layer.bind('mouseenter',function () {
                    time && clearTimeout(time);
                }).bind('mouseleave', function () {
                        self.close();
                    });
                this.elem.data("mouseoverType", '1');
            }
        },
        "layerDelegate": function () {
            if (!this.elem.data("selectinted")) {
                var layer = this.layer,
                    config = this.config,
                    parent = this.parent,
                    layerClickBack = config.layerClickBack;
                ;
                layer.delegate("[data-value]", "click", function () {
                    var _this = this,
                        dataValue = $(_this).attr("data-value"),
                        setValueElems = parent.find("[data-simulate-value=simulate-select]");
                    setValueElems.each(function (index, value) {
                        value.nodeName.toLowerCase() === "input" ? $(value).val(dataValue) : $(value).html(dataValue);
                    });
                    if ($.isFunction(layerClickBack)) {
                        layerClickBack.call(dataValue, dataValue);
                    } else {
                        window[layerClickBack] && window[layerClickBack].call(_this, _this);
                    }
                    ;
                    layer.hide();
                    //return false;加后导致后面再绑定的click事件失效
                });
                this.elem.mousedown(function (event) {
                    event.stopPropagation();
                });
                layer.mousedown(function (event) {
                    event.stopPropagation();
                });
                this.elem.data("selectinted", 1);
            }
            ;
        },
        "close": function () {
            var self = this;
            if (self.config.eventType === "mouseover") {
                $(layerCache).each(function (index, value) {
                    $(value.layer).hide();
                    self.select && $(value.elem).removeClass(self.select);
                });
            } else {
                $(document).mousedown(function () {
                    self.layer.hide();
                    self.select && self.elem.removeClass(self.select);
                });
            }
        }
    });
    $.simulateSelect = simulateSelect;
    $.fn.simulateSelect = function (config) {
        var data_select_config = $(this).attr('data-select-config'), configs = {};
        $.extend(configs, config);
        configs = data_select_config ? $.extend(configs, $.parse_config(data_select_config)) : config;
        return simulateSelect(this, configs);
    };
})(jQuery);