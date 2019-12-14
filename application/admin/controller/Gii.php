<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\common\model\AuthRule;
class Gii extends Controller
{
	public static $tableinfos;
	public static $tables;
	public $templates=[
						'controller'=>"./giitmp/controller.html",
						'index'=>"./giitmp/index.html",
						'create'=>"./giitmp/create.html",
						'edit'=>"./giitmp/edit.html",
						'read'=>"./giitmp/read.html",
						];
	public function index()
	{
		$tables=Db::query('show tables');
		$this->assign('tables',$tables);
		return $this->fetch();
	}
	public function create($table,$controller,$module)
	{
		return $this->fetch(
			'tableinfo',
			['table'=>strtolower($table),'controller'=>strtolower($controller),'module'=>strtolower($module)]
		);
	}
	public function tableinfo($table)
	{
		return view();
	}
	public function info_json($table)
	{
		$rows=Db::query('show full columns from '.$table);
		if (!$rows) {
			throw new Exception("不存在数据表".$table);
			
		}
		foreach ($rows as $k => &$v) {
			if ($v['field']=='id') {
				//unset会删除数组索引，导致json之后的格式是json对象格式,jqgrid获取不到数据
				array_splice($rows,$k,1);
			}
			strpos($v['type'],'(')&&$v['size']=substr($v['type'],strpos($v['type'],'(')+1,strpos($v['type'],')')-strpos($v['type'],'(')-1);
			strpos($v['type'],'(')&&$v['type']=substr($v['type'],0,strpos($v['type'],'('));
			
		}
        $json['rows']=$rows;
        return json($json);
	}
	public function giiIndexPage($postData)
	{
		self::$tableinfos=$postData;
		$index_content=$this->index_content();
		return json(['state'=>'success','content'=>$index_content,'message'=>'代码已生成']);
	}
	public function giiCreatePage($postData)
	{
		self::$tableinfos=$postData;
		$create_content=$this->create_content();
		return json(['state'=>'success','content'=>$create_content,'message'=>'代码已生成']);
	}
	public function giiEditPage($postData)
	{
		self::$tableinfos=$postData;
		$edit_content=$this->edit_content();
		return json(['state'=>'success','content'=>$edit_content,'message'=>'代码已生成']);
	}
	public function giiReadPage($postData)
	{
		self::$tableinfos=$postData;

		$read_content=$this->read_content();
		return json(['state'=>'success','content'=>$read_content,'message'=>'代码已生成']);
	}

	public function giiControlPage($postData)
	{
		self::$tableinfos=$postData;
		$control_content=$this->control_content();
		return json(['state'=>'success','content'=>$control_content,'message'=>'代码已生成']);
	}

