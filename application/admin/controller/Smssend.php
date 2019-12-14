<?php
namespace app\admin\controller;
use think\Db;
use app\common\model\AuthRule;
use \think\Validate;
class Smssend extends Base
{
    /**
     * 显示资源列表
     */
    public function index()
    {

        return $this->fetch();
    }
    public function index_json()
    {
        $w='';
        $k=addslashes( input('keyword'));//防SQL注入
        if($k!='') {
            $w.=" and ( id like '%{$k}%' or  tono like '%{$k}%' or  content  like '%{$k}%'  or  backcontent  like '%{$k}%' or  user_id  like '%{$k}%')";
        }
        $psql=new  \PageSQL();
        $psql->select="SELECT id,tono,content,sendtime,backcontent,backtime,user_id ";
        $psql->from =" FROM smssend  ";
        $psql->where =' where 1=1 '.$w;
        $psql->keyIndex='user_id desc'; 
        $db=new \DbTools();
        $pager=new \PageJQ();
        $rows=$db->getDataJQ($psql,$pager);
        $json['rows']=$rows;
        $json['records']=$pager->records;
        $json['page']=$pager->page;
        $json['total']=$pager->total;
        return json($json);
    }
    /**
     * 显示创建资源表单页.
     */
    public function create()
    {
        $flag=$this->initSelectByDict('sendstate','flag');
        $this->assign('flag',$flag);
        return $this->fetch();
    }

    /**
     * 保存新建的资源
     */
    public function save()
    {
        $data=input('post.');
        //验证提交信息
        $rule = [
            ['tono' , 'number|length:11','手机号码必须为纯数字|手机号码长度必须为11位'],
            ['tono' , ['regex'=>'/^1(3[0-9]|4[57]|5[0-35-9]|8[0-9]|70)\\d{8}$/'],'非法手机号码'],
            
        ];
        $validate = new Validate($rule);
        if (!$validate->check($data)) {
            return json(['state'=>'error','message'=>$validate->getError()]);
        }
        $res=Db::name('smssend')->insert($data);
        clearTokenGUID();
        return json(['state'=>'success','message'=>'添加成功']);
        
    }

    /**
     * 显示指定的资源
     */
    public function read($id)
    {
        $data=Db::name('smssend')->where('id',$id)->find();
        $data['flag']=config('dict')['sendstate'][$data['flag']];
        $this->assign('data',$data);
        return $this->fetch();

    }

    /**
     * 显示编辑资源表单页.
     */
    public function edit($id)
    {
  
        $data=Db::name('smssend')->where('id',$id)->find();
        $flag=$this->initSelectByDict('sendstate','flag',$data['flag']);
        $this->assign('flag',$flag);
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 保存更新的资源
     */
    public function update($id)
    {
        $data=input('post.'); 
        $rule = [
            ['tono' , 'number|length:11','必须为数字|手机号码长度必须为11位'],
            ['tono' , ['regex'=>'/^1[3|5|7|8]\d{9}$/'],'非法手机号码'],  
        ];
        $validate = new Validate($rule);
        if (!$validate->check($data)) {
            return json(['state'=>'error','message'=>$validate->getError()]);
        }
        $res=Db::name('smssend')->update($data);
        clearTokenGUID();
        return json(['state'=>'success','message'=>'修改成功']);
       
    }

    /**
     * 删除指定资源
     */
    public function delete()
    {  
        Db::name('smssend')->where('id','in',input('ids'))->delete();
        return json(['state'=>'success','message'=>'删除成功']);
    }
    
}
