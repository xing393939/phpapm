$(function () {
    $(document).delegate('[data-type]', 'click', function () {
        var _this = $(this), dataType = _this.attr('data-type');
        ({
            "simulate-select": function () {
                _this.simulateSelect({"adjustY": -1, "adjustLayerWidth": -2});
            }
        }[dataType] || function () {
        })();
        if (dataType !== 'simulate-select-hover') {
            return false;
        }
    });
    $(document).delegate('[data-type=simulate-select-hover]', 'mouseenter', function () {
        $(this).simulateSelect({"adjustY": -1, "adjustLayerWidth": -2, "eventType": 'mouseover'});
    })
});

function edit_as_name(obj, id) {
    var txt = $(obj).html();
    if (txt.indexOf('<input') > -1) {
        return;
    }
    $(obj).html('<input class="inpm" onkeydown="if (event.keyCode==13) return false;" onblur="edit_as_name_do(this,' + id + ')" id="as_name_id_' + id + '" name="as_name" value="' + txt + '">');
    $('#as_name_id_' + id)[0].select();
}

function edit_as_name_do(obj, id) {
    $.post("?act=report_monitor_as_name", {as_name: obj.value, id: id});
    $(obj).parent().bind('click', function (data) {
        edit_as_name($(this)[0], id)
    });
    $(obj).parent().html(obj.value);
}

function edit_v2_group(obj, id) {
    var txt = $(obj).html();
    if (txt.indexOf('<input') > -1) {
        return;
    }
    $(obj).html('<input class="inpm" onkeydown="if (event.keyCode==13) return false;" onblur="edit_v2_group_do(this,' + "'" + id + "'" + ')" id="v2_group_id_' + id + '" name="v2_group" value="' + txt + '">');
    $('#v2_group_id_' + id)[0].select();
}

function edit_v2_group_do(obj, ids) {
    $.post("?act=report_monitor_v2_groups", {'v2_group': obj.value, 'id': ids});
    $(obj).parent().bind('dblclick', function (data) {
        edit_v2_group($(this)[0], ids)
    });
    $(obj).parent().html(obj.value);
}

function edit_compare_group(obj, id) {
    var txt = $(obj).html();
    if (txt.indexOf('<input') > -1) {
        return;
    }
    $(obj).html('<input class="inpm" onkeydown="if (event.keyCode==13) return false;" onblur="edit_compare_group_do(this,' + id + ')" id="compare_group_id_' + id + '" name="compare_group" value="' + txt + '">');
    $('#compare_group_id_' + id)[0].select();
}

function edit_compare_group_do(obj, id) {
    $.post("?act=report_monitor_compare_group", {compare_group: obj.value, id: id});
    $(obj).parent().bind('click', function (data) {
        edit_compare_group($(this)[0], id)
    });
    $(obj).parent().html(obj.value);
}

//反选
function call() {
    var arrChk = $("input[name='uncount[]']");
    $(arrChk).each(function (idx, item) {
        $(item).attr("checked", !$(item).attr("checked"));
    });
}