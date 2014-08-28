var isIE = /msie/i.test(navigator.userAgent);
var isDTD = /CSS1Compat/i.test(document.compatMode);
String.prototype.trim = function(){
	return this.replace(/(^\s*)|(\s*$)/g,"");
}
Array.prototype.deleteItem = function(item){
	var i,count = this.length;
	for(i = 0;i < count;i++){
		if(this[i] == item){
			this.splice(i,1);
			i--;
			count--;
		}
	}
}
Array.prototype.addItem = function(item){
	for(var i = 0;i < this.length;i++){
		if(this[i] == item)
			return;
	}
	this.push(item);
}
Array.prototype.indexOf = function(_value){
	for(var i = 0;i < this.length;i++)
		if(this[i] == _value) return i;
	return -1;
};
Array.prototype.lastIndexOf = function(_value){
	for(var i = this.length - 1;i >= 0;i--)
		if(this[i] == _value) return i;
return -1;
};
Array.prototype.contains = function(_value){return this.indexOf(_value)!= -1;};
if(!isIE){
	window.constructor.prototype.__defineGetter__("event",function(){
		var func = arguments.callee.caller;
		while(func != null){
			var arg0 = func.arguments[0];
			if(arg0 && (arg0.constructor==Event || arg0.constructor ==MouseEvent)){
				return arg0;
			}
			func = func.caller;
		}
    	return null;
	});
	Event.prototype.__defineSetter__("returnValue",function(b){
		if(!b)this.preventDefault();
		return b;
	});
	Event.prototype.__defineGetter__("srcElement",function(){
		var node=this.target;
		while(node.nodeType != 1)node=node.parentNode;
		return node;
	});
	Event.prototype.__defineGetter__("fromElement",function(){// 返回鼠标移出的源节点
        var node;
        if(this.type == "mouseover")
            node = this.relatedTarget;
        else if(this.type == "mouseout")
            node = this.target;
        if(!node)return;
        while(node.nodeType != 1)node=node.parentNode;
        return node;
        });
    Event.prototype.__defineGetter__("toElement",function(){// 返回鼠标移入的源节点
        var node;
        if(this.type == "mouseout")
            node = this.relatedTarget;
        else if(this.type == "mouseover")
            node = this.target;
        if(!node)return;
        while(node.nodeType != 1)node=node.parentNode;
        return node;
        });
	Event.prototype.__defineGetter__("offsetX",function(){
			return this.layerX;
	});
	Event.prototype.__defineGetter__("offsetY",function(){
		return this.layerY;
	});
	HTMLElement.prototype.attachEvent = function(sType,foo){
		this.addEventListener(sType.slice(2),foo,false);
	}
	HTMLElement.prototype.detachEvent = function(sType,foo){
		this.removeEventListener(sType.slice(2),foo,false);
	}
	HTMLDocument.prototype.attachEvent = function(sType,foo){
		this.addEventListener(sType.slice(2),foo,false);
	}
	HTMLDocument.prototype.detachEvent = function(sType,foo){
		this.removeEventListener(sType.slice(2),foo,false);
	}
	HTMLElement.prototype.__defineGetter__("innerText",function(){
		return this.textContent;
	});
	HTMLElement.prototype.__defineSetter__("innerText",function(str){
		this.textContent = str;
	});
	HTMLElement.prototype.insertAdjacentElement = function(where,parsedNode){
		switch(where){
			case "beforeBegin":
                this.parentNode.insertBefore(parsedNode,this);
                break;
            case "afterBegin":
                this.insertBefore(parsedNode,this.firstChild);
                break;
            case "beforeEnd":
                this.appendChild(parsedNode);
                break;
            case "afterEnd":
                if(this.nextSibling)
                    this.parentNode.insertBefore(parsedNode,this.nextSibling);
                else
                    this.parentNode.appendChild(parsedNode);
                break;
		}
	}
    HTMLElement.prototype.insertAdjacentHTML = function(where,htmlStr){
        var r = this.ownerDocument.createRange();
        r.setStartBefore(this);
        var parsedHTML = r.createContextualFragment(htmlStr);
        this.insertAdjacentElement(where,parsedHTML);
	}
	HTMLElement.prototype.contains = function(Node){// 是否包含某节点
        do if(Node == this)return true;
        while(Node = Node.parentNode);
        return false;
        }
}
else document.execCommand("BackgroundImageCache",false,true);
function $$(id){return (typeof id == "string" ? document.getElementById(id) : id);}
function $N(name){return document.getElementsByName(name);}
function $TN(name,root){return root ? $$(root).getElementsByTagName(name) : document.getElementsByTagName(name);}
function $F(id){return exist(id) ? $$(id).value.trim() : null;}
function $IH(id,s){$$(id).innerHTML = s;}
function $IT(id,s){$$(id).innerText = s;}
function $iF(id,s){$$(id).value = s;}
function $DC(name){return document.createElement(name);}
function isEmpty(str){return str.replace(/(?:null)|(?:undefined)/i,"").length == 0;}
function exist(id){return $$(id)!= null;}
function hide(){
	for(var i = 0; i < arguments.length; i++){
		if(exist(arguments[i])){
			if($$(arguments[i]).style.visibility) $$(arguments[i]).style.visibility = "hidden";
			else $$(arguments[i]).style.display = "none";
		}
	}
}
function show(){
	for(var i = 0; i < arguments.length; i++){
		if(exist(arguments[i])){
			if($$(arguments[i]).style.visibility) $$(arguments[i]).style.visibility="visible";
			else $$(arguments[i]).style.display = "";
		}
	}
}
function $previousSibling(id){
	return (($$(id).previousSibling.nodeName == "#text" && !/^\S$/g.test($$(id).previousSibling.nodeValue)) ? $$(id).previousSibling.previousSibling : $$(id).previousSibling);
}
function $nextSibling(id){
	return (($$(id).nextSibling.nodeName == "#text" && !/^\S$/g.test($$(id).nextSibling.nodeValue)) ? $$(id).nextSibling.nextSibling : $$(id).nextSibling);
}
function $removeNode(id){
	if(exist(id)){
		$$(id).parentNode.removeChild($$(id));
	}
}