	public function control_content()
	{
		$tableinfos=self::$tableinfos;
		$fields='';
		foreach ($tableinfos['data'] as $k => $v) {
			$fields[]=$v['field'];
		}
		$fields=implode(',',$fields);
		$table=$tableinfos['table'];
		$content=file_get_contents($this->templates['controller']);
		$likecontent=$this->getLikeContent();
		$create='';
		$edit='';
		foreach ($tableinfos['data'] as $k => $v) {
			if ($v['field']!='id') {
				$pageTips=$this->pageTips(strtolower($v['input_type']),$v['field']);
				if ($pageTips['create']) {
					$create.=$pageTips['create'];
				}
				if ($pageTips['edit']) {
					$edit.=$pageTips['edit'];
				}
			}
		}
		$controller=$tableinfos['controller']?$tableinfos['controller']:$table;
		$content=str_replace("{module}",$tableinfos['module'], $content);
		$content=str_replace("{controller}", ucfirst($controller), $content);
		$content=str_replace("{table}", $table, $content);
		$content=str_replace("{fields}", $fields, $content);
		$content=str_replace("{create}", $create, $content);
		$content=str_replace("{edit}", $edit, $content);
		$content=str_replace("{like}", $likecontent, $content);

		return $content;
	}
	public function pageTips($inputtype,$field)
	{	
		$tipsarr=array();
		switch ($inputtype) {
					case 'select':
						$tipsarr['create']=<<<EOF
						//todo 根据实际情况填写变量
						\$data='';
						
						\$vfield='';
						\$txtfield='';
						//请自行完成initSelectByData 参数填写
						\$select_{$v['field']}=\$this->initSelectByData(\$data,'$field',\$vfield,\$txtfield,\$selectVal=[],\$defaultTxt='',\$defaultVal='',\$css='');
						\$this->assign('select_{$v['field']}',\$select_{$v['field']});
EOF;
						$tipsarr['edit']=<<<EOF
						//todo 根据实际情况填写变量
						\$data='';
						
						\$vfield='';
						\$txtfield='';
						//请自行完成initSelectByData 参数填写
						\$select_{$v['field']}=\$this->initSelectByData(\$data,'$field',\$vfield,\$txtfield,\$selectVal=[],\$defaultTxt='',\$defaultVal='',\$css='');
						\$this->assign('select_{$v['field']}',\$select_{$v['field']});
EOF;
						break;
					
					case 'checkbox':
						$tipsarr['create']=<<<EOF
						//todo 根据实际情况填写变量
						\$data='';
						
						\$vfield='';
						\$txtfield='';
						//请自行完成initCheckboxByData 参数填写以及数据填充
						\$checkbox_{$v['field']}=\$this->initCheckboxByData(\$data,'$field',\$vfield,\$txtfield,\$selectVal=[],\$defaultTxt='',\$defaultVal='',\$css='');
						\$this->assign('checkbox_{$v['field']}',\$checkbox_{$v['field']});
EOF;
						$tipsarr['edit']=<<<EOF
						//todo 根据实际情况填写变量
						\$data='';
						
						\$vfield='';
						\$txtfield='';
						//请自行完成initCheckboxByData 参数填写以及数据填充
						\$checkbox_{$v['field']}=\$this->initCheckboxByData(\$data,'$field',\$vfield,\$txtfield,\$selectVal=[],\$defaultTxt='',\$defaultVal='',\$css='');
						\$this->assign('checkbox_{$v['field']}',\$checkbox_{$v['field']});
EOF;
						break;
				}
		return $tipsarr;
	}
	public function index_content()
	{
		$tableinfos=self::$tableinfos;
		$content=file_get_contents($this->templates['index']);
		$colmodel_content=$this->getColmodelContent();
		$content=str_replace("{colmodelcontent}",$colmodel_content,$content);
		$content=str_replace("{controller}", $tableinfos['controller'], $content);
		$content=str_replace("{module}", $tableinfos['module'], $content);
		$content=str_replace("{table}",$table,$content);
		return $content;
	}
	public function getColmodelContent()
	{
		$tableinfos=self::$tableinfos;
		$colmodel_content='';
		foreach ($tableinfos['data'] as $k => $v) 
		{
			$is_sortable=$v['is_sortable']?'true':'false';
			if($v['field']!='id')
			{
				if ($k==1) {
					$colmodel_content.=<<<EOF
					{label: '{$v['label_title']}', name: '{$v['field']}', index: '{$v['field']}',  sortable: {$is_sortable},
					formatter:function (value,Obj) {
                            var id=Obj.rowId;
                            return '<a href="javascript:" class="read" onclick="read('+id+')" >'+value+'</a>';}},
EOF;
				}else{
					
					$colmodel_content.="{label: '".$v['label_title']."', name: '".$v['field']."', index: '".$v['field']."',  sortable: {$is_sortable}},"."\n\t";
				}
				

			}

		}
		return $colmodel_content;
	}
	public function getLikeContent()
	{
		$tableinfos=self::$tableinfos;
		$like=' ';
		foreach ($tableinfos['data'] as $k => $v) {
			if ($v['field']!='id'&&$v['is_search']==1) {
				$like.=$v['field']." like '%{\$k}%' or ";
			}
			
		}
		if (!empty($like)) {
			$like=' and ( '.$like.')';
		}
		$like=substr($like, 0,strripos($like, 'or'));
		return $like;
	}
	public function create_content()
	{
		
		$createhtml=$this->createHtmlContent();
		$content=file_get_contents($this->templates['create']);
		$content=str_replace("{createhtml}",$createhtml,$content);
		return $content;
	}

	public function createHtmlContent()
	{
		$tableinfos=self::$tableinfos;
		$createhtml='';
		foreach ($tableinfos['data'] as $key => $v) {
			if ($v['field']!='id') {
				$createhtml[]=$this->getCreateHtml($tableinfos['data'][$key],$tableinfos['listtype']);
			}
		}
		
		$createhtml=$this->formattingHtml($createhtml,$tableinfos['listtype']);
		return $createhtml;
	}
	public function edit_content()
	{
		$edithtml=$this->editHtmlContent();
		$content=file_get_contents($this->templates['edit']);
		$content=str_replace("{edithtml}",$edithtml,$content);
		return $content;
	}
	public function editHtmlContent()
	{
		$tableinfos=self::$tableinfos;
		$edithtml='';
		foreach ($tableinfos['data'] as $key => $v) {
			if ($v['field']!='id') {
				$edithtml[]=$this->getEditHtml($tableinfos['data'][$key],$tableinfos['listtype']);
			}
		}
		
		$edithtml=$this->formattingHtml($edithtml,$tableinfos['listtype']);
		return $edithtml;
	}
	public function read_content()
	{
		$readhtml='';
		$tableinfos=self::$tableinfos;
		foreach ($tableinfos['data'] as $key => $v) {
			if ($v['field']!='id') {
				$readhtml[]=$this->getReadHtml($tableinfos['data'][$key],$tableinfos['listtype']);
			}
		}
		$readhtml=$this->formattingHtml($readhtml,$tableinfos['listtype']);
		$content=file_get_contents($this->templates['read']);
		$content=str_replace("{readhtml}",$readhtml,$content);
		return $content;
	}
	public function getReadHtml($fieldinfos,$listtype)
	{
		if ($listtype) {
			$titleWidth='15%';
			$fieldWidth='35%';
		}else{
			$titleWidth='20%';
			$fieldWidth='80%';
		}
		return <<<EOF
					<td class="title" width="{$titleWidth}" size='{$fieldinfos['label_width']}' nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
					</td>
					<td class="field" width="{$fieldWidth}" colspan="{colspan}">
						{\$data.{$fieldinfos['field']}}
					</td>		
EOF;
	}

