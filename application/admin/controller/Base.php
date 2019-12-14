<?php
namespace app\admin\controller;
use app\common\model\Rbac;
use think\Controller;
use think\Db;
use think\Validate;
use think\Exception;
use think\Log;
class Base extends Controller
{
    /**
     * 前置方法验证token
     * @var [type]
     */
    protected $beforeActionList = [
        // 新增修改保存时要求检验token 防止重复提交和攻击
        'checkToken'  =>  ['only'=>'update,save'],
    ];

    public function checkToken()
    {   
        $data=input('post.');
        $tokenname=array_keys($data)[0];//获得表单提交过来带有guid的token名
        $rule=[
         [$tokenname,'tokenGUID']//这里的tokenGUID是自定义的验证带有guid的验证规则
        ];
        $validate = new Validate($rule);
        $res=$validate->check($data);
        if (!$res) {
            throw new Exception("令牌无效，刷新重试。");
            return ;
        }
    }
    public function _initialize()
    {
        //检查是否有session,是否完成登录
        if(!session('?admin_id'))
        {
            $this->redirect(url('admin/login/index'));
        }
        //检查权限
        $auth=new Rbac;
        if(!$auth->check_auth())
        {
         $this->error('没有权限');
        }
        //默认选择中文语种
        if(!session('?langid'))
        {
            session('langid',1);
        }
        /******************tp5cms 系统信息缓存 start*********/
        if(!cache('?sysconfig'))
        {
            $sysconfig=Db::name('sysconfig')->where('langid',session('langid'))->select();
            foreach ($sysconfig as $k => $v) {
                $rst[$v['configname']]=$v['configvalue'];
            }
            cache('sysconfig',$rst);
        }
         /******************tp5cms 系统信息缓存 end*********/
    }


    /**
     * @param $distkey
     * @param $namefield
     * @param array|string $selectVal
     * @param string $defaultTxt
     * @param string $defaultVal
     * @param string $css
     * @return string  根据extra\dict.php文件中配置的字典项，生成相应的select的HTML
     */
    public function initSelectByDict($distkey,$namefield,$selectVal=[],$defaultTxt='',$defaultVal='',$css='')
    {
        if(empty($selectVal))
        {
            $selectVal=[];
        }

        $dist=config('dict');
        $data=$dist[$distkey];
        $html="<select id='$namefield' ".$css." name='$namefield'>";
        if($defaultTxt)
            $html.="<option value='".htmlspecialchars($defaultVal)."'>".htmlspecialchars($defaultTxt)."</option>";
        foreach ($data as $k => $v) {
            $k=htmlspecialchars($k);
            $v=htmlspecialchars($v);
            $html.="<option value='$k' ";
            if (is_array($selectVal)) {
                if(in_array($k,$selectVal))
                {
                    $html.=' selected="selected" ';
                }
            }else{
                 if($k==$selectVal)
                {
                    $html.=' selected="selected" ';
                }
            }
           
            $html.=">$v</option>";
        }
        $html.="</select>";
        return $html;
    }

    /**
     * @param $data
     * @param $namefield
     * @param $vfield
     * @param $txtfield
     * @param array|string $selectVal
     * @param string $defaultTxt
     * @param string $defaultVal
     * @param string $css
     * @return string 根据$data数组， 生成相应的select的HTML
     */
    public function initSelectByData($data,$namefield,$vfield,$txtfield,$selectVal=[],$defaultTxt='',$defaultVal='',$css='')
    {
        $html="<select id='$namefield' ".$css." name='$namefield'>";
        if($defaultTxt)
            $html.="<option value='".htmlspecialchars($defaultVal)."'>".htmlspecialchars($defaultTxt)."</option>";
        foreach ($data as $k => $v) {
            $html.="<option value='".htmlspecialchars($v[$vfield])."' ";
            if (is_array($selectVal)) {
                if(in_array($v[$vfield],$selectVal))
                {
                    $html.='selected="selected"';
                }
            }else{
                if($v[$vfield]==$selectVal)
                    {
                        $html.='selected="selected"';
                    }
            }
            
            $html.=">".htmlspecialchars($v[$txtfield])."</option>";
        }
        $html.="</select>";
        return $html;
    }

