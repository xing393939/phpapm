/***************************
 *JCalendar日历控件
 *@author brull
 *@email [email]brull@163.com[/email]
 *@date 2007-4-16
 *@更新 2007-5-27
 *@version 1.0 beta
 ***************************/

/*
 *@param year 年份,[可选]
 *@param month 月份，[可选]
 *@param date 日期，[可选]
 *或者是以横线间隔开的日期，比如：2007-4-27
 */
 
function JCalendar (year,month,date) {
	if($$("calendar"))return;//唯一实例
	var _date = null;
	if(arguments.length == 3) _date = new Date(year,month-1,date);
	else if(arguments.length == 1 && typeof arguments[0] == "string"){
		var tmp = arguments[0].split("-");
		_date = new Date(tmp[0],tmp[1] - 1, tmp[2]);
	}
	//如果没有参数，就初始化为当天日期
	else if(arguments.length == 0) _date = new Date();
	this.year = _date.getFullYear();
	this.month = _date.getMonth() + 1;
	this.date = _date.getDate();
	this.FIRSTYEAR = 1949;
	this.LASTYEAR = 2049;
	JCalendar.cur_year = this.year;
	JCalendar.cur_month = this.month;
	JCalendar.cur_date = this.date;
	JCalendar.cur_obj_id = null;//作为输入控件时保存当前文本框的id
}

/**
 *设置日历年份下拉菜单的年份范围
 *@first 第一个年份界限
 *@last 第二个年份界限
 *两个参数顺序可以颠倒
 */
JCalendar.prototype.setYears = function(first,last){
	if(isNaN(first) || isNaN(last)) return;
	this.FIRSTYEAR = Math.min(first,last);
	this.LASTYEAR = Math.max(first,last);
}

/**
 *以HTML串返回日历控件的HTML代码
 */
JCalendar.prototype.toString = function(){
	var fday = new Date(this.year,this.month-1,1).getDay();//每月第一天的星期数
	var select_year = new Array();//年份下拉菜单
	var select_month = new Array();//月份下拉菜单
	//日历里的每个单元格的数据，预先定义一段空数组，对应日历里第一周空的位置。[注意星期天对应的数是0]
	var date = new Array(fday > 0 ? fday : 0);
	var dayNum = new Date(this.year,this.month,0).getDate();//每月的天数
	var html_str = new Array();//保存日历控件的HTML代码
	var date_index = 0;//date数组的索引
	var weekDay = ["日","一","二","三","四","五","六"];
	
	//填充年份下拉菜单
	select_year.push("<select id='select_year'  style='display:none' onblur =\"hide(this);show('title_year')\" onchange='JCalendar.update(this.value,JCalendar.cur_month)'>");
	for(var i = this.FIRSTYEAR; i <= this.LASTYEAR; i++){
		if(i == this.year)
			select_year.push("<option value='" + i + "' selected='selected'>" + i +"</option>");
		else
			select_year.push("<option value='" + i + "'>" + i +"</option>");
	}
	select_year.push("</select>");
	
	//填充月份下拉菜单
	select_month.push("<select  id='select_month' style='display:none'  onblur =\"hide(this);show('title_month')\" onchange='JCalendar.update(JCalendar.cur_year,this.value)'>");
	for(var i = 1; i <= 12; i++){
		if(i == this.month)
			select_month.push("<option value='" + i + "' selected='selected'>" + i +"月</option>");
		else
			select_month.push("<option value='" + i + "'>" + i +"月</option>");
	}
	select_month.push("</select>");

	//初始化date数组
	for(var j = 1; j <= dayNum; j++){
		date.push(j);
	}
	//开始构建日历控件的HTML代码
	html_str.push("<table id='calendar'>");
	//日历表格caption
	html_str.push("<caption>" + "<a href='#'  id='prev_month' title='上一月份' onclick=\"JCalendar.update(JCalendar.cur_year,JCalendar.cur_month-1);return false;\"><</a><a href='#' id='title_year' title='点击选择年份' onclick=\"hide(this);show('select_year');$$('select_year').focus();return false\">" + this.year + "年</a>" + select_year.join("") + "<a href='#' id='title_month' title='点击选择月份' onclick=\"hide(this);show('select_month');$$('select_month').focus();return false\">" + this.month + "月</a>" + select_month.join("") + "<a href='#' id='next_month' title='下一月份' onclick=\"JCalendar.update(JCalendar.cur_year,JCalendar.cur_month+1);return false;\">></a></caption>");
	//日历表格头
	html_str.push("<thead><tr>");
	for(var i = 0; i < 7; i++){//填充日历头
		html_str.push("<td>" + weekDay[i] + "</td>");
	}
	html_str.push("</tr></thead>");
	//日历主体
	var tmp;
	html_str.push("<tbody>");
	for(var i = 0; i < 6; i++){//填充日期，6行7列
		html_str.push("<tr>");
		for(var j = 0; j < 7; j++){
			tmp = date[date_index++];
			if(!tmp) tmp = "";
			html_str.push("<td ");
			if(tmp == this.date) html_str.push("id='c_today' ");
			html_str.push("onmouseover='JCalendar.over(this)' onmouseout='JCalendar.out(this)' onclick='JCalendar.click(this)'>" + tmp + "</td>");
		}
		html_str.push("</tr>");
	}
	html_str.push("</tbody></table>");
	return html_str.join("");
}