	public function getCreateHtml($fieldinfos,$listtype)
	{
		$fieldinfos['is_required']=$fieldinfos['is_required']==1?'required':'';
		$required_tips=$fieldinfos['is_required']==1?'<span class="red">*</span>':'';
		if ($listtype) {
			$titleWidth='15%';
			$fieldWidth='35%';
		}else{
			$titleWidth='20%';
			$fieldWidth='80%';
		}
		$fieldWidth=$fieldinfos['label_width']!=''?$fieldinfos['label_width']:$fieldWidth;
		switch (strtolower($fieldinfos['input_type'])) {
			case 'date':
				return <<<EOF

					<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
					</td>
					<td class="field" width="{$fieldWidth}" colspan="{colspan}">
						<div class="pull-left input_wrap">
							<div class="input-group ">
								<input class="form-control  datepicker  {$fieldinfos['is_required']}" id="{$fieldinfos['field']}" name="{$fieldinfos['field']}" type="text" data-datetype='date' value="">
								<span class="input-group-addon   laydatebtn"></span>
							</div>
							<span class="helping">{$fieldinfos['label_helping']} </span>
						</div>
					</td>

EOF;
				break;
			case 'datetime':
				return <<<EOF

					<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
					</td>
					<td class="field" width="{$fieldWidth}" colspan="{colspan}">
						<div class="pull-left input_wrap">
							<div class="input-group ">
								<input class="form-control  datepicker  {$fieldinfos['is_required']}" id="{$fieldinfos['field']}" name="{$fieldinfos['field']}" type="text" data-datetype='datetime' >
								<span class="input-group-addon   laydatebtn"></span>
							</div>
							<span class="helping">{$fieldinfos['label_helping']} </span>
						</div>

					</td>

EOF;
				break;
			case '':
			case 'input':
				return <<<EOF

					<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
					</td>
					<td class="field" width="{$fieldWidth}" colspan="{colspan}">
						<div class="input-group pull-left">
							<input class="{$fieldinfos['is_required']}  " id="{$fieldinfos['field']}" name="{$fieldinfos['field']}"  type="text" value=""/>
	                    </div>
						<span class="helping">{$fieldinfos['label_helping']} </span>
						
					</td>

EOF;
				break;
			case 'radio':
				return <<<EOF

					<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
					</td>
					<td class="field" width="{$fieldWidth}" colspan="{colspan}">
						<div class="input-group pull-left">
							<label>
					            <input name="{$fieldinfos['field']}" type="radio" class="ace"   value="1">
					            <span class="lbl">是</span>
					        </label>
					        <label>
					            <input name="{$fieldinfos['field']}" type="radio" class="ace"  checked="checked" value="0">
					            <span class="lbl">否</span>
					        </label>
					    </div>
					    <span class="helping">{$fieldinfos['label_helping']} </span>
					</td>

EOF;
				break;
			case 'checkbox':
				return <<<EOF

					<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
					</td>
					<td class="field" width="{$fieldWidth}" colspan="{colspan}">
						<div class="input-group pull-left">
							{\$checkbox_{$fieldinfos['label_title']}}
					    </div>
					    <span class="helping">{$fieldinfos['label_helping']} </span>
					</td>

EOF;
				break;
			case 'select':
				return <<<EOF

					<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
					</td>
					<td class="field" width="{$fieldWidth}" colspan="{colspan}">
						<div class="input-group pull-left">
							{\$select_{$fieldinfos['field']}}
					    </div>
					    <span class="helping">{$fieldinfos['label_helping']} </span>
					</td>

EOF;
				break;

			case 'image':
				return <<<EOF

					<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
					</td>
					<td class="field" width="{$fieldWidth}" colspan="{colspan}">
						<div class="input-group pull-left">
							<div id="uploader-demo" class="uploader">
								<img src="" alt="" id="{$fieldinfos['field']}_img" width="100" height="100" class="cp">
								<div class="uploadInfo text-center">
									上传成功
								</div>
								<!--用来存放item-->
								<div id="{$fieldinfos['field']}_imgpicker">选择图片</div>
								<input type="hidden" name="{$fieldinfos['field']}" id="{$fieldinfos['field']}" value="" class="cp" />
							</div>
							<script src="__ADMIN__/js/powerFloat/jquery.powerFloat.min.js" type="text/javascript" charset="utf-8"></script>
							<script type="text/javascript" src="__ADMIN__/js/webuploader/webuploader.js"></script>
							<script>
								imagePicker('#{$fieldinfos['field']}_imgpicker', '#{$fieldinfos['field']}_img', '#{$fieldinfos['field']}');
								$(document).ready(function() {

									$(".cp").powerFloat({
										targetMode: "ajax",
										target: function() {
											return $(this).val();
										},
										position: "5-7"
									});
								});
							</script>
					    </div>
					    <span class="helping">{$fieldinfos['label_helping']} </span>
					</td>

EOF;
				break;
			case 'imagelist':
				return <<<EOF

				<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
				</td>
				<td class="field" width="{$fieldWidth}" colspan="{colspan}">
					<div class="input-group pull-left">
	                    <div class="col-sm-12">
	                        <button type="button" class="fa fa-cloud-upload fa-lg btn btn-primary" id="{$fieldinfos["field"]}_imageex" onclick="uploadImage('img',false,0 , 0, cb_{$fieldinfos["field"]});">上传</button>
	                    </div>

	                    <div class="{$fieldinfos["field"]} ">
	                        <input type="hidden" name="{$fieldinfos["field"]}" id="{$fieldinfos["field"]}" value=""/>
	                    </div>
	                </div>
                	<span class="helping">{$fieldinfos['label_helping']} </span>
                </td>
				<link href="__ADMIN__/js/attfile/css/attfilelist.css" rel="stylesheet" />
				<link href="__ADMIN__/ACE/components/jquery-colorbox/colorbox.min.css" rel="stylesheet" />
				<script src="__ADMIN__/js/jQueryRotate.js"></script>
				<script src="__ADMIN__/ACE/components/jquery-colorbox/jquery.colorbox.js"></script>
				<script src="__ADMIN__/js/attfile/imageList.js"></script>
				<script src="__ADMIN__/ACE/components/jquery-ui/jquery-ui.min.js" type="text/javascript" charset="utf-8"></script>
	            <script>    
	            function cb_{$fieldinfos["field"]}(data)
	            {
	                $('.{$fieldinfos["field"]}').imageList("append",data);
	            } 
	            $(function() {
	            		$('.{$fieldinfos["field"]}').imageList({
	                        "num": 8,
	                        "mode": 'edit',
	                        "inputId":"#{$fieldinfos["field"]}"
	                    })
	                    $(function(){setTimeout(function() {
								$(".cboxElement").colorbox({
									rel: 'group1'
								});
					
							}, 1000);

						})
	            });
	            </script>

EOF;
				break;
			case 'filelist':
				return <<<EOF

				<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
				</td>
				<td class="field" width="{$fieldWidth}" colspan="{colspan}">
                    <div class="input-group pull-left">
                        <button type="button" class="btn btn-xs  btn-myset" id="{$fieldinfos["field"]}_attachment" onclick="uploadFile('files',true, 0 ,0, '*',  cb_{$fieldinfos["field"]});">
                            <span class="ace-icon fa fa-search icon-on-right bigger-110"></span>上传
                        </button>
	                    <div  class="{$fieldinfos["field"]} col-sm-5">
	                         <input type="hidden" name="{$fieldinfos["field"]}" id="{$fieldinfos["field"]}" value=""  />
	                    </div>
	                    
	                </div>
                	<span class="helping">{$fieldinfos['label_helping']} </span>
                </td>
	            <script>    
	            function cb_{$fieldinfos["field"]}(data)
	            {
	                $('.{$fieldinfos["field"]}').attFileList("append",data);
	            } 
	            $(function() {
	                $(".{$fieldinfos["field"]}").attFileList({mode:'edit',inputId:"#{$fieldinfos["field"]}"});
	            });
	            </script>

EOF;
				break;
			case 'datadlg':
				return <<<EOF

				<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
				</td>
				<td class="field" width="{$fieldWidth}" colspan="{colspan}">
				<div class="input-group">
					<input type="hidden" name="{$fieldinfos["field"]}" id="{$fieldinfos["field"]}" />
					<input type="text" name="{$fieldinfos["field"]}_show" id="{$fieldinfos["field"]}_show" class="form-control search-query admin_sea" value="" placeholder="单选用户" />

					<span class="input-group-addon addon-btn no-left-border" id="{$fieldinfos["field"]}_selectBtn" onclick="dataDlgTable('selectrole', '{$fieldinfos["field"]}_selectBtn', public_DlgCallBack );" data-dlgreturn="{$fieldinfos["field"]}_show.val()=rolename;{$fieldinfos["field"]}.val()=id" data-dlgcallback="">
                        <i class="ace-icon fa fa-check"></i>
                    </span>
					<span class="input-group-addon addon-btn" onclick="clearValue('#{$fieldinfos["field"]},#{$fieldinfos["field"]}_show');">
                        <i class="ace-icon fa fa-close"></i>
                    </span>
				</div>
				<span class="helping">{$fieldinfos['label_helping']} </span>
				</td>

EOF;
				break;
			case 'picture':
				return <<<EOF

				<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
				</td>
				<td class="field" width="{$fieldWidth}" colspan="{colspan}">
					<div class="input-group">
						<div id="uploader-demo" class="uploader">
							<img src="" alt="" id="{$fieldinfos["field"]}_imgcro" width="100" height="100">
							<div class="uploadInfo text-center">
								上传成功
							</div>
							<!--用来存放item-->
							<div id="filePicker2">选择图片</div>
							<input type="" name="{$fieldinfos["field"]}" id="{$fieldinfos["field"]}" value="" class="cp" />

							<button type="button" class="btn btn-xs  btn-myset" id="btnupload3" onclick="imageCrop($('#{$fieldinfos["field"]}').val(),300,300,imageCropcb);">
							<span class="ace-icon fa fa-search icon-on-right bigger-110"></span> 裁剪
						    </button>

						</div>
					</div>
					<span class="helping">{$fieldinfos['label_helping']} </span>
				</td>
				<script type="text/javascript" src="__ADMIN__/js/webuploader/webuploader.js"></script>
					<script>
						imagePicker('#filePicker2', '#{$fieldinfos["field"]}_imgcro', '#{$fieldinfos["field"]}');

						function imageCropcb(data) {
							$('#{$fieldinfos["field"]}_imgcro').attr("src", '__ROOT__' + data.data.url);
							$('#{$fieldinfos["field"]}').val(data.data.url);
						}
					</script>

EOF;
				break;
			case 'editor':
				return <<<EOF

				<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
				</td>
				<td class="field" width="{$fieldWidth}" colspan="{colspan}">
					<textarea id="{$fieldinfos["field"]}_editor" name="{$fieldinfos["field"]}" placeholder="详细内容" type="text" /></textarea>
				</td>
				<script src="__ADMIN__/ueditor/ueditor.config.js"></script>
				<script src="__ADMIN__/ueditor/ueditor.all.min.js"></script>
				<script type="text/javascript">
						var ue = UE.getEditor('{$fieldinfos["field"]}_editor');
				</script>

EOF;
				break;

		}
	}
	public function getEditHtml($fieldinfos,$listtype)
	{
		$fieldinfos['is_required']=$fieldinfos['is_required']==1?'required':'';
		$required_tips=$fieldinfos['is_required']==1?'<span class="red">*</span>':'';
		if ($listtype) {
			$titleWidth='15%';
			$fieldWidth='35%';
		}else{
			$titleWidth='20%';
			$fieldWidth='80%';
		}
		switch (strtolower($fieldinfos['input_type'])) {
			case 'date':
				return <<<EOF

					<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
					</td>
					<td class="field" width="{$fieldWidth}" colspan="{colspan}">
						<div class="pull-left input_wrap">
							<div class="input-group ">
								<input class="form-control  datepicker size='{$fieldinfos['label_width']}'  {$fieldinfos['is_required']}" id="{$fieldinfos['field']}" name="{$fieldinfos['field']}" type="text" data-datetype='date' value="{\$data['{$fieldinfos['field']}']}">
								<span class="input-group-addon   laydatebtn"></span>
							</div>
							<span class="helping">{$fieldinfos['label_helping']} </span>
						</div>
					</td>

EOF;
				break;
			case 'datetime':
				return <<<EOF

					<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
					</td>
					<td class="field" width="{$fieldWidth}" colspan="{colspan}">
						<div class="pull-left input_wrap">
							<div class="input-group ">
								<input class="form-control  datepicker size='{$fieldinfos['label_width']}'  {$fieldinfos['is_required']}" id="{$fieldinfos['field']}" name="{$fieldinfos['field']}" type="text" data-datetype='datetime' value="{\$data['{$fieldinfos['field']}']}">
								<span class="input-group-addon   laydatebtn"></span>
							</div>
							<span class="helping">{$fieldinfos['label_helping']} </span>
						</div>

					</td>

EOF;
				break;
			case '':
			case 'input':
				return <<<EOF

					<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
					</td>
					<td class="field" width="{$fieldWidth}" colspan="{colspan}">
						<div class="input-group pull-left">
							<input class="{$fieldinfos['is_required']} size='{$fieldinfos['label_width']}'  " id="{$fieldinfos['field']}" name="{$fieldinfos['field']}"  type="text" value="{\$data['{$fieldinfos['field']}']}"/>
	                    </div>
						<span class="helping">{$fieldinfos['label_helping']} </span>
						
					</td>

EOF;
				break;
			case 'radio':
				return <<<EOF

					<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
					</td>
					<td class="field" width="{$fieldWidth}" colspan="{colspan}">
						<div class="input-group pull-left">
							<label>
		<input name="{$fieldinfos['field']}"  type="radio" class="ace" {eq name='\$data.{$fieldinfos['field']}' value='1'}checked="checked" {/eq} value="1">
					            <span class="lbl">是</span>
					        </label>
					        <label>
					            <input name="{$fieldinfos['field']}" type="radio" class="ace"  {eq name='\$data.{$fieldinfos['field']}' value='1'}checked="checked" {/eq} value="0">
					            <span class="lbl">否</span>
					        </label>
					    </div>
					    <span class="helping">{$fieldinfos['label_helping']} </span>
					</td>

EOF;
				break;
			case 'checkbox':
				return <<<EOF

					<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
					</td>
					<td class="field" width="{$fieldWidth}" colspan="{colspan}">
						<div class="input-group pull-left">
							{\$checkbox_{$fieldinfos['label_title']}}
					    </div>
					    <span class="helping">{$fieldinfos['label_helping']} </span>
					</td>

EOF;
				break;
			case 'select':
				return <<<EOF

					<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
					</td>
					<td class="field" width="{$fieldWidth}" colspan="{colspan}">
						<div class="input-group pull-left">
							{\$select_{$fieldinfos['field']}}
					    </div>
					    <span class="helping">{$fieldinfos['label_helping']} </span>
					</td>

EOF;
			case 'image':
				return <<<EOF

					<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
					</td>
					<td class="field" width="{$fieldWidth}" colspan="{colspan}">
						<div class="input-group pull-left">
							<div id="uploader-demo" class="uploader">
								<img src="{\$data['{$fieldinfos['field']}']}" alt="" id="{$fieldinfos['field']}_img" width="100" height="100" class="cp">
								<div class="uploadInfo text-center">
									上传成功
								</div>
								<!--用来存放item-->
								<div id="{$fieldinfos['field']}_imgpicker">选择图片</div>
								<input type="hidden" name="{$fieldinfos['field']}" id="{$fieldinfos['field']}" value="" class="cp" value="{\$data['{$fieldinfos['field']}']}"/>
							</div>
							<script src="__ADMIN__/js/powerFloat/jquery.powerFloat.min.js" type="text/javascript" charset="utf-8"></script>
							<script type="text/javascript" src="__ADMIN__/js/webuploader/webuploader.js"></script>
							<script>
								imagePicker('#{$fieldinfos['field']}_imgpicker', '#{$fieldinfos['field']}_img', '#{$fieldinfos['field']}');
								$(document).ready(function() {

									$(".cp").powerFloat({
										targetMode: "ajax",
										target: function() {
											return $(this).val();
										},
										position: "5-7"
									});
								});
							</script>
					    </div>
					    <span class="helping">{$fieldinfos['label_helping']} </span>
					</td>

EOF;
				break;
			case 'imagelist':
				return <<<EOF

				<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
				</td>
				<td class="field" width="{$fieldWidth}" colspan="{colspan}">
					<div class="input-group pull-left">
	                    <div class="col-sm-12">
	                        <button type="button" class="fa fa-cloud-upload fa-lg btn btn-primary" id="{$fieldinfos["field"]}_imageex" onclick="uploadImage('img',false,0 , 0,cb_{$fieldinfos["field"]});">上传</button>
	                    </div>

	                    <div class="{$fieldinfos["field"]} ">
	                        <input type="hidden" name="{$fieldinfos["field"]}" id="{$fieldinfos["field"]}" value="{\$data['{$fieldinfos['field']}']}"/>
	                    </div>
	                </div>
                	<span class="helping">{$fieldinfos['label_helping']} </span>
                </td>
				<link href="__ADMIN__/js/attfile/css/attfilelist.css" rel="stylesheet" />
				<link href="__ADMIN__/ACE/components/jquery-colorbox/colorbox.min.css" rel="stylesheet" />
				<script src="__ADMIN__/js/jQueryRotate.js"></script>
				<script src="__ADMIN__/js/Sortable-master/Sortable.js"></script>
				<script src="__ADMIN__/ACE/components/jquery-colorbox/jquery.colorbox.js"></script>
				<script src="__ADMIN__/js/attfile/imageList.js"></script>
	            <script>    
	            function cb_{$fieldinfos["field"]}(data)
	            {
	                $('.{$fieldinfos["field"]}').imageList("append",data);
	            } 
	            $(function() {
	            		$('.{$fieldinfos["field"]}').imageList({
	                        "num": 8,
	                        "mode": 'edit',
	                        "inputId":"#{$fieldinfos["field"]}"
	                    })
	                    $(function(){setTimeout(function() {
								$(".cboxElement").colorbox({
									rel: 'group1'
								});
					
							}, 1000);

						})
	            });
	            </script>

EOF;
				break;
			case 'filelist':
				return <<<EOF

				<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
				</td>
				<td class="field" width="{$fieldWidth}" colspan="{colspan}">
                    <div class="input-group pull-left">
                        <button type="button" class="btn btn-xs  btn-myset" id="{$fieldinfos["field"]}_attachment" onclick="uploadFile('files',true, 0 ,0, '*', '{$fieldinfos["field"]}_attachment',cb_{$fieldinfos["field"]});">
                            <span class="ace-icon fa fa-search icon-on-right bigger-110"></span>上传
                        </button>
	                    <div  class="{$fieldinfos["field"]} col-sm-5">
	                         <input type="hidden" name="{$fieldinfos["field"]}" id="{$fieldinfos["field"]}" value="{\$data['{$fieldinfos['field']}']}"  />
	                    </div>
	                    
	                </div>
                	<span class="helping">{$fieldinfos['label_helping']} </span>
                </td>
                <link href="__ADMIN__/js/attfile/css/attfilelist.css" rel="stylesheet" />
                <script src="__ADMIN__/js/attfile/attfilelist.js"></script>
	            <script>    
	            function cb_{$fieldinfos["field"]}(data)
	            {
	                $('.{$fieldinfos["field"]}').attFileList("append",data);
	            } 
	            $(function() {
	                $(".{$fieldinfos["field"]}").attFileList({mode:0,inputId:"#{$fieldinfos["field"]}"});
	            });
	            </script>

EOF;
				break;
			case 'datadlg':
				return <<<EOF

				<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
				</td>
				<td class="field" width="{$fieldWidth}" colspan="{colspan}">
				<div class="input-group">
					<input type="hidden" name="{$fieldinfos["field"]}" id="{$fieldinfos["field"]}" value="{\$data['{$fieldinfos['field']}']}"/>
					<input type="text" size='{$fieldinfos['label_width']}' name="{$fieldinfos["field"]}_show" id="{$fieldinfos["field"]}_show" class="form-control search-query admin_sea" value="" placeholder="单选用户" value="{\$data['{$fieldinfos['field']}']}"/>

					<span class="input-group-addon addon-btn no-left-border" id="{$fieldinfos["field"]}_selectBtn" onclick="dataDlgTable('selectrole', '{$fieldinfos["field"]}_selectBtn', public_DlgCallBack );" data-dlgreturn="{$fieldinfos["field"]}_show.val()=rolename;{$fieldinfos["field"]}.val()=id" data-dlgcallback="">
                        <i class="ace-icon fa fa-check"></i>
                    </span>
					<span class="input-group-addon addon-btn" onclick="clearValue('#{$fieldinfos["field"]},#{$fieldinfos["field"]}_show');">
                        <i class="ace-icon fa fa-close"></i>
                    </span>
				</div>
				<span class="helping">{$fieldinfos['label_helping']} </span>
				</td>

EOF;
				break;
			case 'picture':
				return <<<EOF

				<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
				</td>
				<td class="field" width="{$fieldWidth}" colspan="{colspan}">
					<div class="input-group">
						<div id="uploader-demo" class="uploader">
							<img src="{\$data['{$fieldinfos['field']}']}" alt="" id="{$fieldinfos["field"]}_imgcro" width="100" height="100">
							<div class="uploadInfo text-center">
								上传成功
							</div>
							<!--用来存放item-->
							<div id="filePicker2">选择图片</div>
							<input type="hidden" name="{$fieldinfos["field"]}" id="{$fieldinfos["field"]}"  class="cp" value="{\$data['{$fieldinfos['field']}']}" />

							<button type="button" class="btn btn-xs  btn-myset" id="btnupload3" onclick="imageCrop($('#{$fieldinfos["field"]}').val(),300,300,imageCropcb);">
							<span class="ace-icon fa fa-search icon-on-right bigger-110"></span> 裁剪
						    </button>

						</div>
					</div>
					<span class="helping">{$fieldinfos['label_helping']} </span>
				</td>
				<script type="text/javascript" src="__ADMIN__/js/webuploader/webuploader.js"></script>
					<script>
						imagePicker('#filePicker2', '#{$fieldinfos["field"]}_imgcro', '#{$fieldinfos["field"]}');

						function imageCropcb(data) {
							$('#{$fieldinfos["field"]}_imgcro').attr("src", '__ROOT__' + data.data.url);
							$('#{$fieldinfos["field"]}').val(data.data.url);
						}
					</script>

EOF;
				break;
			case 'editor':
				return <<<EOF

				<td class="title"	width="{$titleWidth}" nowrap="nowrap">{$required_tips}{$fieldinfos['label_title']}
				</td>
				<td class="field" width="{$fieldWidth}" colspan="{colspan}">
					<textarea id="{$fieldinfos["field"]}_editor" name="{$fieldinfos["field"]}"  placeholder="详细内容" type="text" />{\$data['{$fieldinfos['field']}']}</textarea>
				</td>
				<script src="__ADMIN__/ueditor/ueditor.config.js"></script>
				<script src="__ADMIN__/ueditor/ueditor.all.min.js"></script>
				<script type="text/javascript">
						var ue = UE.getEditor('{$fieldinfos["field"]}_editor');
				</script>
				
EOF;
				break;
		}
	}
	public function formattingHtml($arr,$listtype)
	{
		$html="<table cellspacing='1' width='100%' border='0' align='center' class='form-table'> \n";
		$counts=count($arr);
		// 双列
		if ($listtype) {
			foreach ($arr as $key => $value) {
                if ($counts%2==0) {
                	$value=str_replace('{colspan}',0,$value);
                    if ($key%2==0) {
                        $arr[$key]='<tr>'.$value;

                    } else{
                        $arr[$key]=$value.'</tr>';
                    }
                    
                }else{

                	if ($key==($counts-1)) {
                       $value=str_replace('{colspan}',3,$value);
                       $arr[$key]='<tr>'.$value.'</tr>'; 
                    }
                    $value=str_replace('{colspan}',0,$value);
                    if ($key%2==0) {
                        $arr[$key]='<tr>'.$value;
                    } else{
                        $arr[$key]=$value.'</tr>';
                    } 
                   
                }
			}
		}else{
			foreach ($arr as $key => $value) {
                $value=str_replace('{colspan}',0,$value);
                $arr[$key]='<tr>'.$value.'</tr>'; 
			}
		}
		$html.=implode("\n" ,$arr)."\n </table>";
		return $html;
	}

