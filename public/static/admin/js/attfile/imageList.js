/*!
 * jQuery imagelist 1.1.0
 * Released under the MIT license
 * 本插件启用拖拽功能 依赖 Sortable.js 只要调用次js文件即可 初始化方法已写在插件内部
 */

(function($) {
	'use htmlict';
	var methods = {
		init: function(options) {   //初始化
            var defaults= {
			     		// 模式 edit ：编辑模式  read 查看模式
						mode: "read",
						// Use Prettify select library
						inputId:"#idlists",  //ID列表输入框元素
						// 数据源地址
						listUrl: "/admin/Attfile/getList",
						deleteUrl: "/admin/Attfile/delete",
						descUrl:"/admin/Attfile/updateDesc",   //title content 更新提交地址
						titleUrl:"/admin/Attfile/updateTitle",   //title content 更新提交地址
						rotateUrl:"/admin/Attfile/rotateImg",
						updateOrderUrl: "/admin/Attfile/updateOrder",//删除附件提交到的地址
						isShowTitle:true,
						ImgWidth:120,
						ImgHeight:120
					}
			var html = "";
			var myid=this.attr('id');
			var element = this;
			var options = $.extend(true,{}, defaults, options);
			this.data("options",options);
			var listUrl = options.listUrl;
			var mode = options.mode;
			var inputId=options.inputId;
			var deleteUrl = options.deleteUrl;
			var isShowTitle=options.isShowTitle;
			html += '<div class="prev fa fa-angle-left"></div>';
			html += '<div class="wrap">';
			html += '<ul class="filelist sorttable"></ul>';
			html += '</div>';
			html += '<div class="next  fa fa-angle-right"></div>';
			if(isShowTitle)
			{
			html += ' <div class="clearfix"></div><div class="hr-12"></div><div class="col-md-6 form-group">';
			html += '<label class="col-sm-2 control-label no-padding-right" style="min-width: 70px;">图片标题</label>';
			html += '<div class="col-sm-10"><input class="col-xs-12 title" id="'+myid+'_title" type="text"></div></div>'
			html += '<div class="col-md-6 form-group">';
			html += '<label class="col-sm-2 control-label no-padding-right" style="min-width: 70px;">图片说明</label>';
			html += '<div class="col-sm-10">';
			html += ' <textarea   class="col-xs-12 desc" id="'+myid+'_desc" rows="2" style="height:34px;"></textarea>';
			html += '</div></div>';	
			}
			 
			this.append(html);
			if(mode=='read')
			{
				element.find(".title,.desc").attr("readonly", "readonly");
	         }
			methods._setWrapWidth.call(this);
			$(window).resize(function(){
				methods._setWrapWidth.call(element);
			})
			var idlist = $(inputId).val();
			$.post(listUrl, {ids: idlist}, function(data) {
					methods._addToList.call(element, data );
				}, "json");

			element.on("click", ".delete", function(e) {    //点击删除移除元素并提交到后台删除更新隐藏域
				e.stopPropagation();
				var delobj=$(this); //删除按钮
				var id = $(this).closest("li").data("id");
				var token = $(this).closest("li").data("token");
				$.post(deleteUrl,{id:id,token:token},function(rst){
					delobj.closest("li").remove();
					element.find("input.title").val("");
					element.find("textarea.desc").val("");
					methods._updateInputId.call(element);
				});
				
			})
			element.on("click", ".zomm", function() {    //点击放大
				$(this).parent().siblings("p.imgWrap").find(".cboxElement").click();
			});
			element.on("click", ".rotateRight", function() {    //右旋转
				methods._rotateRight.call(element,this); // 这里的this是右转按钮  ， element是插件
			});
			element.on("click", ".rotateLeft", function() {     //左旋转
				methods._rotateLeft.call(element,this); // 这里的this是右转按钮  ， element是插件
			});
			element.on("click", ".prev", function() {    //左箭头
				methods._next.call(element)
			});
			element.on("click", ".next", function() {     //右箭头
				methods._prev.call(element)
			});

			element.on("click", ".filelist li", function() {     //点击元素后增加选中并设置输入框的值
				$(this).addClass("filelist-select").siblings().removeClass("filelist-select")
				element.find("input.title").val($(this).data("title"));
				element.find("textarea.desc").val($(this).data("desc"));
			})
			element.on("mouseenter", ".wrap", function(e) {
				e.preventDefault();
				return false;
			})

			methods._updateTitle.call(element);
			methods._updateDesc.call(element);
			return options;
		},
		
		_rotate: function(btn,deg) {    //旋转方法
			var img = $(btn).closest("li").find("img");
			var rotate =img.data("rotate");
			var animateTo = rotate+ deg * 90; //deg 为1 则旋转度数为90 即为右旋转 deg为-1 则旋转度数为-90  即左旋转
			var rotate =img.data("rotate",animateTo);
			img.rotate({
				duration:300,
				angle: rotate, //初始度数
				animateTo: animateTo, //旋转到多少度
				//	    callback: rotation,
				easing: function(x, t, b, c, d) { // t: current time, b: begInnIng value, c: change In value, d: duration
					return c * (t / d) + b;
				}
			});
		},
		_rotateRight: function(btn) {    //右旋转方法
			var id=$(btn).closest("li").data("id");
			var rotateUrl=this.data("options").rotateUrl;
			methods._rotate.call(this,btn, 1);
            $.post(rotateUrl,{id:id,rotate:'right'},function(result){
            	if(result)
            	{
            		var img = $(btn).closest("li").find("img");
            		img.parent().attr('href',result.data.fileurl);
            	}

            });
		},
		_rotateLeft: function(btn) {   //左旋转方法
			var id=$(btn).closest("li").data("id");
			var rotateUrl=this.data("options").rotateUrl;
			methods._rotate.call(this,btn, -1);
			$.post(rotateUrl,{id:id,rotate:'left'},function(result){
               if(result)
            	{
            		var img = $(btn).closest("li").find("img");
            		img.parent().attr('href',result.data.fileurl);
            	}
			});
		},
		_updateStyle: function() {    //更新遮罩层的高度
			this.find(".opacity").css("height", this.data("options").ImgHeight)
		},
		_updateTitle: function() {    //更新title方法
			var element = this;
			var url=this.data("options").titleUrl;
			this.find("input.title").change(function() {
				var t=$(this).val();
				var id=element.find(".filelist-select").data("id");
				if(id)
				{
					$.post(url,{id:id,title:t},function(){
	                   element.find(".filelist-select").data("title",t);
					});
		        }
			})
		},
		_setWrapWidth:function(){   //设置容器宽度
			this.find(".wrap").css({width:this.width()-42})
		},
		
		_updateDesc: function() {   //更新content方法
			var element = this;
			var url=this.data("options").descUrl;
			this.find("textarea.desc").change(function() {
				var t=$(this).val();
				var id=element.find(".filelist-select").data("id");
				if(id)
				{
					$.post(url,{id:id,desc:t},function(){
						element.find(".filelist-select").data("desc",t);
				    });
		        }
			})
		},
		_updatePrevNextStyle: function() {   //更新左右箭头位置
			var tops = (this.find("p.imgWrap").height() - 51) / 2;
			tops = tops > 0 ? tops : 50
			this.find(".prev,.next").css({
				top: tops
			})
		},
		_updateInputId: function() {    //更新隐藏域的值
			var ids = [];
			this.find("ul.filelist li").each(function() {
				var id = $(this).data("id");
				ids.push(id);
			})
			
			$(this.data("options").inputId).val(ids.join());
			//更新排序
            var upurl=this.data("options").updateOrderUrl;
             $.post(upurl,{ids:ids.join()},function(rst){});
			  if(ids.length>0){   //如果隐藏域值不为空  调用change方法  处理隐藏域验证问题
             	
             	$(this.data("options").inputId).change();
             }
		},
		_addToList: function(data ) {   //插入方法
			var element = this;
			var myid=this.attr('id');
			var width = this.data("options").ImgWidth;
			var height=this.data("options").ImgHeight;
			var mode = this.data("options").mode;
			var optionbtn = "";
			if(mode == "edit") {
				optionbtn = '<span class="delete fa fa-trash-o  fa-fw" title="点击删除"></span><span class="zomm fa fa-search-plus fa-fw" title="点击放大"></span><span class="rotateRight fa fa-rotate-right  fa-fw" title="右旋转90度"></span><span class="rotateLeft fa fa-rotate-left  fa-fw" title="左旋转90度"></span>'
			} else {
				optionbtn = '<span class="zomm fa fa-arrows-alt"></span>';
			}

			for(var i = 0; i <  data.length; i++) {
				var html = "";
				var title=data[i].atttitle ;
				html += '<li data-id="' + data[i].id + '" data-desc="' + data[i].attdesc + '" data-title="' + data[i].atttitle+ '" data-token='+data[i].token+' style="">';
				html += '<p class="imgWrap">';
				html += '<a onclick="return false" href="' + data[i].fileurl + '" title="' + data[i].atttitle + '" data-rel="colorbox" class="cboxElement cboxElement_'+myid+'">';
				html += '<img data-rotate="0" src="' + data[i].fileurl + '" class="img-responsive" style="width:'+width+'px;height:'+height+'px"></a></p>';
				html += '<div class="file-panel">';

				html += optionbtn;
				html += '</div><div class="opacity"></div>';
				html += '</li>';
				this.find(".filelist").append(html);

			}
			if(mode == "edit") { //只有编辑模式下才能触发和执行的操作
	             $(this).find( ".sorttable" ).sortable({
	             	update:function( event, ui){
	             		methods._updateInputId.call(element);
	             	}
	             });

			}

			element.find(".cboxElement_"+myid).colorbox({	rel: "cboxElement_"+myid});
			methods._updateStyle.call(this);
		},
		_isEnd: function() {  //是否已经到头
			var left = this.find(".wrap").scrollLeft();
			var mywidth = this.find(".wrap").width();
			var li = this.find("ul li");
			var liWidth = li.width() * li.size();
			if(left > liWidth - mywidth+50) {
				return 1
			}
		},
		_prev: function() {    //左箭头方法
			if(methods._isEnd.call(this) == 1) {
				return
			}
			var left = this.find(".wrap").scrollLeft();
			left += this.data("options").ImgWidth+4;
			this.find(".wrap").animate({
				scrollLeft: left
			}, 500)
		},
		_next: function() {   //右箭头方法
			var left = this.find(".wrap").scrollLeft();
			left -=this.data("options").ImgWidth+4;
			this.find(".wrap").animate({
				scrollLeft: left
			}, 500)
		},
		update: function(data ) {   //更新方法
			methods._addToList.call(this, data );
			methods._updateInputId.call(this);
		},
		append: function(data ) {    //追加方法
			var size=data.length+this.find("li").length;
			var width=this.find("li").width()||this.data("options").ImgWidth;
			var left = this.find(".wrap").scrollLeft();
			left+=size*width;
			this.find(".wrap").animate({
				scrollLeft: left
			}, 500)
			methods._addToList.call(this, data );
			methods._updateInputId.call(this);
		}

	}
	$.fn.imageList = function(method) {
		var element = $(this);
		element.addClass("sw-imglist"); //添加插件样式
		if(!element[0]) return element; // stop here if the form does not exist

		if(typeof(method) == 'string' && method.charAt(0) != '_' && methods[method]) {
			return methods[method].apply(element, Array.prototype.slice.call(arguments, 1));

		} else if(typeof method == 'object' || !method) {
			// default conhtmluctor with or without arguments
			methods.init.apply(element, arguments);

		} else {
			$.error('Method: ' + method + ' does not exist in jQuery.imglist');
		}
	};

})(jQuery)