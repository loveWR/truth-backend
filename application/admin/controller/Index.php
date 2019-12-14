<?php
namespace app\admin\controller;
use think\Db;
use think\Request;
use app\api\Token;
use app\api\exception\TokenException;
use \Oci;
class Index extends Base
{

    public static $cpid;
    public static $pid=2;
    public function index()
    {   
      
      return view();
    }
	public function demo(){
		  $roles=Db::name('roles')->order(" sortno asc ")->select();
        $rolesddl=$this->initSelectByData($roles,'roleid','id','rolename',[],'全部角色');
        $this->assign('rolesddl',$rolesddl);
		return $this->fetch();
		
	}
    public function test()
    {
      return view();
          $a=explode(',', input('ids')) ;
         
      dd($a);

$basename = Request::instance()->root();
// if (pathinfo($basename, PATHINFO_EXTENSION) == 'php') {
//     $basename = dirname($basename);
// }
// if ($basename == '\\') {
//     $basename = '';
// }
// //解决linux ztree页面查看时加载缓慢问题
// if ($basename == '/') {
//     $basename = '';
// }
 
      dd($basename);//

        return $this->fetch();
      $a=urlencode('admin/adminusers/list');
      dd($a);
         //  $data=action('admin/adminusers/out' );
         // dd($data);

//        $a="ab%c',\",abc,3333&28_a%b\\a";
//        $b=Oci::quot_like($a);
//        dd($b);
//        $a='我爱中华人民共和国我爱中华人民共和国我爱中华人民共和国';
//         $a='1234';
//        $b=mb_substr($a,0,22,"utf-8");
//        $this->assign("b",$b);
//        return view();
//        $o=OCI('tt','db2');
//      $res=$o->query("select * from tt ");
        //  $res=$o->getFields('tt');
        $data['aInt']=14;
        $data['nn']=1.55;
         $data['c']='a1234567890127890我爱共和国我爱中华人民共和国我爱中华人民共';
        $data['c2']='b2222222222我爱共和国我爱中华人民共和国我爱中华人民共';
          $data['d']='sysdate';
          $data['con']='con';
         $data['con2']='con2';




        $o=new \Oci('db2');
         $res= $o->insert('tt',$data );
        //  $res= $o->update('tt',$data,'where id=810675 ');
        // $id=$o->get_col('select con from tt');

       // $id=$o->get_id();
     //   $o->begin_trans();
//
    //   $r=  $o->execute('update tt set c2=:c2 where id=810681',['c2'=>'testc2']);
//
//        $o->commit();
       dd(  $res );

//        $data["cb"]=file_get_contents('a.txt');
//        $data["d"]=date('Y-m-d H:i:s',time());
//        $data["t"]=date('Y-m-d',time());
//      $res=OCI('tt','db2')->where('id=2')->update($data);
//      dd($res);
    }

}