/**
 *特别显示关键天，典型例子博客的日历
 * 实现原理，为每个关键天的表格单元添加一个class,名字为keydate,CSS样式需要自己写，比如加个背景之类的
 *@param 日期的数组，比如：[1,4,6,9]
 */
JCalendar.prototype.setKeyDate = function(){
	var dates = arguments[0];
	var tds = $TN("td",$$("calendar"));
	var reg = null;
	for(var i = 0; i < dates.length; i++){
		reg = new RegExp("\\b" + dates[i] + "\\b");
		for(var j = 7; j < tds.length; j++){//忽略表格头
			if(reg.test(tds[j].innerText)){
				tds[j].className = "keydate";
				break;
			}
		}
	}
}

/**
 *可以将日历控件邦定到某个文本框，在点击文本框的时候，会在direction指定的方向弹出日历,可以多次调用来帮定多个文本框
 *@ param obj_id 需要邦定日历的文本框的id
 *@ param direction 日历出现的相对于文本框的方向 [可选] 默认为right
 */
JCalendar.prototype.bind = function(obj_id,direction){
	var obj = $$(obj_id);
	var direction = direction ? direction : "right";
	if(!obj)return;
	if(!$$("calendar_container")){//唯一容器
		var contain = $DC("div");
		var s = contain.style;
		s.visibility = "hidden";
		s.position = "absolute";
		s.top = "-200px";//不能占据页面空间
		s.zIndex = 65530;
		contain.id = "calendar_container";
		contain.innerHTML = this.toString();
		document.body.appendChild(contain);
		if(isIE){
			var ifm = $DC("iframe");
			var s = ifm.style;
			ifm.frameBorder = 0;
			ifm.height = (contain.clientHeight - 3) + "px";
			s.visibility = "inherit";
			s.filter = "alpha(opacity=0)";
			s.position = "absolute";
			s.top = "-200px";//不能占据页面空间
			//s.left ="-200px;";
			s.width = $$("calendar_container").offsetWidth;
			s.zIndex = -1;
			contain.insertAdjacentElement("afterBegin",ifm);
		}
	}
	//覆盖日历事件
	JCalendar.onupdate = function () {};
	JCalendar.onclick = function (year,month,date){
		var obj = $$(JCalendar.cur_obj_id);
		if(/^\d{1,2}:\d{1,2}(?:\d{1,2})*$/.test(obj.value)) obj.value = year + '-' + month + '-' + date + " " + obj.value;
		else obj.value = obj.value.replace(/^[^\s]*/i,year + '-' + month + '-' + date);
		//添加onchange事件
		try{obj.onchange();}catch(e){}
		hide("calendar_container");
	}
	//邦定事件
	document.attachEvent("onclick",function(){
		if($$("calendar_container").style.visibility="visible")hide("calendar_container");
	});
	obj.attachEvent("onclick",function(e){
		var obj = e.srcElement;
		var dates =obj.value.split(/\s/)[0].split("-");//文本框日期数组,文本框内容可能有时间这样的字串，即:2007-5-26 15:39
		var x,y,left,top;
		var contain = $$("calendar_container");
		var body = isDTD ? document.documentElement : document.body;
		left = body.scrollLeft + e.clientX - e.offsetX;
		top = body.scrollTop + e.clientY - e.offsetY;
		switch(direction){
			case "right" : x = left + obj.offsetWidth; y = top;break;
			case "bottom" : x = left; y = top + obj.offsetHeight;break;
		}
		contain.style.top = y + "px";
		contain.style.left = x + "px";
		//更新日历日期
		if(dates.length == 3 && (JCalendar.cur_year != dates[0] || JCalendar.cur_month != dates[1] || JCalendar.cur_date != dates[2]))
			JCalendar.update(dates[0],dates[1],dates[2]);//如果文本框有时间则更新时间到文本框的时间
		else if (dates.length != 3){
			var now = new Date();
			JCalendar.update(now.getFullYear(),now.getMonth() + 1,now.getDate());
		}
		if($$("calendar_container").style.visibility="hidden")show("calendar_container");
		e.cancelBubble = true;
		JCalendar.cur_obj_id = obj.id;
	});
	$$("calendar_container").attachEvent("onclick",function(e){e.cancelBubble = true;});
}

