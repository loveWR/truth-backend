/*通用JS，所有页引用,放在JQuery引用 后面*/

//console.log只有IE7以上可用， 防止在不支持console的浏览下出错。
if (typeof (console) == "undefined") {
    console = {};
    console.log = function(s) { //alert(s);
     };
}
//为String 加上format方法，如"abcd{0},xyz{1}".format(1,2);

String.prototype.format = function() {
    var args = arguments;
    return this.replace(/\{(\d+)\}/g,
        function(m, i) {
            return args[i];
        });
};

/**tools 工具类开始 *****************/

var tools = new Object();
tools.isMozilla = function() {
    return (typeof document.implementation != 'undefined') &&
    (typeof document.implementation.createDocument != 'undefined') &&
    (typeof HTMLDocument != 'undefined');
}

tools.isIE = function() { return window.ActiveXObject ? true : false; }

tools.IEVer = function() {
    return tools.isIE() ? parseInt(navigator.userAgent.toLowerCase().match(/msie (\d+)/)[1], 10) : 0;
} //只返回主版本号如 6，7，8

tools.isFirefox = function() { return (navigator.userAgent.toLowerCase().indexOf("firefox") != -1); }

tools.isSafari = function() { return (navigator.userAgent.toLowerCase().indexOf("safari") != -1); }

tools.isOpera = function() { return (navigator.userAgent.toLowerCase().indexOf("opera") != -1); }

