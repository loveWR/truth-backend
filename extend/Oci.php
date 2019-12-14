<?php
use think\log;
/**
 *oracle 数据库操作类似，框架操作oracle数据库时，尽量使用这个类似，不使用TP框架
 * 注意以下方法中所有返回的记录集，键名都是字段名转为大写了，除非数据库建表时使用了双引号指定大小写敏感的列名如”Name"。
    使用举例：
        $o=new \Oci('db2');
         $res= $o->insert('tt',$data );
         $res= $o->update('tt',$data,'where id=810675 ');
        $id=$o->get_col('select con from tt');
       $id=$o->get_id();
       $o->begin_trans();
      $r=  $o->execute('update tt set c2=:c2 where id=810681',['c2'=>'testc2']);
       $o->commit();
 */
class Oci
{
    protected $conn;  //数据库链接
    protected $fieldInfo=array(); //缓存表的字段信息，二维数组，表名为主键
    protected $trans_flag=false; //是否处理begintrans 状态
    /**
     * @param $k 要被单引号括起来的关键字内容
     */
    public static function quot($k)
    {
        $k=str_replace( "'", "''",$k);
        $k=str_replace( "&", "'||CHR(38)||'",$k);
        return "'".$k."'";
    }
    /**
     * @param $k 关键字
     * @param $t  LIKE比较方式 ，可以是L，B，R  分别代表like 'xx%','%xx%' ,'%xx'
     */
    public static function quot_like($k, $t='B')
    {
        $ex = "";
        if (strpos($k, '_') > -1 || strpos($k, '%') > -1) //有这两个字符，则需要转义
        {
            $ex = "escape'\\'";
            $k = str_replace("\\", "\\\\", $k);
            $k = str_replace("_", "\\_", $k);
            $k = str_replace("%", "\\%", $k);
        }
        $k = str_replace("'", "''", $k);
        $k = str_replace("&", "'||CHR(38)||'", $k);
        $Lfix = ($t == 'B' || $t == 'R') ? "%" : "";
        $Rfix = ($t == 'B' || $t == 'L') ? "%" : "";
        $str = "'" . $Lfix . $k . $Rfix . "' " . $ex;
        return $str;
    }

    /**
     * Oci constructor. 构造函数1
     * @param string $dbconfig 数据库配置名 默认链接database.php中的数据库配置，也可以指定config中的其它数据库配置信息如db2
     * config($dbconfig)['database'] 通常是oracle的tns_name
     */
    public function __construct( $dbconfig = 'database')
    {  //192.168.1.1:1521/ora10g
        $tns=config($dbconfig)['hostname'].':'.config($dbconfig)['hostport'].'/'.config($dbconfig)['database'];
        $this->connect(config($dbconfig)['username'], config($dbconfig)['password'], $tns);
        return $this;
    }

    /**
     * 启动事务
     */
    public function begin_trans()
    {
        $this->trans_flag = true;
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        oci_commit($this->conn);
        $this->trans_flag = false;
    }

    /**
     * 回滚事务
     */
    public function rollback()
    {
        oci_rollback($this->conn);
        $this->trans_flag = false;
    }

    /**连接ORACLE
     * @param $user
     * @param $password
     * @param $connection_string TNS_name 中配置的数据库网络服务名
     * @return $this
     */
    public function connect($user, $password, $connection_string)
    {//PHP程序为UTF-8编码，所以此处必须使用AL32UTF8（或UTF8，9i 以前），而不是oracle服务器的编码ZHS16GBK
        $this->conn = oci_connect($user, $password, $connection_string,'AL32UTF8');
        return $this;
    }