    /** 指定一个代码类型，（codetype表中的typekey）， 取得这类似代码的select
     * @param $type
     * @param $namefield
     * @param array|string $selectVal
     * @param string $defaultTxt
     * @param string $defaultVal
     * @param string $css
     * @return string
     */
    public function initSelectByType($type,$namefield,$selectVal=[],$defaultTxt='',$defaultVal='',$css='')
    {
        $sql="select c.id,c.codename from code c inner join codetype t on c.type_id=t.id where t.typekey='{$type}' order by c.sortno asc ";
        $data=Db::query($sql);

        $html="<select id='$namefield'  name='$namefield'  $css>"   ;
        if($defaultTxt)
            $html.="<option value='$defaultVal'>$defaultTxt</option>";
        foreach ($data as $k => $v) {
            $html.="<option value='{$v['id']}' ";
            if (is_array($selectVal)) {
                if(in_array($v['id'],$selectVal))
                {
                    $html.='selected="selected"';
                }
            }else{
                if($v['id']==$selectVal)
                {
                    $html.='selected="selected"';
                }
            }
            
            $html.=">{$v['codename']}</option>";
        }
        $html.="</select>";
        return $html;
    }
    public function initCheckboxByDict($distkey,$namefield,$selectVal=[],$css='')
    {
        $data=config('dict.'.$distkey);
        $html="";
        foreach ($data as $k => $v) {
            $k=htmlspecialchars($k);
            $v=htmlspecialchars($v);
            $html.="<span class='$css'><input type='checkbox' id='".$namefield.$k."'   name='$namefield' class='ace' value='$k' ";
            if (is_array($selectVal)) {
                if(in_array($k,$selectVal))
                {
                    $html.=' checked="checked" ';
                }
            }else{
                 if($k==$selectVal)
                {
                    $html.=' checked="checked" ';
                }
            }
           
           $html.="/><span class='lbl'><label for='".$namefield.$k."'>$v</label></span></span>";
        }
        return $html;
    }

    public function initCheckboxByData($data,$namefield,$vfield,$txtfield,$selectVal=[],$css='')
    {
        $html="";
        foreach ($data as $k => $v) {
            $html.="<span class='$css'><input type='checkbox' id='".$namefield.$k."' name='$namefield' class='ace' value='".htmlspecialchars($v[$vfield])."' ";
            if (is_array($selectVal)) {
                if(in_array($v[$vfield],$selectVal))
                {
                    $html.='checked="checked"';
                }
            }else{
                if($v[$vfield]==$selectVal)
                    {
                        $html.='checked="checked"';
                    }
            }
            
            $html.="/><span class='lbl'><label for='".$namefield.$k."'>".htmlspecialchars($v[$txtfield])."</label></span></span>";
        }
        return $html;
    }


    public function analy_advsearch($advsearch,$fieldlist)
    { 
        $list=trim($advsearch,chr(2));
        $list=explode(chr(2),$list);
        
        foreach ($list as $key => $value) {
            //分割后： fieldname | comptype | context| convalue| logic
            $cols=explode(chr(1),$value);
            $fieldname=$cols[0];
            $comptype=$cols[1];
            $context=$cols[2];
            $convalue=$cols[3];
            $logic=$cols[4];
            $rescols[$key]=$this->analy_searchcols($cols,$fieldlist);//根据当前页面控制器对高级搜索的定义，将$col格式化成sql标准
            //sql解析不通过则返回空
            if (!$rescols[$key]) {
                return '';
            }
        }

        foreach ($rescols as $k => $v) {
            //去除每个)前一行的logic
            if ($k>0) {
                if ($rescols[$k][0]==')') {
                    $rescols[$k-1][4]='';
                }
            }
            //删除最后一行的LOGIC
            if ($k==(count($rescols)-1)) {
                $rescols[$k][4]='';
            }
            
        }
        foreach ($rescols as $k => $v) {
            $w.=implode(' ',$v).' ';
        }
        if ($w) {
            $w=" and ($w)";
        }
        return $w;
    }