	public function mysubstr($value)
	{

		$pos1=strpos($value, ',');
		$pos2=strpos($value, '，');
		if ($pos1&&$pos2) {
			$pos=$pos1<$pos2?$pos1:$pos2;
			$value=substr($value,0,$pos);
			return $value;
		}
		$pos1 &&  $value=substr($value,0, $pos1);
		$pos2 &&  $value=substr($value,0, $pos2);
		return $value;
	}
	public function isexit($table)
	{
		$tables=Db::query('show tables');
		$arr='';
		foreach ($tables as $k => $v) {
			foreach ($v as $key => $value) {
				$arr[]=$value;
			}
		}
		if (in_array($table, $arr)) {
			return true;
		}else{
			return false;
		}
	}

	public function invalidNamespace($namespace)
	{
		$path=$this->formattingPath($namespace,$type='controller',$isauto=false);
		$ppath=$path;
		strpos($path,'/')&&$ppath=substr($path,0,strrpos($path,'/')+1);
		if ($ppath==APP_PATH) {
			return json(['state'=>'error','message'=>'不能缺少模块，不能直接建立在app目录下']);
		}
		if (!is_dir($ppath)) {
			$namespaceArr=explode('\\',$namespace);
			if (isset($namespaceArr[1])&&!empty($namespaceArr[1])) {
				$module=$namespaceArr[1];
				return json(['state'=>'error','message'=>"模块:'".$module."'不存在,请先在项目下建立该模块目录"]);
			}
			return json(['state'=>'error','message'=>"无效的命名空间"]);
			
		}
		return false;
	}
	public function formattingPath($namespace,$type='controller',$isauto=true)
	{
		$nameArr='';
		$path='';
		$nameArr=explode('\\', $namespace);
		if (is_array($nameArr)) {
			foreach ($nameArr as $k => $v) {
				if ($v==config('app_namespace')) {
					$v=APP_PATH;
				}
				if ($isauto) {
					if (strtolower($v)=='controller') {
					unset($v);
				}
				}
				
				$path.=$v.'/';
			}
			if ($isauto) {
				$path=str_replace('//','/',$path.'/'.strtolower($type).'/');
			}else{
				$path=str_replace('//','/',$path.'/');
			}
		}
		
		(strpos($path,'//'))&&$path=str_replace('//','/',$path);
		return $path;
	}
public function test($table,$namespace)
{
		$tableinfos=Db::query('show full columns from '.$table);
		//dd($tableinfos);
		self::$tableinfos=$tableinfos;
		$controllername=ucfirst($table);
		$namespace=input('namespace');
		if ($namespace) {
			if ($this->invalidNamespace($namespace)) {//根据命名空间检验是否有效路径
				return json(['state'=>'error','message'=>'无效的命名空间']);
			}
			else{

				$controller_path=$this->formattingPath($namespace);//根据命名空间生成controller路径
				$view_path=$this->formattingPath($namespace,'view');//根据命名空间生成view路径
				(!strpos($namespace,'controller'))&&$namespace=$namespace.'\\controller';//容错性处理
				(strpos($namespace,'\\\\'))&&$namespace=str_replace('\\\\','\\',$namespace);
			}

		}
		else{
			$namespace='app\admin\controller';//default
			$controller_path="../application/admin/controller/";//default
			$view_path="../application/admin/view/";//default
		}
		 //dd($controller_path);
		if(!is_file($controller_path.$controllername.".php"))
		{
			$index_content=$this->index_content($table);
			$control_content=$this->control_content($table,$namespace);
			$create_content=$this->create_content();
			$edit_content=$this->edit_content();
			$read_content=$this->read_content();
			@mkdir($controller_path,0755,true);
			file_put_contents($controller_path.$controllername.".php", $control_content);
			@mkdir($view_path.strtolower($controllername),0755, true);
			file_put_contents($view_path.strtolower($controllername)."/index.html",$index_content);
			file_put_contents($view_path.strtolower($controllername)."/create.html",$create_content);
			file_put_contents($view_path.strtolower($controllername)."/edit.html",$edit_content);
			file_put_contents($view_path.strtolower($controllername)."/read.html",$read_content);
		}else{
			return json(['state'=>'error','message'=>'控制器'.$controllername.'已存在 :'.dirname($controller_path)]);
		}
		return json(['state'=>'success','message'=>'新增成功']);
}
}