    /**根据给定的SQL查询返回数组，如果有LOB字段，内容也会载入返回的数组
     * @param $sql   注意防止SQL注入
     * @return array
     */
    public function query($sql)
    {
        config('database.debug') && Log::sql('[OCI] '.$sql);
        $stmt = oci_parse($this->conn,$sql );
        oci_execute($stmt);
        while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_LOBS+OCI_RETURN_NULLS)) {
            $data[] = $row;
        }
        // 释放资源
        oci_free_statement($stmt);
        return $data;
    }

    /**取得单列的值
     * @param $sql 取单列值的SQL ，如select count(*) from users
     * @return mixed 第一行，第一列的值
     */
    public function get_col($sql)
    {
        config('database.debug') && Log::sql('[OCI] '.$sql);
        $stmt = oci_parse($this->conn,  $sql );
        oci_execute($stmt);
        while ($row = oci_fetch_array($stmt, OCI_NUM + OCI_RETURN_LOBS+OCI_RETURN_NULLS )) {
            $data[] = $row;
        }
        // 释放资源
        oci_free_statement($stmt);
        return $data[0][0]; //注意 要OCI_NUM
    }

    /** 执行数据操作SQL，
     * @param $sql 注意防范SQL注入,只能对文本、数字、日期字段使用参数，LOB字段不能使用，要修改LOB字段请使用udpate方法
     * @param $data SQL参数数组，键名必须与$sql中的参数定义完全对应,不能多，不能少
     * @return bool     *
     */
    public function execute($sql,$data=[])
    {
        config('database.debug') && Log::sql('[OCI] '.$sql);
        $stmt = oci_parse($this->conn,$sql );
        //绑定变量
        if(count($data)>0) {
            foreach ($data as $key => $val) {
               oci_bind_by_name($stmt, ":{$key}", $data[$key]);
                }
        }

        // 执行该语句的使用，OCI_NO_AUTO_COMMIT -默认不提交，形成事务
        oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        // 释放资源
        oci_free_statement($stmt);
        if(!$this->trans_flag) //如果外层没有事务，则直接提交，否则由外层事务控制。
        {oci_commit($this->conn);}
        return true;
    }
    /** 分页查询
     * @param $pagesql PageSql 类
     * @param $pageJQ  pageJQ类 ，查询后会修改其中的分页属性
     * @return array   返回分页数据
     */
    public function get_page($pagesql, $pageJQ)
    {
        //if(strtolower( config('database.type'))=='oracle')
        //如果默认数据库为oracle 则使用这行 $sql = $this->ora_jqSql($pagesql, $pageJQ);
        $sql = $this->get_sql($pagesql, $pageJQ);
        $data = $this->query($sql);
        return $data;
    }

    /**准备分页查询的SQL，并返回总记录数和分页信息
     * @param $ps
     * @param $pa   pageJQ类 ，查询后会修改其中的分页属性
     * @return string 返回分页的SQL
     */
    private function get_sql($ps, $pa)
    {
        //先取总记录数
        $sql = "SELECT COUNT(*) CNT $ps->from $ps->where";
        $ar = $this->query($sql);
        $records = $ar[0]['CNT'];
        $pa->records = $records;
        //计算总页数，确保当前页码>0 不大于总页数
        $pa->total = ceil($records / $pa->rows);
        $pa->page = $pa->page > $pa->total ? $pa->total : $pa->page;
        $pa->page = $pa->page < 1 ? 1 : $pa->page;
        //计算翻页记录起止数
        $start = ($pa->page - 1) * $pa->rows;
        $end =  $pa->page* $pa->rows;
        //排序子句的构成。 优先使用PageSQL->OrderBy，未指定则以前端排序条件组合 。
        $orderby = empty($ps->orderBy) ? $pa->orderBy : $ps->orderBy;
        $sql = "SELECT /*+ FIRST_ROWS(50)*/ * FROM 
                                    (SELECT ALIAS_TABLE_NAME___.*, ROWNUM  ROWNUM_ FROM 
                                        ( $ps->select  $ps->from  $ps->where $orderby ) ALIAS_TABLE_NAME___ 
                                    ) WHERE ROWNUM_ >  $start AND  ROWNUM_ <= $end";
        return $sql;
    }

    /**  向指定表中插入一行数据，可以包括多个CLOB字段
     * 如有日期字段，可以是以下格式 2017-10-01 （日期）,2017-10-01 13:59:59（含时间的日期）, sysdate(当前时间),null（空）
     * @param $table 表名 不区分大小写
     * @param $data  一维数组 键名不区分大小写
     * @return bool  true/false是否成功。
     */
    public function insert($table , $data)
    {
        $fi=$this->get_fields($table); //field info
        $keys=array();
        $values=array();
        $returnings=array();   //LOB字段引用
        $returnings2=array();
        try{
            //数据整理，准备sql
            foreach ($data as $key => $val) {
                $skey=strtolower($key);
                if(!array_key_exists($skey,$fi)) continue;//给定的DATA中的KEY不是有效字段名
                $keys[]=$skey;
                $ftype=$fi[$skey]['data_type'];
                switch ($ftype)
                {
                    case 'clob':
                        $values[]='EMPTY_CLOB()';
                        $returnings[]=$skey;
                        $returnings2[]=':'.$skey.'_lob';
                        break;
                    case 'date':
                        if(strtolower($val)=='sysdate') //系统当前时间
                        {
                            $values[] = "sysdate";
                        }elseif(empty($val)) //日期字段赋空值
                        {
                            $values[] = "null";
                        }
                        else{  //2017-10-10 23:59:59 19位日期格式
                            $values[] = "TO_DATE(:{$skey} ,'yyyy-MM-dd HH24:mi:ss')";
                        }
                        break;
                    default:
                        $values[]=":{$skey}";
                        break;
                }

            }
            $keys_str=join(',',$keys);
            $values_str=join(',',$values);
            $returning_str='';
            if(count($returnings)>0) //有CLOB字段
            {
                $returning_str=" RETURNING ".join(',' ,$returnings). " INTO ".join(',' ,$returnings2);
            }
            //组装SQL
            $sql = "INSERT INTO   {$table} ({$keys_str}) VALUES({$values_str}) " . $returning_str;
            $stmt = oci_parse($this->conn, $sql);
            config('database.debug') && Log::sql('[OCI] '.$sql);
            //绑定变量
            foreach ($data as $key => $val) {
                $skey=strtolower($key);
                if(!array_key_exists($skey,$fi)) continue;//给定的DATA中的KEY不是有效字段名
                $ftype=$fi[$skey]['data_type']; //字段类型
                //根据字段类型，生成相应的参数绑定语句oci_bind_by_name
                switch ($ftype)
                {
                    case 'clob':
                        // 创建一个“空”的OCI LOB对象绑定到定位器
                        $pointer=$skey.'_p'; //指向LOB的指针
                        //以字段名为变量名，指向LOB字段
                        $$pointer = oci_new_descriptor($this->conn, OCI_D_LOB);
                        $lob_param = ':'.$skey.'_lob';
                        // 将Oracle LOB定位器绑定到PHP LOB对象
                        oci_bind_by_name($stmt, $lob_param,$$pointer, -1, OCI_B_CLOB);
                        break;
                    case 'date':
                        if(strtolower($val)!='sysdate' && !empty($val)) {
                            $data[$key]= date('Y-m-d H:i:s', strtotime($val));
                            oci_bind_by_name($stmt, ":{$skey}", $data[$key], 19);//yyyy-mm-dd HH24:23:25
                        }
                        break;
                    case 'varchar2':
                        $max=(int)( $fi[$skey]['char_length']); //字段最大字符数
                        $data[$key]=mb_substr( $data[$key],0,$max,"utf-8");  //截取
                        oci_bind_by_name($stmt, ":{$skey}",$data[$key],$max,SQLT_CHR);
                        break;
                    default:
                        oci_bind_by_name($stmt, ":{$skey}",$data[$key]);
                        break;
                }

            }

            // 执行该语句的使用，OCI_NO_AUTO_COMMIT -默认不提交，形成事务
            $r= oci_execute($stmt, OCI_NO_AUTO_COMMIT);
            // 保存LOB对象数据
            foreach ($data as $key => $val) {
                $skey=strtolower($key);
                //给定的DATA中的KEY不是有效字段名,或不是clob字段
                if(!array_key_exists($skey,$fi) || $fi[$skey]['data_type']!='clob') continue;
                $pointer=$skey.'_p'; //指向LOB的指针
               $$pointer->save($val);
            }
            // 如果成功，则提交。如果Oci的实例处于事务中，则不单个提交，由外层统一提交事务。
            if(!$this->trans_flag)
            {oci_commit($this->conn);}
            return true;
        }
        catch (\Exception $e)
        {    // 如果错误，则回滚事务。如果Oci的实例处于事务中，则不单个提交，由外层统一提交事务。
            if(!$this->trans_flag)
            {oci_rollback($this->conn);}
            throw $e ;
        }
        finally{
            try {
                // 释放资源
                oci_free_statement($stmt);
                foreach ($data as $key => $val) {
                    $skey = strtolower($key);
                    //给定的DATA中的KEY不是有效字段名,或不是clob字段
                    if (!array_key_exists($skey, $fi) || $fi[$skey]['data_type'] != 'clob') continue;
                    $pointer = $skey . '_p'; //指向LOB的指针
                    $$pointer->free();
                }
            }catch(\Exception $e) {}
        }
    }

    /** 更新表中的一条记录
     * @param $table 表名
     * @param $data DATA数组，各字段的值，键名不区分大小写，键名不是字段名的将忽略
     * @param $where  更新条件，注意防止SQL注入,以where关键字开始
     * @return bool  成功返回true
     * @throws Exception 发生错误往上层抛出
     */
    public function update($table,$data,$where='where 1=0')
    {
        $fi=$this->get_fields($table); //field info
        $sets=array();
        $returnings=array();   //LOB字段引用
        $returnings2=array();
        try{
            //数据整理，准备sql
            foreach ($data as $key => $val) {
                $skey=strtolower($key);
                if(!array_key_exists($skey,$fi)) continue;//给定的DATA中的KEY不是有效字段名
                $keys[]=$skey;
                $ftype=$fi[$skey]['data_type'];
                switch ($ftype)
                {
                    case 'clob':
                        $sets[]= $skey.'=EMPTY_CLOB()';
                        $returnings[]=$skey;
                        $returnings2[]=':'.$skey.'_lob';
                        break;
                    case 'date':
                        if(strtolower($val)=='sysdate') //系统当前时间
                        {
                            $sets[] = $skey."=sysdate";
                        }elseif(empty($val)) //日期字段赋空值
                        {
                            $sets[] = $skey."=null";
                        }
                        else{  //2017-10-10 23:59:59 19位日期格式
                            $sets[] = $skey."=TO_DATE(:{$skey} ,'yyyy-MM-dd HH24:mi:ss')";
                        }
                        break;
                    default:
                        $sets[]=$skey."=:{$skey}";
                        break;
                }

            }
            $sets_str=join(',',$sets);
            $returning_str='';
            if(count($returnings)>0) //有CLOB字段
            {
                $returning_str=" RETURNING ".join(',' ,$returnings). " INTO ".join(',' ,$returnings2);
            }
            //组装SQL
            $sql = "UPDATE {$table} SET {$sets_str} {$where} " . $returning_str;
            $stmt = oci_parse($this->conn, $sql);
            config('database.debug') && Log::sql('[OCI] '.$sql);

            //绑定变量
            foreach ($data as $key => $val) {
                $skey=strtolower($key);
                if(!array_key_exists($skey,$fi)) continue;//给定的DATA中的KEY不是有效字段名
                $ftype=$fi[$skey]['data_type']; //字段类型
                //根据字段类型，生成相应的参数绑定语句oci_bind_by_name
                switch ($ftype)
                {
                    case 'clob':
                        // 创建一个“空”的OCI LOB对象绑定到定位器
                        $pointer=$skey.'_p'; //指向LOB的指针
                        //以字段名为变量名，指向LOB字段
                        $$pointer = oci_new_descriptor($this->conn, OCI_D_LOB);
                        $lob_param = ':'.$skey.'_lob';
                        // 将Oracle LOB定位器绑定到PHP LOB对象
                        oci_bind_by_name($stmt, $lob_param,$$pointer, -1, OCI_B_CLOB);
                        break;
                    case 'date':
                        if(strtolower($val)!='sysdate' && !empty($val)) {
                            $data[$key]= date('Y-m-d H:i:s', strtotime($val));
                            oci_bind_by_name($stmt, ":{$skey}", $data[$key], 19);//yyyy-mm-dd HH24:23:25
                        }
                        break;
                    case 'varchar2':
                        $max=(int)( $fi[$skey]['char_length']); //字段最大字符数
                        $data[$key]=mb_substr( $data[$key],0,$max,"utf-8");  //截取
                        oci_bind_by_name($stmt, ":{$skey}",$data[$key],$max,SQLT_CHR);
                        break;
                    default:
                        oci_bind_by_name($stmt, ":{$skey}",$data[$key]);
                        break;
                }

            }

            // 执行该语句的使用，OCI_NO_AUTO_COMMIT -默认不提交，形成事务
            $r= oci_execute($stmt, OCI_NO_AUTO_COMMIT);
            // 保存LOB对象数据
            foreach ($data as $key => $val) {
                $skey=strtolower($key);
                //给定的DATA中的KEY不是有效字段名,或不是clob字段
                if(!array_key_exists($skey,$fi) || $fi[$skey]['data_type']!='clob') continue;
                $pointer=$skey.'_p'; //指向LOB的指针
                $$pointer->save($val);
            }
            // 如果成功，则提交。如果Oci的实例处于事务中，则不单个提交，由外层统一提交事务。
            if(!$this->trans_flag)
            {oci_commit($this->conn);}
            return true;
        }
        catch (\Exception $e)
        {    // 如果错误，则回滚事务。如果Oci的实例处于事务中，则不单个提交，由外层统一提交事务。
            if(!$this->trans_flag)
            {oci_rollback($this->conn);}
            throw $e ;
        }
        finally{
            try {
                // 释放资源
                oci_free_statement($stmt);
                foreach ($data as $key => $val) {
                    $skey = strtolower($key);
                    //给定的DATA中的KEY不是有效字段名,或不是clob字段
                    if (!array_key_exists($skey, $fi) || $fi[$skey]['data_type'] != 'clob') continue;
                    $pointer = $skey . '_p'; //指向LOB的指针
                    $$pointer->free();
                }
            }catch(\Exception $e) {}
        }
    }

    /** 取得最大ID号，基于oracle的序列生成下个ID号。全库唯一。
     * @return mixed
     */
    public function get_id()
    {
        $sql = "SELECT GETID.NEXTVAL ID FROM DUAL";
        $stmt = oci_parse($this->conn,  $sql );
        oci_execute($stmt);
        while ($row = oci_fetch_array($stmt, OCI_NUM  )) {
            $data[] = $row;
        }
        // 释放资源
        oci_free_statement($stmt);
        return $data[0][0]; //注意OCI_NUM
    }

    /**
     * 取得数据表的字段信息
     * @access public
     * @param $tableName  给定表名，如users或 erp.products
     * @return array  返回给定表的字段信息，列名，类型，字段长，精度
     */
    public function get_fields($tableName)
    {
        $tableName=strtoupper($tableName);
        if(isset($this->fieldInfo[$tableName])) //尝试使用缓存
        {
           return  $this->fieldInfo[$tableName];
        }
        $sql = "SELECT COLUMN_NAME,DATA_TYPE,NVL(DATA_PRECISION,DATA_LENGTH) DATA_LENGTH,CHAR_LENGTH, DATA_SCALE   
                FROM  USER_TAB_COLUMNS WHERE UPPER( TABLE_NAME ) ='{$tableName}'";
        if(false!==strpos( $tableName,'.'))  //操作的是别的用户下的表，如scott.users
        {
            $ar=explode($tableName,'.');
            $sql = "SELECT COLUMN_NAME,DATA_TYPE,NVL(DATA_PRECISION , DATA_LENGTH ) DATA_LENGTH,CHAR_LENGTH, DATA_SCALE   
                FROM  USER_TAB_COLUMNS WHERE  UPPER(OWNER)='{$ar[0]}' UPPER(table_name) ='{$ar[1]}'";
        }
        $result = $this->query($sql);
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[strtolower($val['COLUMN_NAME'])] = [
                    'column_name' => strtolower($val['COLUMN_NAME']),
                    'data_type' => strtolower($val['DATA_TYPE']),
                    'data_length' => $val['DATA_LENGTH'],
                    'data_scale' =>empty( $val['DATA_SCALE'])?0:$val['DATA_SCALE'],
                    'char_length' => $val['CHAR_LENGTH']
                ];
            }
            $this->fieldInfo[$tableName]=$info;
        }
        return $info;
    }

}

?>