    private function analy_searchcols($cols,$fieldlist)
    {   
        $resArr= array();
        $fieldname=$cols[0];
        $comptype=$cols[1];
        $context=$cols[2];
        $convalue=$cols[3];
        $logic=$cols[4];
        //查询字段必须是控制器定义的字段
        $fields=array_keys($fieldlist);
        if (!in_array($fieldname,$fields)&&$fieldname!='('&&$fieldname!=')') {      
            return false;      
        }
        //查询条件必须是规定的比较条件
        $comptypeArr=['=','<=','>=','!=','>','<','in','notin','null','notnull','%like','%like%','like%',''];
        if (!in_array($comptype,$comptypeArr)) {
            return false;
        }
        //由于app目录下的config配置了'default_filter' => 'trim,htmlspecialchars',这里进行html实体解译
        $comptype=htmlspecialchars_decode($comptype);
        $logic=$logic=='and'?'and':'or';
        if ($fieldname=='(') {
            $logic='';
            $comptype='';
            $convalue='';
        }
        if ($fieldname==')') {
            $comptype='';
            $convalue='';
        }
        //根据字段数据类型和逻辑条件，以mysql语句标准格式化
        switch ($comptype) {
            case 'null':
                $comptype='is null';
                $convalue='';
                break;
            case '%like':
            case '%like%':
            case 'like%':
              //处理包含like筛选 $comptype为%like、%like% 、like%，转为 like %$convalue、 like %convalue%、like convalue%
                 $convalue=str_replace('like',$convalue,$comptype);
                 $convalue="'$convalue'";
                 $comptype='like';
                break;
            case 'notnull':
                $comptype='is not null';
                $convalue='';
                break;
             case 'notin':
                $comptype='not in';
                //如果查询字段为字符串类型， 转化为如 ('1','2','3')格式
                //整型为 (1,2,3)
                 $convalue=trim($convalue,',');
                if ($fieldlist[$fieldname]['datatype']=='varchar') {
                   
                    $colvalue=explode(',',$convalue);
                    foreach ($colvalue as $k => $v) {
                        $colvalue[$k]="'$v'";
                    }
                    $convalue=implode(',',$colvalue);
                }
                $convalue="($convalue)";
                break;
            case 'in':
                $comptype='in';
                //如果查询字段为字符串类型， 转化为如 ('1','2','3')格式
                //整型为 (1,2,3)
                 $convalue=trim($convalue,',');
                if ($fieldlist[$fieldname]['datatype']=='varchar') {
                   
                    $colvalue=explode(',',$convalue);
                    foreach ($colvalue as $k => $v) {
                        $colvalue[$k]="'$v'";
                    }
                    $convalue=implode(',',$colvalue);
                }
                $convalue="($convalue)";
                break;
            case '=':
            case '<=':
            case '>=':
            case '!=':
            case '>':
            case '<':  
                    if ($comptype=='!=') {
                        $comptype='<>';
                    }
                    //根据字段设定的datatype类型格式化convalue
                    $datatype=$fieldlist[$fieldname]['datatype'];
                    switch ($datatype) {
                        case 'int':
                            $convalue=intval($convalue);
                            break;
                        case 'date':
                        case 'datetime':
                            $convalue="'".date('Y-m-d H:i:s',strtotime($convalue))."'";
                        break;
                        case 'varchar':
                            $convalue="'$convalue'";
                            break;
                        //小数处理
                        case 'decimal':
                            $convalue=floatval($convalue);
                            break;
                    }
                    break;
        }
        //sql语句不需要拼接textname
        $resArr[0]=$fieldname;
        $resArr[1]=$comptype;
        $resArr[3]=$convalue;
        $resArr[4]=$logic;
        return $resArr;    
    }
}
