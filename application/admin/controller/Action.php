<?php
namespace app\admin\controller;
use think\Db;
use app\common\model\Rbac;
use think\Log;
class Action extends Base
{
    /**
     * 显示资源列表
     */
    public function index()
    {
        $w='';
        $k=addslashes( input('keyword'));//防SQL注入
        if($k)
        {
            $w=" where  ( actcode like '%{$k}%' or  actname like '%{$k}%' or  menutext  like '%{$k}%'   )";
        }    
        $sql = "select *   from action $w order by pid,sortno,id  ";
        $data = Db::query($sql);

         foreach ($data as &$r) {
            if($r['ismenu']==0)  //不是菜单 的，显示为红色
                { 
                    $r['font']='{"color":"red"}';
                }
            $r['target']='rightframe';
            $r["url"] =  "__ROOT__/admin/action/read?id={$r['id']}";
        }

        $this->assign('zNodes', json_encode($data, JSON_UNESCAPED_UNICODE)); //中文原样输出
        return $this->fetch();
    }

    /**
     * 显示创建资源表单页.
     */
    public function create()
    {
        return $this->fetch();
    }

    /**
     * 保存新建的资源
     */
    public function save()
    {
        $data = input('post.');

        Db::name('action')->insert($data);
        clearTokenGUID();
        return json(['state' => 'success', 'message' => '新增成功']);
    }

    /**
     * 显示编辑资源表单页.
     */
    public function edit($id)
    {
        $data = Db::name('action')->where('id', $id)->find();
        $pname = Db::name('action')->where('id', $data['pid'])->value('actname');
        $this->assign('data', $data);
        $this->assign('pname', $pname);

        return $this->fetch();
    }
    public function read($id)
    {
        $data = Db::name('action')->where('id', $id)->find();
        $pname = Db::name('action')->where('id', $data['pid'])->value('actname');
        $this->assign('data', $data);
        $this->assign('pname', $pname);

        return $this->fetch();
    }

    /**
     * 保存更新的资源
     */
    public function update($id)
    {
        $data = input('post.');
        $data['menutext'] = input('actname');
        $tool = new \DbTools;
        $result = $tool->getTreeSubId('action', $data['id']);
        $id = $data['id'];
        array_push($result, $id);
        if (in_array($data['pid'], $result)) {
            return json(['state' => 'error', 'message' => '父节点不能选择自己及子节点']);
        }
        Db::name('action')->where('id', $id)->update($data);
        clearTokenGUID();
        return json(['state' => 'success', 'message' => '修改成功']);
    }
    /**
     * 删除指定资源
     */
    public function delete()
    {
        $id = input('ids');
        $tool = new \DbTools;
        $result = $tool->getTreeSubId('action', $id);
        $arr = explode(',', $id);
        $ids = array_merge($arr, $result);
        if (Db::name('action')->where('id', 'in', $ids)->delete()) {
            return json(['state' => 'success', 'message' => '删除成功','data'=>$ids]);
        }
    }

    /**
     * 批量操作
     */
    public function batch()
    {
        $action = Db::name('action')->where('id',  input('id'))->find();
            $this->batchadd($action);
        return json(['state' => 'success', 'message' => '操作成功']);
    }
 

    public function assignrole()
    {
        $tools=new \DbTools;
        $actionpath=$tools->getTreePath('action',input('actionid'),'actname');
        $this->assign('actionpath', $actionpath);

        return $this->fetch();
    }

    public function assignrole_json()
    {   
       
       $w=' and ra.actionid='.input('actionid/d');
        $k=addslashes( input('keyword'));//防SQL注入
        if($k!='') {
            $w.=" and (r.rolename like '%{$k}%' or  r.roledesc like '%{$k}%'  )";
        }
        $psql=new  \PageSQL();
        $psql->select="SELECT r.id,r.rolename,r.roledesc,r.sortno  ";
        $psql->from =" FROM roles r inner join role_action ra on r.id=ra.roleid  ";
        $psql->where =' where 1=1 '.$w;
        $psql->orderBy='order by  r.id desc';
        $db=new \DbTools();
        $pager=new \PageJQ();
        $rows=$db->getDataJQ($psql,$pager);
        $json['rows']=$rows;
        $json['records']=$pager->records;
        $json['page']=$pager->page;
        $json['total']=$pager->total;
        return json($json);
    }