/*===========================静态方法=======================================*/
/**
 *更新日历内容
 */
JCalendar.update = function(_year,_month,_date){
	date = new Date(_year,_month-1,1);
	var fday = date.getDay();//每月第一天的星期数
	var year = date.getFullYear();
	var month = date.getMonth() + 1;
	var dayNum = new Date(_year,_month,0).getDate();//每月的天数
	var tds = $TN("td",$$("calendar"));
	var years = $$("select_year").options;
	var months = $$("select_month").options;
	var _date = _date ? _date : JCalendar.cur_date;
	//更新当前年月
	JCalendar.cur_year = year;
	JCalendar.cur_month = month;
	if(_date && _date <= dayNum) JCalendar.cur_date = _date;
	else if(_date > dayNum) JCalendar.cur_date = _date - dayNum;
	$$("title_year").innerText = year + "年";
	$$("title_month").innerText = month + "月";
	//更新年份下拉菜单选中项
	for(var i = years.length - 1; i >= 0; i-- ){
		if(years[i].value == year){
			$$("select_year").selectedIndex = i;
			break;
		}
	}
	//更新月份下拉菜单选中项
	for(var i = months.length - 1; i >= 0; i-- ){
		if(months[i].value == month){
			$$("select_month").selectedIndex = i;
			break;
		}
	}
	//清空日历内容,忽略日历头，即第一行
	for(var i = 7; i < tds.length; i++) tds[i].innerText = "";
	if(	$$("c_today"))$$("c_today").removeAttribute("id");
	for(var j = 1; j <= dayNum; j++){
		tds[6 + fday + j].innerText = j;
		if(j == JCalendar.cur_date) tds[6 + fday + j].id = "c_today";
	}
	JCalendar.onupdate(year,month,JCalendar.cur_date);
}

JCalendar.click = function(obj){
	var tmp = $$("c_today");
	if(tmp && tmp == obj){
		JCalendar.onclick(JCalendar.cur_year,JCalendar.cur_month,JCalendar.cur_date);
	}
	else if(obj.innerText != ""){
		if(tmp) tmp.removeAttribute("id");
		JCalendar.cur_date = parseInt(obj.innerText);
		obj.id = "c_today";
		JCalendar.onclick(JCalendar.cur_year,JCalendar.cur_month,JCalendar.cur_date);
	}
}

JCalendar.over = function(obj){
	if(obj.innerText != "") obj.className = "over";
}

JCalendar.out = function(obj){
	if(obj.innerText != "") obj.className = "";
}

//日历更改时执行的函数，可以更改为自己需要函数,控件传递过来的参数为当前日期
JCalendar.onupdate = function(year,month,date){
	alert("日历已更改，当前日历日期：" + year + "年" + month + "月" + date + "日");
}

//点击日期时执行的函数，可以更改为自己需要函数,控件传递过来的参数为当前日期
JCalendar.onclick = function(year,month,date){
	alert( "当前触发的日期：" + year + "年" + month + "月" + date + "日");
}