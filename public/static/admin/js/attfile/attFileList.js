/**
上传附件后显示附件列表插件。 根据ID清单显示附件列表。
*/

(function ($) {
    var methods = {
        init: function (options) {    //初始化
            var element = this;
			var  defaults={
                mode: 'edit', //edit 为编辑模式 read 为查看模式
                // 数据源地址
                listUrl: "/admin/Attfile/getList", // 获取附件列表的URL
                deleteUrl: "/admin/Attfile/delete",//删除附件提交到的地址
                updateOrderUrl: "/admin/Attfile/updateOrder",//删除附件提交到的地址
                inputId:'#test'
            }

            var options = $.extend(true, {},defaults, options);
            this.data("options",options);
            var mode = options.mode;
            if(mode=="edit"){
        	   element.sortable({
         	    update:function( event, ui){
         		     methods._updateInputId.call(element);
             	}
             	
             });
            }
            //列表删除动作
            var delurl =options.deleteUrl;
            this.on("click", ".filelist .delete", function (e) {
                e.stopPropagation();
                var row=$(this).parent();
                var id = row.data("id");
                var token = row.data("token");
                $.post(delurl,{id:id,token:token},function(rst){  
                    row.remove(); 
                    methods._updateInputId.call(element );
                 });
            })


            var idlist = $(options.inputId).val();
            var listUrl = options.listUrl;
            //根据附件IDS,取得附件列表 JSON，并展示到容器中。
            $.post(listUrl, {ids: idlist}, function (data) {
                methods._addToList.call(element, data );
            }, "json");
        },
        _updateInputId: function () {     //更新隐藏域的ID
            var id = [];
            this.find(".filelist").each(function () {
               id.push($(this).data("id") );
            });
            $(this.data("options").inputId).val(id.join());
            //更新排序
            var upurl=this.data("options").updateOrderUrl;
             $.post(upurl,{ids:id.join()},function(rst){});
        },
		
        _addToList: function (data) {     //插入 data (json数据)
            var mode = this.data("options").mode;
            var delbtn =mode == 'edit'?"delete fa fa-trash-o ": " hidden ";
            for (var i = 0; i < data.length; i++) {
                var str = '<div class="filelist" data-id="' + data[i].id + '" data-token="'+ data[i].token+'"><div class="pull-left"><a target="_blank" href="' + data[i].fileurl + '">' + data[i].oldname + '</a><span class="filesize">' + data[i].filesize + '</span></div><span class="' + delbtn + ' bigger-130 red pull-right"></span></div>'
                this.append(str)
            }

        },
        update: function (json) {   //更新方法
            this.find(".filelist").remove();
            methods._addToList.call(this, json);
            methods._updateInputId.call(this);
        } ,
        append: function (json) {    //追加方法
            methods._addToList.call(this, json );
            methods._updateInputId.call(this);
        }
         
    }

    $.fn.attFileList = function (method) {
        var element = $(this);
        element.addClass("attfilelist"); //添加插件样式
        
        if (!element[0]) return element; // stop here if the form does not exist
        if (typeof(method) == 'string' && method.charAt(0) != '_' && methods[method]) {
            return methods[method].apply(element, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method == 'object' || !method) {
            methods.init.apply(element, arguments);
        } else {
            $.error('Method: ' + method + ' does not exist in attfilelist');
        }
    };

})(jQuery);