    public function addrole()
    {
        $aid=input('actionid');
        $ids = explode(",", input('ids'));
        foreach ($ids as $k => $v) {
            if(!Db::name('role_action')->where('roleid',$v)->where('actionid',$aid)->find())
            {
                Db::name('role_action')->insert(['roleid' => $v, 'actionid' => $aid]);
            }
        }
        return json(['state' => 'success', 'message' => '加入成功']);

    }

    public function removerole()
    {
        Db::name('role_action')->where('actionid', input('actionid'))->where('roleid', 'in', input('ids'))->delete();
        return json(['state' => 'success', 'message' => '移除成功']);
    }

    

    /**
     * @param arrray $data
     * 批量添加 增删改查基本操作
     */
    private function batchAdd($data)
    {
        if (count(explode("/", $data['actcode'])) == 3) {
            list($module, $control, $action) = explode("/", $data['actcode']);
            $row['pid'] = $data['id'];
            $row['ismenu'] = 0;
            $row['isdir'] = 0;
            $row['actcode'] = $module . '/' . $control . '/' . 'create';
            $row['menuurl'] = $module . '/' . $control . '/' . 'create';
            $row['actname'] = '新增录入';
            $row['menutext'] = '新增录入';
            $row['sortno'] = 1;

            Db::name('action')->insert($row);
            $row['actcode'] = $module . '/' . $control . '/' . 'save';
            $row['menuurl'] = $module . '/' . $control . '/' . 'save';
            $row['actname'] = '新增保存';
            $row['menutext'] = '新增保存';
            $row['sortno'] =2;
            Db::name('action')->insert($row);

            $row['actcode'] = $module . '/' . $control . '/' . 'edit';
            $row['menuurl'] = $module . '/' . $control . '/' . 'edit';
            $row['actname'] = '修改录入';
            $row['menutext'] = '修改录入';
            $row['sortno'] =3;
            Db::name('action')->insert($row);

            $row['actcode'] = $module . '/' . $control . '/' . 'update';
            $row['menuurl'] = $module . '/' . $control . '/' . 'update';
            $row['actname'] = '修改保存';
            $row['menutext'] = '修改保存';
            $row['sortno'] =4;
            Db::name('action')->insert($row);

            $row['actcode'] = $module . '/' . $control . '/' . 'delete';
            $row['menuurl'] = $module . '/' . $control . '/' . 'delete';
            $row['actname'] = '删除保存';
            $row['menutext'] = '删除保存';
            $row['sortno'] =5;
            Db::name('action')->insert($row);

            $row['actcode'] = $module . '/' . $control . '/' . 'read';
            $row['menuurl'] = $module . '/' . $control . '/' . 'read';
            $row['actname'] = '查看详情';
            $row['menutext'] = '查看详情';
            $row['sortno'] = 6;
            Db::name('action')->insert($row);


            $row['actcode'] = $module . '/' . $control . '/' . 'exp';
            $row['menuurl'] = $module . '/' . $control . '/' . 'exp';
            $row['actname'] = '导出选择';
            $row['menutext'] = '导出选择';
            $row['sortno'] =7;
            Db::name('action')->insert($row);

            $row['actcode'] = $module . '/' . $control . '/' . 'expAll';
            $row['menuurl'] = $module . '/' . $control . '/' . 'expAll';
            $row['actname'] = '导出所有';
            $row['menutext'] = '导出所有';
            $row['sortno'] =8;
            Db::name('action')->insert($row);

            $row['actcode'] = $module . '/' . $control . '/' . 'import';
            $row['menuurl'] = $module . '/' . $control . '/' . 'import';
            $row['actname'] = '导入';
            $row['menutext'] = '导入';
            $row['sortno'] =9;
            Db::name('action')->insert($row);
        }

    }



}