tools.htmlEncode = function(text) {
    return text.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

//去除字符前后的指定字符，fixReg为普通字符，如果不指定，表示去空格。  ignoreCase指定为false，则区分大小写
tools.trim = function(text, fixReg, ignoreCase) {
    if (typeof (text) == "string") {
        if (!fixReg) {
            return text.replace(/^(\s|\u00A0)+|(\s|\u00A0)+$/g, "");
        } else {
            if (fixReg.length > 1) { //要去除的是多个字符
                var ex = new RegExp("^" + fixReg + "|" + fixReg + "$", "g" + (ignoreCase != false ? "i" : ""));
                var ret = text;
                var old = text;
                do {
                    old = ret;
                    ret = old.replace(ex, "");
                } while (old.length > ret.length)
                return ret;
            } else { //要去除的是单个字符
                var ex = new RegExp("^" + fixReg + "*|" + fixReg + "*$", "g" + (ignoreCase != false ? "i" : ""));
                return text.replace(ex, "");
            }
        }
    } else {
        return text;
    }
}

//替换字符串中指定的字符，oldstr,newstr均为普通字符，如果遇到\ ()[] 等正则字符，需要转义 ignoreCase指定为false，则区分大小写
/*
 如：          var test = "\rabcabcoooOO\\r123"; 第一个\r是一个字符，后面的\\r是表示两个字符
 test = tools.replace(test, "\r", ""); 替换前面的\r
 test = tools.replace(test, "\\\\r", "",false);替换后面的\\r
 */
 tools.replace = function(text, oldStr, newstr, ignoreCase) {
    var ex = new RegExp(oldStr, "g" + (ignoreCase != false ? "i" : ""));
    return text.replace(ex, newstr);
}

tools.isEmpty = function(val) {
    switch (typeof (val)) {
        case 'string':
        return tools.trim(val).length == 0;
        break;
        case 'number':
        return val == 0;
        break;
        case 'object':
        return val == null;
        break;
        case 'array':
        return val.length == 0;
        break;
        default:
        return true;
    }
}

tools.isNumber = function(val) {
    var reg = /^[\d|\.|,]+$/;
    return reg.test(val);
}

tools.isInt = function(val) {
    if (val == "") {
        return false;
    }
    var reg = /\D+/;
    return !reg.test(val);
}

tools.isEmail = function(email) {
    var reg1 = /([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)/;

    return reg1.test(email);
}

tools.isTel = function(tel) {
    var reg = /^[\d|\-|\s]+$/; //只允许使用数字-空格等

    return reg.test(tel);
}

tools.srcElement = function(e) {
    if (typeof e == "undefined") e = window.event;
    var src = document.all ? e.srcElement : e.target;
    return src;
}

tools.isTime = function(val) {
    var reg = /^\d{4}-\d{2}-\d{2}\s((([0-1]?[0-9])|(2[0-3])):([0-5]?[0-9])(:[0-5]?[0-9])?)$/;

    return reg.test(val);
}

tools.GetURLparam = function(url, param) {
    var sValue = url.match(new RegExp("[\?\&]" + param + "=([^\&]*)(\&?)", "i"));
    return sValue ? unescape(sValue[1]) : "";
}
 

tools.SafeJSON = function(str) {
    str = str.replace(/\\/g, "\\\\");
    str = str.replace(/\"/g, "\\\"");
    return str;
}

tools.getCookie = function(sName) {
    // cookies are separated by semicolons
    var aCookie = document.cookie.split("; "); //注意分号后有1个空格
    for (var i = 0; i < aCookie.length; i++) {
        // a name/value pair (a crumb) is separated by an equal sign
        var aCrumb = aCookie[i].split("=");
        if (sName === aCrumb[0])
            return decodeURIComponent(aCrumb[1]);
    }
    // a cookie with the requested name does not exist
    return "";
}

tools.setCookie = function(sName, sValue, days, domain) {
    sValue = encodeURIComponent(sValue);
    var expires = "";
    days=(!!days)?days:1; //默认1天
    
    var date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    expires = "; expires=" + date.toGMTString();
    var acookie = sName + "=" + sValue + expires + "; path=/" + (domain ? "; domain=" + domain : "");
    // alert(acookie); //test
    document.cookie = acookie;
}

tools.removeCookie = function(sName) {
    this.setCookie(sName, "", -1);
}


tools.getPosition = function getPosition(o) //对象在页面上的绝对位置，O是DOM对象
{
    var t = o.offsetTop;
    var l = o.offsetLeft;
    while (o === o.offsetParent) {
        t += o.offsetTop;
        l += o.offsetLeft;
    }
    var pos = { top: t, left: l };
    return pos;
}

tools.getMousePos = function() //鼠标在页面上的绝对位置
{
    var mouseX = 0;
    var mouseY = 0;
    var e = getEvent(); //alert(e.clientX)
    var bb = (document.compatMode && document.compatMode !== "BackCompat") ? document.documentElement : document.body;

    mouseX = e.clientX + bb.scrollLeft;
    mouseY = e.clientY + bb.scrollTop;

    return { left: mouseX, top: mouseY };
}

tools.getEvent = function() //触发事件的对象 同时兼容ie和ff的写法
{
    if (document.all) return window.event;
    var func = getEvent.caller;
    while (func != null) {
        var arg0 = func.arguments[0];
        if (arg0) {
            if ((arg0.constructor === Event || arg0.constructor === MouseEvent) ||
                (typeof (arg0) == "object" && arg0.preventDefault && arg0.stopPropagation)) {
                return arg0;
        }
    }
    func = func.caller;
}
return null;
}

/* hashTable实现开始
 var hashTable = new tools.HashTable();
 hashTable.add("key", "value");
 */
 tools.HashTable = function() {
    this.items = new Array();
    this.itemsCount = 0;
    this.add = function(key, value) {
        if (!this.containsKey(key)) {
            this.items[key] = value;
            this.itemsCount++;
        } else
        throw "key '" + key + "' allready exists.";
    }
    this.get = function(key) {
        if (this.containsKey(key))
            return this.items[key];
        else
            return null;
    }

    this.remove = function(key) {
        if (this.containsKey(key)) {
            delete this.items[key];
            this.itemsCount--;
        }
    }
    this.containsKey = function(key) {
        return typeof (this.items[key]) != "undefined";
    }
    this.containsValue = function containsValue(value) {
        for (var item in this.items) {
            if (this.items[item] == value)
                return true;
        }
        return false;
    }
    this.contains = function(keyOrValue) {
        return this.containsKey(keyOrValue) || this.containsValue(keyOrValue);
    }
    this.clear = function() {
        this.items = new Array();
        this.itemsCount = 0;
    }
    this.size = function() {
        return this.itemsCount;
    }
    this.isEmpty = function() {
        return this.size() == 0;
    }
    this.keys = function() { //以数组的方式反回索引列表
        var keys = new Array();
        for (var i in this.items) {
            keys.push(i);
        }
        return keys;
    }
    this.values = function() { //以数组的方式反回值列表
        var Values = new Array();
        for (var i in this.items) {
            Values.push(this.items[i]);
        }
        return Values;
    }
};
/*hashTable实现结束 */

/**tools 工具类结束 ************* */


/**框架功能开始**/
/** 
*将对象数组的某个属性以逗号拼接起来
*@param { } objar 对象数组
*@param { } pro 属性名， 如id
*@return  字符串,如 1,2,3,5
*/
$.projoin=function(objar,pro)
{
  var ar=[];
  for(var i=0; i<objar.length; i++)
    {
      var v=eval('objar['+i+'].'+pro)   ;
      ar.push(v);
    }
    return ar.join(',');
}

/**
 * 重新载入当前页
 * @returns {}
 */
 $.reload = function() {
    location.reload();
    return false;
}
/**
 * 打开或关闭layer 的 LOADING。。。
 * @param { } bool  true 打开，false 关闭
 * @param {} text   提示信息,暂不使用
 * @returns {}
 */
 var loadingidx=-1;
 $.loading = function(bool, text) {
    if(bool)
    { if(loadingidx>-1) return false;
        loadingidx=top.layer.load();
    }else
    {
        top.layer.close(loadingidx);
        loadingidx=-1;
    }
}
/**
 * 获取当前URL中参数值
 * @param {} name
 * @returns {}
 */
 $.queryString = function(name) {
    var search = location.search.slice(1);
    var arr = search.split("&");
    for (var i = 0; i < arr.length; i++) {
        var ar = arr[i].split("=");
        if (ar[0] === name) {
            return decodeURIComponent(ar[1]);
        }
    }
    return "";
}

 
/**
 * 获取当前浏览器的名字
 * @returns {}
 */
 $.browser = function() {
    var userAgent = navigator.userAgent;
    var isOpera = userAgent.indexOf("Opera") > -1;
    if (isOpera) {
        return "Opera";
    };
    if (userAgent.indexOf("Firefox") > -1) {
        return "FF";
    }
    if (userAgent.indexOf("Chrome") > -1) {
        if (window.navigator.webkitPersistentStorage.toString().indexOf('DeprecatedStorageQuota') > -1) {
            return "Chrome";
        } else {
            return "360";
        }
    }
    if (userAgent.indexOf("Safari") > -1) {
        return "Safari";
    }
    if (userAgent.indexOf("compatible") > -1 && userAgent.indexOf("MSIE") > -1) {
        return "IE";
    };
}
/**
 * 下载一个文件，通过POST 一个FORM方法
 * @param {} url  目标地址，
 * @param {} data   
 * @param {} method 
 * @returns {}
 */
 $.download = function(url, data, method) {
    if (url  ) {
        if(!!data){
        data = typeof data == 'string' ? data : jQuery.param(data);
        var inputs = '';
        $.each(data.split('&'),
            function() {
                var pair = this.split('=');
                inputs += '<input type="hidden" name="' + pair[0] + '" value="' + pair[1] + '" />';
            });
    }
        $('<form action="' + url + '" method="' + (method || 'post') + '">' + inputs + '</form>')
        .appendTo('body')
        .submit()
        .remove();
    };
};
/**
 * 弹出一个MODAL窗口
 * @param {} options
 * @returns {}
 */
 $.modalOpen = function(options) {
    var defaults = {
        maxmin: true, //开启最大化最小化按钮
        title: '弹出窗口',
        width: "800px",
        height: "600px",
        url: '',
        shade: 0.3,
        btn: ['确定', '关闭'],
        btnclass: ['btn btn-primary', 'btn btn-danger'],
        scrollbar:false,  //默认不启用浏览器滚动
        callBack: null
    };
    options = $.extend(defaults, options);
    var _width = parseInt(options.width + "".replace('px', '')); // 转换为数值类型进行比较，确保不超过窗口大小
    var _height = parseInt(options.height + "".replace('px', ''));
    _width = top.$(window).width() > _width ? _width + 'px' : top.$(window).width() + 'px';
    _height = top.$(window).height() > _height ? _height + 'px' : top.$(window).height() + 'px';
    top.layer.open({
        type: 2,
        shade: options.shade,
        title: options.title,
        area: [_width, _height],
        content: options.url,
        btn: options.btn,
        btnclass: options.btnclass,
        scrollbar:options.scrollbar,
        btnAlign: 'c',
        maxmin:options.maxmin,
        yes: function(index, layero) {
            //确定按钮，执行给定的回调。
            //在以下回调中完成弹窗的关闭和弹窗返回数据的运用。
            options.callBack(index);
        },
        cancel: function() {
            //取消按钮无动作
            return true;
        },
        success: function(layero, index){
            if(options.max){
                top.layer.full(index);}
            }

        });
}

/**
 * 从右侧拉出一个非模态窗口
 * @param {} options
 * @returns {}
 */
 var slideDialog;
 $.slideOpen = function(options) {
     if (top.slideDialog !== undefined) {
        top.layer.close(top.slideDialog);
    }

    var defaults = {
        maxmin: true, //开启最大化最小化按钮
        title: '弹出窗口',
        width: "750px",
        height: "100%",
        url: '',
        btn: [  '关闭'],
        btnclass: ['btn btn-primary' ],
        callBack: null
    };
    options = $.extend(defaults, options);
    
   top.slideDialog= top.layer.open({
        type: 2,
        shade: 0, //不要遮罩
        anim :  1,//2右下角往上 1右上角往下
        offset:'rb',
        title: options.title,
        area: [options.width, options.height],
        content: options.url,
        btn: options.btn,
        btnclass: options.btnclass,
        btnAlign: 'c',
        maxmin:options.maxmin,
        yes: function(index, layero) {
           top.layer.close(index);
        },
        cancel: function() {
            //取消按钮无动作
            return true;
        },
        success: function(layero, index){
            if(options.max){
                top.layer.full(index);}
            }

        });
}

/**
 * 确认弹框
 * @param {} content
 * @param {} callBack
 * @returns {}
 */
 $.modalConfirm = function(content, callBack) {
    top.layer.confirm(content,
    {
        icon: " fa fa-question-circle",
        title: "系统提示",
        btn: ['确认', '取消'],
        btnclass: ['btn btn-primary', 'btn btn-danger'],
    },
    function() {
        callBack(true);
    },
    function() {
        callBack(false);
    });
}
/**
 * 提示信息弹框
 * @param {} content
 * @param {} type
 * @returns {}
 */
 $.modalAlert = function(content, type) {
    var icon=" fa fa-exclamation-circle";
    if (type === 'success') {
        icon = " fa fa-check-circle";
    }
    else if (type === 'error') {
        icon = " fa fa-times-circle";
    }
    else if (type === 'warning') {
        icon=" fa fa-exclamation-triangle";
    }
    //未登录的处理
    else if (type === 'unlogin') {
        //todo:处理未登录的情况 ， 自动跳转到登录界面。
        icon=" fa fa-lock";
    }
    top.layer.alert(content,
    {
        icon: icon,
        title: "系统提示",
        btn: ['确认'],
        btnclass: ['btn btn-primary']
    });
}
/**
 * 自动 关闭消息提示框
 * @param {} content
 * @param {} type
 * @returns {}
 */
 $.modalMsg = function(content, type) {
    if (type != undefined) {
        var icon =" fa fa-exclamation-circle"; //默认图标
        if (type == 'success') {
            icon =" fa fa-check-circle";
        }
        else if (type == 'error') {
            icon =" fa fa-times-circle";
        }
        else if (type == 'warning') {
            icon=" fa fa-exclamation-triangle";
        }
        //未登录的处理
        else if (type === 'unlogin') {
            //todo:处理未登录的情况 ， 自动跳转到登录界面。
            icon=" fa fa-lock";
        }
        top.layer.msg(content, { icon: icon, time:  2000, shift: 5 });
    } else {
        top.layer.msg(content);
    }
}
/**弹窗提示输入内容
使用示例
$(".btnPrompt").on("click", function() {
            $.modalPrompt({title:'请输入审核意见',callBack:promptcb});        })
 function promptcb(text)   {  alert(text);    }

*/
 $.modalPrompt = function(options) {
    //配置项可用参数，参考layer插件文档。
    var defaults = {
        title: '请输入',
        formType:2,  // //输入框类型，支持0（文本）默认1（密码）2（多行文本）
        callBack: null
    };
    options = $.extend(defaults, options);
    top.layer.prompt(options, function(text, index){
        top.layer.close(index);
        if(options.callBack!=null)
        {
        window.setTimeout( function(){ options.callBack(text); },100);
       }
  });
}
/**
 *关闭指定index的弹窗，或当前弹窗。 弹窗上的按钮，必须带index关闭。
 * iframe内页中可以不带index 以当前window.name取得弹窗的index
 * @returns {}
 */
 $.modalClose = function(layIndex) {
    //先得到lay层的索引，
    var index = (!!layIndex) ? layIndex : top.layer.getFrameIndex(window.name);
    //弹窗按钮条上，是否勾选了禁止关闭
    var $IsdialogClose = top.$("#layui-layer" + index).find('.layui-layer-btn').find("#IsdialogClose");
    var IsClose = $IsdialogClose.is(":checked");
    if ($IsdialogClose.length === 0) {
        IsClose = true;
    }
    if (IsClose) {
        top.layer.close(index);
    } else {
        location.reload();
    }
}

/**
 * 在弹窗中触发弹窗的确定按钮，如双击时触发。 $.modalOK(window.name);
 * @param {} winName 传入弹窗的window.name
 * @returns {}
 */
 $.modalOK = function (winName) {
    top.$("#" + winName).parent().parent().find("a.layui-layer-btn0").trigger("click");
}
/**
 * 根据弹窗的index，取得弹窗中iframe中的window对象
 * @returns {}
 */
 $.modalWindow = function(layIndex) {
    var winname = "layui-layer-iframe" + layIndex;
    var win = top.frames[winname];
    if (!win) {
        console.log(winname + "不存在");
    }
    return win;
}

/**
 * ajax提交form
 * 编辑页面要保存后不关闭弹窗，继续新增下一条时，将 close设置为false,并在success回调中准备新增下一条。
 * @param {} options
 * @returns {}
 */
 $.ajaxSubmitForm = function(options) {
    var defaults = {
        url: "",
        param: [],
        success: null,
        close: true  
    };
    options = $.extend(defaults, options);
     $.loading(true );
   window.setTimeout(function() {
            $.ajax({
                url: options.url,
                data: options.param,
                type: "post",
                dataType: "json",
                success: function(data) {
                    if (data.state == "success") {
                        options.success(data);
                        $.modalMsg(data.message, data.state);
                        if (options.close == true) {
                            $.modalClose();  //关闭当前弹出的窗口
                        }
                    } else {
                        $.modalAlert(data.message, data.state);
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                   $.loading(false );
                   $.modalMsg(errorThrown, "error");
               },
               beforeSend: function() {
                  //  $.loading(true, options.loading);
              },
              complete: function() {
                 $.loading(false );
               }
           });
        },
        200);
}
 
/**
 * ajax提交FORM执行,并给出确认提示
 * 默认提示prompt: "您确定要删除选择的数据吗？" 
 * @param {} options
 * @returns {}
 */
 $.ajaxConfirm = function(options) {
    var defaults = {
        prompt: "您确定要删除选择的数据吗？",
        url: "",
        param: [],
        success: null,
        close: true
    };
    options = $.extend(defaults, options);
   
    $.modalConfirm(options.prompt,
        function(r) {
            if (r) {
                $.loading(true );
                window.setTimeout(function() {
                    $.ajax({
                        url: options.url,
                        data: options.param,
                        type: "post",
                        dataType: "json",
                        success: function(data) {
                            if (data.state == "success") {
                                options.success(data);
                                $.modalMsg(data.message, data.state);
                            } else {
                                $.modalAlert(data.message, data.state);
                            }
                        },
                        error: function(XMLHttpRequest, textStatus, errorThrown) {
                            $.loading(false);
                            $.modalMsg(errorThrown, "error");
                        },
                        beforeSend: function() {
                            
                        },
                        complete: function() {
                            $.loading(false);
                        }
                    });
                },
                500);
            }
        });
}

 
/**
 * jquery validate 验证后提示信息位置自定义
 * @returns {void}
 */
 $.fn.bindValidate = function() {
    //验证不通过，将错误提示显示在输入框的内右侧，并通过tooltip插件显示错误原因。
    //验证通过，去除错误提示
    $(this)
    .validate({
        focusCleanup: false,
            ignore:".ignore",//默认忽略:hidden 为空则不忽略
            focusInvalid: true, //自动定位到第一验证不通过的域
            errorElement: "i",
            //  focusCleanup: true,
            errorPlacement: function(error, element) {
                  var targ = element.attr("errorPos");
                    if (targ) {  //指定了错误显示的位置
                        $(targ).html("").append(error.html()); //清空内容并添加
                    } else {

                        var p =element.parent();
                        p.find("em.error").remove();
                        p.append('<em class="fa fa-exclamation-circle error speical " data-placement="left" data-toggle="tooltip" title="' + error.html() + '"></em>');
                    }
                    $("[data-toggle='tooltip']").tooltip(); //以tooltip的方式显示错误提示
                
            },
            success: function (label,element) {
                 var obj=$(element)  //验证成功的元素DOM
                var targ = obj.attr("errorPos"); 
                if(targ)  //v如果指定了错误显示的容器，则将容器清空
                {
                  $(targ).html("");
                }
                else  //未指定错误显示容器，则默认移除同级的EM.error
                { obj.parent().find('em.error').remove();}
            }
        });
}


/**
 * 序列化form或某个区域(如div)中的HTML域，用于ajax提交
 * @param {} formdate
 * @returns {}
 */
 $.fn.formSerialize = function() {
    var element = $(this);
    var postdata = {};
    //text，hidden，select 必须有ID或Name属性，优先用ID作键值传到后台 
    element.find("input[type='date'],input[type='datetime-local'],input[type='text'],input[type='number'],input[type='hidden'],input[type='password'],select,textarea")
    .each(function() {
        var id = $(this).attr("id");
        id = (id) ? id : $(this).attr("name");
        if (id) {
            postdata[id] = $(this).val();
        }
    });

    //对于radio ,checkbox 必须指定DOM的name值，以name作键值专到后台
    element.find("input[type='radio']:checked,input[type='checkbox']:checked")
    .each(function() {
        var name = $(this).attr("name");
        if (name) {
            if (postdata[name]) {
                postdata[name] += "," + $(this).val();
            } else {
                postdata[name] = $(this).val();
            }
        }
    });
    //搜索条件拼装时,如果是高级搜索，加入高级搜索标识is_adv_search=1
    if(element.hasClass('is_adv_search'))
    {  
        postdata["is_adv_search"]=1;
    }
    return postdata;
};

/**
 * 同步的方式，取得select 的下拉项，并转换为select2 插件, 默认选中值在data-select属性中。
 * @param {} options
 * @returns {}
 */
 $.fn.bindSelect = function(options) {
    var defaults = {
        id: "id",
        text: "text",
        search: false,
        url: "",
        param: [],
        change: null
    };
    options = $.extend(defaults, options);
    var $element = $(this);
    if (options.url !== "") {
        $.ajax({
            url: options.url,
            data: options.param,
            dataType: "json",
            async: false,
            success: function(data) {
                $.each(data,
                    function(i) {
                        $element.append($("<option></option>").val(data[i][options.id]).html(data[i][options.text]));
                    });
                $element.select2({
                    minimumResultsForSearch: options.search == true ? 0 : -1
                });
                $element.on("change",
                    function(e) {
                        if (options.change != null) {
                            options.change($(this).val());
                        }
                        $("#select2-" + $element.attr('id') + "-container")
                        .html($(this).find("option:selected").text().replace(/　　/g, ''));
                    });
            }
        });
    } else {
        $element.select2({
            minimumResultsForSearch: -1
        });
    }
}

/** 通用选框开始
 *所有的数据选框页面必须实现一个数据返回方法，返回一个数组（存放JSON对象）， function getReturnData() { return [{ a: 3, b: 4 }]; }
 */
/**
 * 通过树状方式选择节点对象
 * 使用例子：<input id="txt" value="" type="text" /> <span id="lbl"> </span>
 * <input id="btntest" type="button" value="button" onclick="dataDlgTree('selectdept','btntest',  DataDlg_CallBack ) ;" data-dlgreturn="txt.val()=deptname;lbl.html() = deptname;txt.recid=id" />
 * @param {} DlgSN 数据库中定义的选框唯一号
 * @param {} callbackfn 回调的JS方法
 * @param {} triggerId 调用界面打开选框的DOMID
 * @param {} param 附加参数 只以以如下格式表达： a-3_b-4, 表示a=3&b=4 所以值的内容不能包含-和_ 
 * @returns {} 返回数组 ，每项为一个对象
 */
function dataDlgTree(DlgSN,triggerId, callbackfn,param) {
    var param=param||'';
    var url = "/admin/tools/dlgtree?dlgsn=" + DlgSN+'&param='+param +"&rnd=" +Math.random();
    dataDlg(DlgSN, url, triggerId, callbackfn);
}

function dataDlgTable(DlgSN, triggerId, callbackfn,param) {
    var param=param||'';
    var url = "/admin/tools/dlgtable?dlgsn=" + DlgSN+'&param='+param + "&rnd=" + Math.random();
    dataDlg(DlgSN, url, triggerId, callbackfn);
}

/**
 *通用用户选择弹窗
 * @param isMulti 是否多选 如 true / false
 * @param triggerId 按钮ID
 * @param callbackfn 回调方法
 */
 function dlgSelectUser( isMulti , triggerId, callbackfn) {
    var url = "/admin/tools/selectUser?ismulti="+isMulti + "&rnd=" + Math.random();
    dataDlg('selectuser', url, triggerId, callbackfn);
}

/**
 *通用代码选择弹窗，传入代码类型和是否多选
 * @param Codetype 代码类型(数据库codetype中定义的codekey) 如 'depttype'
 * @param isMulti 是否多选 如 true / false
 * @param triggerId 按钮ID
 * @param callbackfn 回调方法
 */
 function dlgSelectCode(codeType,isMulti , triggerId, callbackfn) {
    var url = "/admin/tools/selectCode?codeType=" + codeType+"&ismulti="+isMulti + "&rnd=" + Math.random();
    dataDlg('selectcode', url, triggerId, callbackfn);
}
/**
 * 树状方式通用代码选择弹窗，传入代码类型和是否多选
 * @param codeType 代码类型
 * @param isMulti   true / false是否多选
  * @param async   true / false是否异步加载
 * @param triggerId  按钮ID
 * @param callbackfn 回调
 */
 function dlgSelectCodeTree(codeType,isMulti,async , triggerId, callbackfn) {
    var url = "/admin/tools/selectCodeTree?codeType=" + codeType+"&ismulti="+isMulti+"&async="+async + "&rnd=" + Math.random();
    dataDlg('selectcodetree', url, triggerId, callbackfn);
}
//执行弹窗，确定时执行回调
function dataDlg(DlgSN, url, triggerId, callbackfn) {
    var dlgsize = getDataDlgSize(DlgSN); //根据DlgSN弹窗ID，取得弹窗大小，tools.js文件中硬编码定义
    var cb =  callbackfn||public_DlgCallBack  ; //未指定callbackfn，则使用通用的回调方法public_DlgCallBack
    var returnData = [];  //弹窗返回的数据，数组表示，数据中存放JSON对象
    //执行弹窗
    $.modalOpen({
        url: url,
        title: "请选择",
        width: dlgsize.width,
        height: dlgsize.height,
        callBack: function(idx) {
            var win = $.modalWindow(idx);     //弹窗中的windows
            returnData = win.returnData(); // 获取弹窗中返回的数据
            if (!returnData) return false;
            cb(returnData, triggerId);       //执行回调 ，fromDomId是触发弹窗的DOM对象id
            $.modalClose(idx);              //关闭弹窗
        }
    });
}

 
/**
 * 文件上传
 * @param savePath 保存的路径，相对于/upload 的目录 
 * @param autoDir   true / false是否自动生成日期目录 
 * @param maxSize  最大文件大小， 带单位如： 12MB，0表示不限
 * @param maxFiles 最多文件个数，0表示不限
 * @param fileTypes 允许的文件类型后缀如：  'gif,jpg,jpeg,bmp,png',"*"
 * @param callbackfn 回调，必填

 */
function uploadFile(savePath, autoDir,  maxSize, maxFiles, fileTypes, callbackfn) {
    var returnData = [];  //弹窗返回的数据，数组表示，数据中存放JSON对象
    //执行弹窗
    $.modalOpen({
        url: "/admin/upload/index?savePath=" + savePath + "&autoDir=" + autoDir + "&maxSize=" + maxSize + "&maxFiles=" + maxFiles + "&fileTypes=" + fileTypes + "&rnd=" + Math.random(),
        title:  "上传文件",
        width:800,
        height: 600,
        callBack: function (idx) {
            var win = $.modalWindow(idx);     //弹窗中的windows
            returnData = win.returnData(); // 获取弹窗中返回的数据
            if (!returnData) return false;
            callbackfn(returnData );       //执行回调  
            $.modalClose(idx);              //关闭弹窗
        }
    });
}

/**
 * 图片上传
 * @param savePath 保存的路径，相对于/upload 的目录 
 * @param autoDir   true / false是否自动生成日期目录 
 * @param maxSize  带单位如： 12MB，0表示不限
 * @param maxFiles 最多文件个数，0表示不限
 * @param callbackfn 回调,必填
 */

function uploadImage(savePath, autoDir,  maxSize, maxFiles,callbackfn) {
    var fileTypes = "gif,jpg,jpeg,png,bmp,ico,jp2,tiff";
    var returnData = [];  //弹窗返回的数据，数组表示，数据中存放JSON对象
    //执行弹窗
    $.modalOpen({
        url: "/admin/upload/index?savePath=" + savePath + "&autoDir=" + autoDir + "&maxSize=" + maxSize + "&maxFiles=" + maxFiles + "&fileTypes=" + fileTypes  +"&rnd=" + Math.random(),
        title: "上传图片",
        width: 800,
        height: 600,
        callBack: function (idx) {
            var win = $.modalWindow(idx);     //弹窗中的windows
            returnData = win.returnData(); // 获取弹窗中返回的数据
            if (!returnData) return false;
            callbackfn(returnData );       //执行回调  
            $.modalClose(idx);              //关闭弹窗
        }
    });
}

/**
 * 快速封面图片上传控件。用法 如下：
 *<div id="uploader-demo" class="uploader">
 <img src="__ADMIN__/img/uploaddefault.png" alt="" id="img1" width="100" height="100">
 <div class="uploadInfo text-center">
 上传成功
 </div>
 <!--用来存放item-->
 <div id="filePicker1">选择图片</div>
 <input type="" name="input1" id="input1" value="" />
 </div>
    JS：imagePicker('#filePicker1', '#img1', '#input1');
 * @param pickerid 如:#filePicker1
 * @param trigger    如:#image1   触发选择文件动作
 * @param inputid  如:#input1  
 * @param filetypes   文件类型，不含点号如： "xls,doc"、"*" ,所有文件类型用"*"表示
 */
function imagePicker(pickerid,trigger,inputid,filetypes)
{
    filetypes=filetypes||'gif,jpg,jpeg,bmp,png,ico';

    var uploader = WebUploader.create({
        // 选完文件后，是否自动上传。
        auto: true,
        // swf文件路径
        swf: '/public/static/admin/js/webuploader/Uploader.swf',
        // 文件接收服务端。
        server: '/admin/upload/saveFile?autoDir=true',
        // 选择文件的按钮。可选。
        // 内部根据当前运行是创建，可能是input元素，也可能是flash.
        pick:  pickerid,
        duplicate:true,//允许重复上传

        // 只允许选择图片文件。
        accept: {
            title: '请选择要上传的图片',
            extensions: filetypes, //'gif,jpg,jpeg,bmp,png,ico';
            mimeTypes: '*'
        },
    });
    uploader.on( 'uploadSuccess', function( file , response) {
        $( trigger).attr('src', response.attfile.fileurl);
        $( inputid).val(response.attfile.fileurl);
        $( trigger).siblings(".uploadInfo").show(500);
    });
    uploader.on("uploadStart",function(file,percentage){
        $( trigger).siblings(".uploadInfo").hide();
    });
    $( trigger).on("click",function(){
        $(this).siblings().find("input[type=file]").click();
    });
}

/**
 * 快速上传文件控件。用法 如下：
 <div id="uploader-demo" class="uploader">
            <!--用来存放item-->
            <div id="filePicker3">选择图片</div>
            <input type="" name="input1" id="input3" value="" class="cp" />
            <button src="" class="fa fa-cloud-upload fa-lg btn btn-primary btn-myset" alt="" id="img3" width="100" height="100">点击上传</button>
        </div>
    JS：imagePicker('#filePicker3', '#img3', '#input3');
 * @param pickerid 如:#filePicker3
 * @param trigger    如:#img3   触发选择文件动作
 * @param inputid  如:#input3  
 * @param filetypes   文件类型，不含点号如： "xls,doc"、"*" ,所有文件类型用"*"表示
 */
function filePicker(pickerid,trigger, inputid,filetypes )
{
    filetypes=filetypes||'*';
    var uploader = WebUploader.create({
        // 选完文件后，是否自动上传。
        auto: true,
        // swf文件路径
        swf: '/public/static/admin/js/webuploader/Uploader.swf',

        // 文件接收服务端。
        server: '/admin/upload/saveFile?autoDir=true',
        // 选择文件的按钮。可选。
        // 内部根据当前运行是创建，可能是input元素，也可能是flash.
        pick:  pickerid,
        duplicate:true,//允许重复上传
        // 只允许选择图片文件。
        accept: {
            title: '请选择要上传的文件',
            extensions: filetypes,
            mimeTypes: '*'
        },
    });
    uploader.on( 'uploadSuccess', function( file , response) {
        $( inputid).val(response.attfile.fileurl);
    });
 
    $( trigger).on("click",function(){
        $(this).siblings().find("input[type=file]").click();
    });
}

/** 图片裁剪，给定一个图片URL，要裁剪的宽高，以及裁剪后的回调，回调中的参数，见attfile.php 中的imgCropSave
<button type="button" class="fa fa-cut fa-lg btn btn-primary btn-myset" id="btnupload3" 
onclick="imageCrop($('#input3').val(),300,300,imageCropcbs);"> 裁剪 </button>
*/
function imageCrop(imgurl,width,height, callbackfn)
{
    $.modalOpen({
        title: '裁剪图片',
        maxmin: 'true',
        // max:true,
        width:1920,
        height: 1080,
        url: '/admin/attfile/imgcrop?src='+imgurl+"&width="+width+"&height="+height,
        callBack: function(idx) {
            var win = $.modalWindow(idx);
            win.submitForm(callbackfn);
        }
    });

}

/**
 * JS定义数据选框的大小，与数据库中定义的选框SN保持一致。
 * @param {} dlgSN  数据库定义的选框唯一号
 * @returns {} 返回一个对象{width=133,height=200},w,h不带px单位
 */
function getDataDlgSize(dlgSN) {
    switch (dlgSN.toLowerCase()) {
        case 'selectcode':
        return { width: 850, height: 600 };
        case 'selectcodetree':
        return { width: 600, height: 600 };
        case 'selectuser':
        return { width: 850, height: 600 };
        case 'selectdept':
        return { width: 800, height: 600 };
        default:
        return { width: 800, height: 600 };
    }
}


/*数据选择框回调开始*/

/**
 * 树状选择 datadlgreturn：定义接收方式   txt.val() 表示用方法接收， txt.recid  表示用属性接收 ，+= 表示累加接收
 <input id="btntest" type="button" value="select dept" onclick="dataDlgTree('selectdept',false,1, 'btntest',public_DlgCallBack) ;" data-dlgreturn="txt.val()+=deptname;lbl.html() = deptname;txt.recid=id" data-dlgcallback="alert" />
 表格单选
 <input id="Button2" type="button" value="select user" onclick="dataDlgSingle('selectuser', 'Button2',public_DlgCallBack) ;" data-dlgreturn="txt.val()+=fullname;lbl.html() =deptname ;txt.recid=loginid" />
 表格多选
 <input id="Button3" type="button" value="select users" onclick="dataDlgMulti('selectusers', 'Button3',public_DlgCallBack) ;" data-dlgreturn="txt.val()+=fullname;lbl.html() =deptname ;txt.recid=loginid" />
 * @param {} result 是一个包括对象的数组
 * @param {} fromDomID 调用页面触发弹窗的DOM对象的ID
 * @returns {} 不返回
 */
function public_DlgCallBack(result, fromDomID) {
    //console.log([result, fromDomID]); //debug
    if (result) {
        var btn = $("#" + fromDomID);
        var sets = btn.attr("data-dlgreturn"); //返回的数据如何设置到其它对象的属性
        var cb2 = btn.attr("data-dlgcallback"); //附加调用的回调方法
        if (sets) {
            sets = tools.replace(sets, " ", "");
            sets = tools.trim(sets, ";", true);
            var ar = sets.split(';');
            var arkey = new Array();
            var arvalue = new Array();
            var artmp;
            var i;
            for (i = 0; i < ar.length; i++) {
                if (ar[i]) {
                    artmp = ar[i].split('=');
                    arkey.push(artmp[0]);
                    arvalue.push(artmp[1]);
                }
            }
            for (i = 0; i < arvalue.length; i++) { //每个arvalue项 其实是result项中的字段
                var fieldname = arvalue[i];
                var list = "";
                for (var j = 0; j < result.length; j++) { //返回的result是数组，将所有项的值以,相连
                    list += "," + eval("result[j]." + fieldname);
                }
                arvalue[i] = tools.trim(list, ",", true);
            }

            for (i = 0; i < arkey.length; i++) {
                var key = arkey[i];
                artmp = key.split(".");
                if (artmp.length !== 2) continue;
                var id = artmp[0];
                var att = artmp[1];
                var o = " $('#" + id + "').";
                var obj = id; //获取对象
                $("#" + id).change();//手动触发change事件
                if (att.substr(att.length - 1) === "+") {
                    att = att.substr(0, att.length - 1); //去掉+号
                    var oval;
                    if (att.substr(att.length - 2) === "()") { //方法
                        att = att.substr(0, att.length - 2); //去掉（）号
                        oval = eval(o + att + "()");
                        oval = oval === "" ? "" : (oval + ",");
                        eval(o + att + "(oval+arvalue[i]);");
                    } else { //属性
                        oval = eval(o + "attr('" + att + "')");
                        oval = oval === "" ? "" : (oval + ",");
                        eval(o + "attr('" + att + "', oval+arvalue[i]);");
                    }

                } else {
                    if (att.substr(att.length - 2) === "()") { //方法
                        att = att.substr(0, att.length - 2); //去掉（）号
                        eval(o + att + "(arvalue[i]);");
                    } else { //属性
                        eval(o + "attr('" + att + "', arvalue[i]);");
                    }
                }
            }
        }
        if (cb2) {
            eval(cb2 + "(result);");
        }

    }
}


/**
 * 清空指定选择器的值。如：clearValue('#deptid,#deptname')
 * @param {} sel
 * @returns {}
 */
function clearValue(sel) {
    $(sel).val("");
}

/*/** 通用选框结束*/


/*****使用jqgrid页面公用代码开始*****/

/**
 * jqgrid扩展方法，以数组方式返回勾选中的所有行的ID，
 * @returns {}
 */
$.fn.JQselectIds = function() {
    var $grid = $(this);
    var ids_ar = $grid.jqGrid("getGridParam", "selarrrow");
    return ids_ar;
}
/**
 * JQgrid 载入数据后，美化checkbox样式
 * @returns {}
 */
$.fn.JQchkCss = function () {
    var grid = $(this);                             //这个this 表示JQgrid 的数据table (数据行部分)，不包括TITLE行
    var w = "#gbox_" + $(this).attr("id");          //上塑到整个jqgrid的容器
    $(w).find("input[type=checkbox]").each(function() {
        if (!$(this).hasClass("ace")) {         //这个this表示一个checkbox,checkbox 必须去除cbox样式 ，否则无法勾选--fix bug
            $(this).removeClass("cbox").addClass("ace").wrap("<label></label>").parent().addClass("pos-rel").append('<span class="lbl"></span>');
        }
    });
    $(this).on("click","span.lbl",function(event) {
        event.stopPropagation();
        event.stopImmediatePropagation();
        var id = $(this).parent().parent().parent().attr("id");
        grid.setSelection(id);  //选中此行
        $(this).siblings("input").prop("checked", function(index, currentvalue) { return !currentvalue });
    });
}
/**rowid给定行ID，和列名colname， 取得列值 
*/
$.fn.JQgetCol = function(rowid,colname) {
    var rowData = $(gridSelector).jqGrid("getRowData",rowid);//根据id获得本行的所有数据
    var col= rowData[colname]; //获得指定属性的值 
    return col;
}
/**
 * 根据id_ar,取得行对象，以数组形式返回，id_ar是数组
 */
$.fn.JQgetRows = function(id_ar) {
    var rows=[];
    $.each(id_ar,function(k,v){
     var RowData = $(gridSelector).jqGrid("getRowData",v);
         rows.push(RowData);
    });
    return rows;
}

//公用JQGRID更新分页图标函数
function updatePagerIcons(pagerID) {
    var replacement =
    {
        'ui-icon-seek-first': 'ace-icon fa fa-angle-double-left bigger-140',
        'ui-icon-seek-prev': 'ace-icon fa fa-angle-left bigger-140',
        'ui-icon-seek-next': 'ace-icon fa fa-angle-right bigger-140',
        'ui-icon-seek-end': 'ace-icon fa fa-angle-double-right bigger-140'
    };
    $(pagerID + ' .ui-pg-table:not(.navtable) > tbody > tr > .ui-pg-button > .ui-icon')
        .each(function() {
            var icon = $(this);
            var $class = $.trim(icon.attr('class').replace('ui-icon', ''));
            if ($class in replacement) icon.attr('class', 'ui-icon ' + replacement[$class]);
        });
}


/*****使用jqgrid页面公用代码******/


/***********TreeTable相关操作开始**************/
/**
 * 获取TreeTable中勾选中的记录ID
 * @returns {} 数组 []
 */
 $.fn.TTselectIds = function() {
    var ids_ar = [];
    $(this)
    .find("tr input.row:checked")
    .each(function() {
        ids_ar.push($(this).val());
    });
    return ids_ar;
}

/**
*恢复TreeTable 上次展开的节点
*/
$.fn.TTrestoreOpen = function() {
    var id=$(this).attr('id');
    var ids=tools.getCookie('TreeTable_opens_'+id);
        ar=ids.split(","); //字符分割 
        for(var i=0;i<ar.length;i++) {
            if(ar[i]){
                try{
            $(this).treetable("expandNode", ar[i],true ); //后面加true不触发expand事件
        }catch(e){}
        }
    }
}
/**
*存储TreeTable 当前展开的节点
*/
$.fn.TTstoreOpen = function() {
    var id=$(this).attr('id');
    var ids=[];
    $(this).find("tr.expanded").each(function(i)
    {
        ids.push($(this).data('tt-id'));
    })
    tools.setCookie('TreeTable_opens_'+id,ids.join(','));
}

/**
*清空TreeTable 当前展开的节点，全部折叠时调用
*/
$.fn.TTclearOpen= function() {
 var id=$(this).attr('id');
 tools.removeCookie('TreeTable_opens_'+id);
}
/**
* TreeTable 全选与否
*/
$.fn.TTcheckAll= function(checked) {
 var id=$(this).attr('id');
 $(this).find("input[type='checkbox'].row").prop("checked", checked);
}

/***********TreeTable相关操作结束**************/  


/***********DataTable相关操作开始**************/  

/**
 * DataTable扩展方法，以数组方式返回勾选中的所有行的ID，
 * @returns {}
 */
 $.fn.DTselectIds = function() {
    var ids_ar = [];
    $(this).find("tr input.row:checked").each(function(){
        ids_ar.push($(this).val());
    })
    return ids_ar;
}
/**
 * DataTable扩展方法，绑定checkbox动作，全选与单选
 * @constructor
 */
$.fn.DTcheckboxBind = function() {
    var DT=$(this);
    //每行前面的checkbox点击动作，选择当前行
    DT.on("click","td .pos-rel",function(event){
        event.stopPropagation();
        if(!$(this).find("input").prop("checked")){
            $(this).parent().parent().removeClass("selected");
        }
        else {
            $(this).parent().parent().addClass("selected").siblings().removeClass("selected");
        }
    });

    //点击高亮显示当前行
    DT.on("click", "tbody tr", function () {
        if($(this).hasClass('selected')) return false;
        DT.find(".selected").removeClass("selected");
        $(this).addClass("selected").find("input").prop("checked",true);
        $(this).siblings().find("input").prop("checked",false)

    });
 
    //全选
    //checkbox列的title中必须有checkbox并且有chkAll样式
    //dataTable启用表头悬浮时，会动态生成两个表头对象，并且需要从DT的上层容器才能取得悬浮的表头对象
    //让悬浮的表头对象中的checkbox也具有全选的功能。
    var ID=DT.attr("id"); 
    if(!ID){alert("dataTable缺少ID");return ;}
    ID="#"+ID+"_wrapper";
    $("body").on('click',ID+' input.chkAll',function() {   
            var c=$(this)[0].checked;//全选
        DT.find("input[type='checkbox'].row").prop("checked", c);
        });
}

/***********DataTable相关操作结束**************/

/***********通用的初始化开始*************/
 $(function(){
		
    //在关键字框中回车，执行搜索
    $(document).on("keydown",'#keyword',function (event){
        try {
            var evt = window.event || arguments.callee.caller.arguments[0]; // 获取event对象
            if (evt.keyCode == 13) {
               $('#btnSearch').trigger('click');
            }
        } catch (e) { }
    });

    /** 将两个input 联合，codeid2_show的内容被清空时，自动清空codeid2的内容。
       通常用于datadlg的两个输入域，不个显示文本，一个显示ID，当文本清空ID域也自动 清空
    <input type="hidden" name="codeid2" id="codeid2" />
    <input type="text" name="codeid2_show" id="codeid2_show"  class="" ally='#codeid2' />
    */
    $(document).on("change",'input[ally]',function(){
        var e=$(this);
        window.setTimeout(function() { 
         var text=e.val();
        if(text=="")
        {
            var id=e.attr('ally')
            $(id).val('');
        }
        }, 100);
      
    })
    /*
    *自动绑定日期输入框，使用laydate插件,input必须有ID，通过data-datetype指定类型后面必须有个span.laydatebtn
    *<div class="input-group ">
    *    <input class="form-control  datepicker" id="date1" type="text"   data-datetype='date'>
    *   <span class="input-group-addon   laydatebtn"></span>
    *</div>
    */
    $('input.datepicker').each(function(){
        var id=$(this).attr("id");
        var dtype=$(this).data("datetype");
        $(this).parent().find('.laydatebtn').addClass('for_'+id);
        var ee='.for_'+id;
        laydate.render({
          elem: '#'+id, //指定日期输入元素
          type: dtype,  //控件选择类型 见laydate文档
          eventElem: ee,  //点击触发的元素  
          trigger: 'click' ,
		  done:function(value, date, endDate){
          	if($('#'+id).val()){
          		$('#'+id).focus().blur();   //模拟change过程  处理验证问题
          		
          	}
          }
        });
    });
 
    /**
    给form加上验证功能,form需要有validate样式 
    */
    if($("form.validate").length>0){
        $("form.validate").bindValidate();
        //对form中的hidden输入框增加验证成功的处理
        $("form input:hidden").on("change", function() {
            $(this).parent().find("em.error").remove();
        })
    }
    //form中的input 设置form-input样式,行高34px
    $("form input").addClass("form-input"); 

    /** 自动绑定typeahead 功能
        typeahead 预载入输入 调用下面代码
       <script src="__ADMIN__/ACE/assets/js/src/elements.typeahead.js"></script>
       要让某个input 可以typeahead,只要加上class=typeahead,并设置data-typeahead（单引号引起来的一个JSON对象）,即会自动绑定。
       class="typeahead "  data-typeahead='{"url":"/admin/demo/getTypeahead","keyfield":"id","valuefield":"codename","returnid":"codeid2"}' 
        其中url  后台提供数据的URL 通常是返回两个字段记录集的查询
          keyfield 数据集中代表ID的 字段名
          valuefield  数据库代表 显示内容的字段名 
          returnid 指定选择的ID填写到哪里， 通常是一个DOM的ID 如： "codeid2"    
    */
    $('.typeahead').each(function(){
        var cfg= $(this).data('typeahead');
        $(this).bs_typeahead({
            source: function(query, process) {
                return $.ajax({
                    type: "get",
                    url: cfg.url,
                    data: { keyword: query },
                    datatype: "json",
                    success: function(result) {
                        var resultList = result.map(function(item) {
                            var key=item[cfg.keyfield];
                            var value=item[cfg.valuefield];
                            var aItem = {key: key,value: value};
                            return JSON.stringify(aItem);
                        });
                        return process(resultList);
                    }
                });
            },
            highlighter: function(obj) {
                var item = JSON.parse(obj);
                var query = this.query.replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g, '\\$&'); //特殊字符串， 都加上\转义
                return item.value.replace(new RegExp('(' + query + ')', 'ig'), function($1, match) {
                    return '<strong>' + match + '</strong>'
                });
            },
            updater: function(obj) {
                var item = JSON.parse(obj);
                $("#"+cfg.returnid).val(item.key)
                return item.value;
            }
        });
    });
 });
 /***********通用的初始化结束 *************/