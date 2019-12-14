<?php
use think\Db;
use think\Session;
// 应用公共文件
error_reporting(E_ERROR | E_WARNING | E_PARSE);
//调试函数
function dd($data)
{
    dump($data);
    die;
}
/**
 * @param string $url
 * @param array $data
 * @return json|html
 * curl请求
 */
function http_request($url, $data = null) {
    //初识化
    $ch = curl_init();
    //设置变量
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/x-www-form-urlencoded'
        ));
    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //执行
    $output = curl_exec($ch);
    if (curl_errno($ch)) {
        return 'ERROR ' . curl_error($ch);
    }
    curl_close($ch);
    return $output;
}
/**
 * @return string $token
 * 获取保存的微信token
 * @param [bool] 当文件中token过期，可以通过定义$reset来重新获取token
 */
function get_token($reset=0) {
    $arr['time'] = time();

    $ser_arr = file_get_contents("public/token.txt");
    if ($reset) {
        $ser_arr=0;
    }
    if ($ser_arr) {
        $old_arr = unserialize($ser_arr);
        if ($arr['time'] - $old_arr['time'] > 6000) {
            $arr['token'] = get_newtoken();
            $token = $arr['token'];
            $arr = serialize($arr);
            file_put_contents("public/token.txt", $arr);
        } else {
            $token = $old_arr['token'];
        }
    } else {
        $arr['token'] = get_newtoken();
        $token = $arr['token'];
        $arr = serialize($arr);
        file_put_contents("public/token.txt", $arr);
    }
    return $token;
}
/**
 * @return string $token
 * 请求服务器获取微信token
 */
function get_newtoken() {
    $appid = config('wxsetting')['app_id'];
    $appsecret = config('wxsetting')['app_secret'];
    $new_access_token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
    $new_access_token_json = http_request($new_access_token_url);
    $new_access_token_array = json_decode($new_access_token_json, true);
    $new_access_token = $new_access_token_array['access_token'];
    return $new_access_token;
}
/**
 * @return array $signPackage
 * 用于调用高级接口的信息
 */
function getSignPackage() {
    $jsapiTicket = get_ticket();
    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $timestamp = time();
    $nonceStr = createNonceStr();
    $string = "jsapi_ticket={$jsapiTicket}&noncestr={$nonceStr}&timestamp={$timestamp}&url={$url}";
    $signature = sha1($string);
    $signPackage = array(
        "appId" => config('wxsetting')['app_id'] ,
        "nonceStr" => $nonceStr,
        "url" => $url,
        "timestamp" => $timestamp,
        "signature" => $signature,
        "rawString" => $string
        );
    return $signPackage;
}
/**
 * @return string $ticket
 * 获取保存的微信ticket
 */
function get_ticket() {
    $arr['time'] = time();
    $ser_arr = file_get_contents('public/ticket.txt');
    if ($ser_arr) {
        $old_arr = unserialize($ser_arr);
        if ($arr['time'] - $old_arr['time'] > 6666) {
            $token = get_token();
            $arr['ticket'] = get_newticket($token);
            $ticket = $arr['ticket'];
            $arr = serialize($arr);
            file_put_contents('public/ticket.txt', $arr);
        } else {
            $ticket = $old_arr['ticket'];
        }
    }
    //第一次 操作
    else {
        $token = get_token();
        $arr['ticket'] = get_newticket($token);
        $ticket = $arr['ticket'];
        $arr = serialize($arr);
        file_put_contents('public/ticket.txt', $arr);
    }
    return $ticket;
}
/**
 * @return string $ticket
 * 获取保存的微信ticket
 */
function get_newticket($token) {
    $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=" . $token . "&type=jsapi";
    $data = json_decode(http_request($url) , true);
    return $data['ticket'];
}
/**
 * @param int $length
 * @return string $str
 * 获取随机字符
 */
function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str.= substr($chars, mt_rand(0, strlen($chars) - 1) , 1);
    }
    return $str;
}
/**
 * @param obj $model
 * @param array $data
 * @param string $method 
 * @return json $rst
 * 插入或修改数据,当错误时 抛出异常
 */
function ajax_validate($model,$data,$method)
{
    try{
     $model->$method($data);
 }
 catch(\Exception $e)
 {
    $rst['state']='error';
    $rst['message']=$e->getMessage();
    return json($rst);
}
$rst['state']='success';
$rst['message']='操作成功';
return json($rst);
}
/**
 * @return string $ip
 * 获取用户ip
 */
function getIP()
{
    if (getenv("HTTP_CLIENT_IP"))
        $ip = getenv("HTTP_CLIENT_IP");
    else if(getenv("HTTP_X_FORWARDED_FOR"))
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    else if(getenv("REMOTE_ADDR"))
        $ip = getenv("REMOTE_ADDR");
    else $ip = "Unknow IP";
    return $ip;
}

/**
*前端使用datatable 列表时， 取得orderby 子句，datatable支持多列排序
*/
function getDataTableOrder()
{
   
    $cols=input("columns/a");
    $order=input("order/a");
    $sord='';
        //排序字段拼装
    foreach ($order as $k => $v) {
     $i=$v['column'];
     $dir=strtolower( $v['dir'])=='asc'?'asc':'desc';
     $fn=empty( $cols[$i]['name'] )? $cols[$i]['data']:$cols[$i]['name'];
           $idxname= safeFieldName ( $fn); //排序字段，防止SQL注入
           $sord .=  $idxname . ' ' .$dir .',';
       }
       $sord=trim($sord,',');
      $sord=empty($sord)?'':'order by '.$sord;
    return $sord;
   }


/**
 * @param $fieldname  字段名
 * @return mixed|string
 * 对给定的字段名进行安全过滤，防止SQL注入，字段名只能是数字，字母_.
 */
function safeFieldName($fieldname)
{
    if (empty( $fieldname) )return "";
    return preg_replace("/[^\w\[\]_\.]/", '',$fieldname);
}


function getGUID()
{
   // dd(time().uniqid(mt_rand(), true));
  return   strtoupper(md5(time().uniqid(mt_rand(), true)));
}

//传入int ,转换为带单位的文件大小
function fileSizeToUnit($size,$digits=1)
{   //digits，要保留几位小数
    $unit= array('','K','M','G','T','P');//单位数组，是必须1024进制依次的哦。
    $base= 1024;//对数的基数
    $i   = floor(log($size,$base));//字节数对1024取对数，值向下取整。
    return round($size/pow($base,$i),$digits).' '.$unit[$i] . 'B';
}
//传入带单位的文件大小如B,KB,MB，转换为int型
function fileSizeToByte($sizestr)
{   //digits，要保留几位小数
    $sizestr=strtoupper($sizestr);
    $num=floatval($sizestr);
    $u=str_replace($num, '', $sizestr);
    $unit= array('B','KB','MB','GB','TB','PB');//单位数组，是必须1024进制依次的哦。
    $p=array_keys($unit,$u)[0];
    $ret=$num* pow(1024,$p);
    return  $ret;
}
/**
 * 根据给定的字段名， IDS清单（通常是逗号相格的多个整数，或字符串）
 * @param $fieldName  字段名
 * @param $ids         ids 如 1,2,3 , 'a','b','c'
 * @param $andor         and  或or
 * @param string $typeIsInt  true/false 是否整型字段
 */
function idsInWhere($fieldName,$ids,$andor='and', $typeIsInt=true)
{
  $ret='';
  if($ids=='') return '';
  $arr=explode(',',trim($ids,','));
  foreach ($arr as $a)
  {
    if($typeIsInt)
    {
        $ret.=','.(int)$a;
    }else
    {
        $ret.=",'".addslashes($a)."'";
    }
}
$ret=ltrim($ret,',');
return  $andor. " $fieldName in (".$ret.") ";
}

/**将多个逗号相隔的ID，转换为int 型，再以逗号连接，保证SQL安全
 * 如 '1,3,4,5,delete users ' 将被转换为1,2,3,5,0
 *
 * @param $ids 如1,2,3,4,5
 * @return string
 */
function idsToInt($ids)
{
    $ret='';
    $ar=explode(',',trim($ids,','));
    foreach ($ar as $s)
    {
        $ret.=','.(int)$s;
    }
    return trim( $ret,',');

}
/**判断是否超级系统管理员
 * @return bool
 */
function isRoot()
{
 return session('admin')['loginid'] == 'root' ;
}

/**
 * @param $actionCode EX： admin/user/create
 * @return bool
 * 给定一个actioncode,判断当前用户是否有权限。
 */
function haveAction($actionCode)
{
    if(isRoot()) return true; //root有所有权限
    $myactions=session('admin_action_codes');
    return in_array(strtolower( $actionCode),$myactions);
}
/**
 * 生成pdf
 * @param  string $html      需要生成的内容
 */
function pdf($html='<h1 style="color:red">hello word</h1>')
{
    vendor('Tcpdf.tcpdf');
    $pdf = new \Tcpdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    // 设置打印模式
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Nicola Asuni');
    $pdf->SetTitle('TCPDF Example 001');
    $pdf->SetSubject('TCPDF Tutorial');
    $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
    // 是否显示页眉
    $pdf->setPrintHeader(false);
    // 设置页眉显示的内容
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 017', PDF_HEADER_STRING);
    // 设置页眉字体
    $pdf->setHeaderFont(Array('dejavusans', '', '12'));
    // 页眉距离顶部的距离
    $pdf->SetHeaderMargin('5');
    // 是否显示页脚
    $pdf->setPrintFooter(true);
    // 设置页脚显示的内容
    $pdf->setFooterData(array(0,64,0), array(0,64,128));
    // 设置页脚的字体
    $pdf->setFooterFont(Array('dejavusans', '', '10'));
    // 设置页脚距离底部的距离
    $pdf->SetFooterMargin('10');
    // 设置默认等宽字体
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    // 设置行高
    $pdf->setCellHeightRatio(1);
    // 设置左、上、右的间距
    $pdf->SetMargins('10', '10', '10');
    // 设置是否自动分页  距离底部多少距离时分页
    $pdf->SetAutoPageBreak(TRUE, '15');
    // 设置图像比例因子
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    $pdf->setFontSubsetting(true);
    $pdf->AddPage();
    // 设置字体
    $pdf->SetFont('stsongstdlight', '', 14, '', true);
    $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
    $pdf->Output('example_001.pdf', 'D');
}

/***************************excel处理函数开始********************************/
    /**
     * create_excel
     * [生成excel并下载]
     * @param  [array]      $data       [数据]                                **必填
                     
     * @param  array        $header     [如果不设置，则默认取数据库字段名；   **选填
     *                                  如果设置，请控制二维数组的长度与sql中的字段数量个数相同]                  
     * @param  array        $type       [指定列数据格式]                      **选填
     * @param  string       $filename   [下载文件名]                          **选填
     *  [php:output]                    [浏览器输出]
     */
    function create_excel(array $data,array $header,array $type, $filename='simple',$sheetname='Sheet1'){
            $list=['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
            //$filename=create_filename($filename);
            //初始化phpexcel对象
            $phpexcel=phpexcel_init($data,$list,$sheetname);
            //写入表头
            write_header($data,$header,$phpexcel);
            //写入数据
            write_sheet($data,$phpexcel,$type,$list,2);
            //下载文件
            excel_download($phpexcel,$filename);
        }
        /**
         * 初始化phpexcel对象
         * @param  [array]  $data  [数据]         **必填
         * @param  [type]   $list  [表格列码]     **必填
         * @param  string   $sheet [表格名]       **选填
         * @return [object]        [phpexcel]     
         */
        function phpexcel_init($data,$list,$sheet='Sheet1')
        {
            ini_set('max_execution_time', '0');
            Vendor('PHPExcel.PHPExcel');
            $phpexcel = new \PHPExcel();
            $phpexcel->getProperties()
                ->setCreator("Maarten Balliauw")
                ->setLastModifiedBy("Maarten Balliauw")
                ->setTitle("Office 2007 XLSX Test Document")
                ->setSubject("Office 2007 XLSX Test Document")
                ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
                ->setKeywords("office 2007 openxml php")
                ->setCategory("Test result file");
            $phpexcel->getActiveSheet()->setTitle($sheet);
            $phpexcel->setActiveSheetIndex(0);
            //设置单元格根据内容自动调整长度
            for ($i=0; $i <count($data[0]) ; $i++) { 
                    $phpexcel->getActiveSheet()->getColumnDimension($list[$i])->setAutoSize(true);
            }
            return $phpexcel;
        }
        /**
         * 写入表头标题
         * @param  [array]  $data     [数据]              **选填
         * @param  [array]  $header   [标题数组]          **必填
         * @param  [object] $phpexcel [phpexcel对象]      **必填
         * @param  [bool]   $type     默认根据二维数据插入标题,否则根据自定义一维数组插入标题      **选填
         */
         function write_header($data,$header,$phpexcel,$type='')
        {
            $sheet=$phpexcel->setActiveSheetIndex(0);
            if (!empty($type)) {
                $key = ord("A");
                for ($i=0; $i <count($header) ; $i++) { 
                    $colum = chr($key);
                    $sheet->setCellValue($colum.'1', $header[$i]);
                    $key++;
                }
                return ;
            }
            
            if (empty($header)) {
                foreach ($data as $key => $value) {
                    foreach ($value as $k=> $v) {
                            $header[]=$k;
                        }
                }
                $header=array_unique($header);
                $key = ord("A");
                foreach($header as $v){
                    $colum = chr($key);
                     $sheet->setCellValue($colum.'1', $v);
                    
                    $key += 1;
                }
            }else{
                $key = ord("A");
                foreach($header as $v){
                    $colum = chr($key);
                    $sheet ->setCellValue($colum.'1', $v);
                    
                    $key += 1;
                }    
            }
        }
        /**
         * 向phpexcel表格写入数据
         * @param  [array]      $data     [数据]                **必须
         * @param  [object]     $phpexcel [表格对象]            **必须
         * @param  [type]       $type     [指定列的数据格式]参考 TP5MySQL\src\vendor\PHPExcel\PHPExcel\Style\NumberFormat.php
         *                                支持：string、float、int; 默认为自动识别 
         *                                                      **选填
         * @param  [array]      $list     [表格列码]            **必须
         * @param  integer      $cell     [开始插入数据行数]    **选填
         * 
         */
        function write_sheet($data,$phpexcel,$type,$list,$cell=1)
        {
            $sheetdata=$phpexcel->getActiveSheet();
            //写入数据
            foreach ($data as $k => $v) {
                $data[$k]=array_values($v);
                 foreach ($data[$k] as $key => $value) {
                    //根据指定类型写入数据
                    if ($type) {
                        switch (strtolower($type[$key])) {
                            case 'string':
                                $sheetdata->getStyle($list[$key].$cell)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
                                $sheetdata->setCellValue($list[$key].$cell, $value);
                                break;
                            case 'float':
                                $sheetdata->getStyle($list[$key].$cell)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                                $sheetdata->setCellValue($list[$key].$cell,$value);
                                break;
                            case 'int':
                                $sheetdata->getStyle($list[$key].$cell)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
                                $sheetdata->setCellValue($list[$key].$cell,$value);   
                                    break;
                            default:
                                $sheetdata->setCellValue($list[$key].$cell, $value);
                                break;
                        }
                    //未设置指定类型则以默认方式写入 
                    }else{
                        $sheetdata->setCellValue($list[$key].$cell, $value);
                    }
                        
                 }
                $cell++;
            }
        }
    /**
     * 下载$phpexcel对象生成的excel
     * @param  [object] $phpexcel [表格对象]    **必须
     * @param  [string] $filename [文件名]      **选填
     *            
     */
       function excel_download($phpexcel,$filename='simple')
       {                   
            header('Content-Description: File Transfer'); 
            header("Content-Type: application/octet-stream");  
            header('Content-Disposition:inline;filename="'.$filename.'.xls"');  
            header("Content-Transfer-Encoding: binary");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header('Expires: 0');  
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");  
            header("Pragma: public");
            $objwriter = \PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
            $objwriter->save('php://output');
            exit;
       }
    /**
    * 生成csv
    * @param  array         $data       [数据]            **必须    
    * @param  array         $header     [表头数组]        **选填
    * @param  string        $filename   [保存的文件名]    **选填
    * @return php://output              文件流
    */
     function create_csv($data, array $header,$filename="simple")
    {

        if (!empty($header)) {
             foreach ($header as $key => $value) {
                if (is_array($value)) {
                    throw new Exception('表头请使用一维数组', 1);
                    return false;
                }
            }
        }
       
        header('Content-Type: application/force-download');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'.csv"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0

        ob_flush();
        flush();

        //创建临时存储内存
        $fp = fopen('php://memory','w');
        //fputcsv($fp,$data);
        //表头
        if (!empty($header)) {
            fputcsv($fp,$header);
        }else{
            foreach ($data as $key => $value) {
                foreach ($value as $k=> $v) {
                    $header[]=$k;
                }
            }
            $header=array_unique($header); 
             fputcsv($fp,$header);
        }
             
        foreach ($data as $key => $value) {
            fputcsv($fp,$value);    
        }
        rewind($fp);
        $content = "";
        while(!feof($fp)){
              $content .= fread($fp,10240);
        }
        fclose($fp);
        $content =changeCode($content,'gbk');//转成gbk，否则excel打开乱码
        echo $content;
        exit;
    }
    /**
     * 按模板文件生成excel并下载
     * @param  array    $data          [数据]         **必须
     * @param  string   $templatepath  [模板文件路径] **必须
     * @param  string   $filename      [保存文件名]   **选填
     * 
     */
    function createExcelAsTemplate(array $data,$templatepath,$filename='simple.xls')
        {
            //读取模板文件信息
            $res=getinfo_excel($templatepath);
            $list=['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
            $phpexcel=phpexcel_init($data,$list);
            if (!empty($res['data']) ) {
               write_sheet($res['data'],$phpexcel,'',$list,1);
            }
            $rows=isset($res['rows'])?$res['rows']:0;
            if (!empty($data) && is_array($data)) {
                 //写入新数据
               write_sheet($data,$phpexcel,'',$list,$rows+1);
            }
            excel_download($phpexcel,$filename);
        }
    /**
     * 读取模板文件内容
     * @param  [string] $file       [模板文件路径]
     * @return [array]  $result     [文件信息]
     *                               $result[
     *                                    'data'=>'excel表格数据',
     *                                    'rows'=>'行数',
     *                                    'cols'=>'列数',
     *                               ]
     */
    function getinfo_excel($file){
        //储存返回内容的数组
        $result=['rows'=>0,'cols'=>0];
         // 判断文件是什么格式
        $type = pathinfo($file);
        $type = strtolower($type["extension"]);

        $type=$type==='csv' ? $type : ($type==='xlsx'?'Excel7':'Excel5');
        if ($type=='csv') {
            $out=array();
            $n=0;
            $handle = fopen($file, 'r');   
            while ($data = fgetcsv($handle, 10000))   
            {   
             $info[]=$data;
             $n++;
         } 
         fclose($handle);  
         return $result=['data'=>$info,'rows'=>$n];   
         
        }

        ini_set('max_execution_time', '0');
        Vendor('PHPExcel.PHPExcel');

    // 判断使用哪种格式
        
        if ($type=='Excel7') {
            $objReader = new \PHPExcel_Reader_Excel2007();
        }else{
            $objReader = \PHPExcel_IOFactory::createReader($type);
        }

        $objPHPExcel = $objReader->load($file);
        $sheet = $objPHPExcel->getSheet(0);
    // 取得总行数
        $highestRow = $sheet->getHighestRow();
    // 取得总列数
        $highestColumn = $sheet->getHighestColumn();
    //循环读取excel文件,读取一条,插入一条
        $data=array();
    //从第一行开始读取数据
        for($j=1;$j<=$highestRow;$j++){
        //从A列读取数据
            for($k='A';$k<=$highestColumn;$k++){
            // 读取单元格
                $data[$j][]=$objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
                if ($j==1) {
                    $header[]=$objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
                }
            }
        }
        return $result=['data'=>$data,'header'=>$header,'rows'=>$highestRow,'cols'=>$highestColumn];
    }


//导入
    function import_excel($file){
    // 判断文件是什么格式
            $type = pathinfo($file);
            $type = strtolower($type["extension"]);
            $type=$type==='csv' ? $type : ($type==='xlsx'?'Excel7':'Excel5');
            if ($type=='csv') {
                $out=array();
                $n=0;
                $handle = fopen($file, 'r');   
                while ($data = fgetcsv($handle, 10000))   
                {   
                 $info[]=$data;
             } 
             fclose($handle);  
             return $info;   
             
         }

         ini_set('max_execution_time', '0');
         Vendor('PHPExcel.PHPExcel');
    // 判断使用哪种格式
        if ($type=='Excel7') {
            $objReader = new \PHPExcel_Reader_Excel2007();
        }else{
            $objReader = \PHPExcel_IOFactory::createReader($type);
        }
         $objPHPExcel = $objReader->load($file);
         $sheet = $objPHPExcel->getSheet(0);
    // 取得总行数
         $highestRow = $sheet->getHighestRow();
    // 取得总列数
         $highestColumn = $sheet->getHighestColumn();
    //循环读取excel文件,读取一条,插入一条
         $data=array();
    //从第一行开始读取数据
         for($j=1;$j<=$highestRow;$j++){
        //从A列读取数据
            for($k='A';$k<=$highestColumn;$k++){
            // 读取单元格
                $data[$j][]=$objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
            }
        }
        return $data;
    }
/***************************excel处理函数结束********************************/
/***************************字符串编码处理函数开始********************************/
function chkCode($string){
       $code = array('UTF-8','GBK','GB18030','GB2312');
       foreach($code as $c){
          if( $string === iconv('UTF-8', $c, iconv($c, 'UTF-8', $string))){
             return $c;
         }
     }
     return "no";
 }

 function changeCode($string,$code='utf-8')
 {
    $type=chkCode($string);
    if ($type!='no') {
     $out=iconv($type,$code,$string);
 }
 return $out;
}

/***************************字符串编码处理函数结束********************************/
function downloadfile($filepath='')
{
    header('Content-Description: File Transfer');
    
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.basename($filepath));
    header('Content-Transfer-Encoding: binary');
    header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); 
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filepath));
    ob_clean();
    flush();
    readfile($filepath);

}

function _sock($url) {
  $host = parse_url($url,PHP_URL_HOST);
  $port = parse_url($url,PHP_URL_PORT);
  $port = $port ? $port : 80;
  $scheme = parse_url($url,PHP_URL_SCHEME);
  $path = parse_url($url,PHP_URL_PATH);
  $query = parse_url($url,PHP_URL_QUERY);
  if($query) $path .= '?'.$query;
  if($scheme == 'https') {
    $host = 'ssl://'.$host;
    }

    $fp = fsockopen($host,$port,$error_code,$error_msg,1);
    if(!$fp) {
        return array('error_code' => $error_code,'error_msg' => $error_msg);
    }
    else {
            stream_set_blocking($fp,true);//开启非阻塞模式
            stream_set_timeout($fp,1);//设置超时
            $header = "GET $path HTTP/1.1\r\n";
            $header.="Host: $host\r\n";
            $header.="Connection: close\r\n\r\n";//长连接关闭
            fwrite($fp, $header);
            usleep(1000); //如果没有这延时，可能在nginx服务器上就无法执行成功
            fclose($fp);
            return array('error_code' => 0);
        }
}
/**
 * 模拟用户请求
 * @param  [string] $url    请求地址
 * @param  string $data   post数据
 * @param  string $decode 是否进行json解码
 * @return string|array    请求地址返回内容
 */
function curl($url,$data='',$decode='')
{
       $ssl = substr($url, 0, 8) == "https://" ? TRUE : FALSE;
       if ($ssl)
       {
            $opt[CURLOPT_SSL_VERIFYHOST] = 2;//如果是https则告诉服务器不进行sll认证
            $opt[CURLOPT_SSL_VERIFYPEER] = FALSE;
        }
        $opt[CURLOPT_RETURNTRANSFER]= true;
        // $opt[CURLOPT_BINARYTRANSFER]= true;
        //post 数据
        if (!empty($data)) {
         
            $opt[CURLOPT_POST]=true;
            $opt[CURLOPT_POSTFIELDS]=$data;
            
        }
        $ch = curl_init($url) ;  
        curl_setopt_array($ch,$opt) ;
        $ouput=curl_exec($ch);
        if (curl_errno($ch)) {
            return 'ERROR ' . curl_error($ch);
        }
        curl_close($ch);
        if (empty($decode)) {
            return $ouput;
        }
        return json_decode($ouput,true);
    }
/**
 * 清除session里的tokenGUID
 * @return [type] [description]
 */
function clearTokenGUID()
{
    //判断是否连续输入
    if (input('iscontinue')) {
        return ;
    }
    $tokenname=array_keys(input('post.'))[0];
    Session::delete($tokenname);

}
/**
 * 生成api唯一令牌
 * @param  integer $length [令牌长度]
 * @return [string]         
 */
function createToken($length=32)
{
    $token='';
    $chars='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    for ($i=0; $i <$length ; $i++) { 
        $token.=$chars[mt_rand(0,strlen($chars)-1)];
    }
    $time=time();
    $salt='dknsdhn112';
    $token=md5($token.$time.$salt);
    return $token;
}


/******************************ORACLE 字段操作方法 开始***************************/
 function clobToStr(array $data)
{
    foreach ($data as $k => $v) {
        if (is_array($v)) {
          foreach ($v as $key => $value) {
            is_resource($value) && $data[$k][$key]=stream_get_contents($value);
          }
        }
        else{
          is_resource($v) && $data[$k][$v]=stream_get_contents($v);
        }
    }
    return $data;
}

    /**
     * 针对oracle日期处理函数
     * @param  [type] $date [description]
     * @return [type]       [description]
     */
function oraDate($date){
    $arr=array();
    $arr[0]='exp';
    $date="to_date('".date('Y-m-d H:i:s',strtotime($date))."','yyyy-mm-dd HH24:mi:ss')";
    $arr[1]=$date;
    return $arr;
}

/**
 * 使用ociclob类的助手方法
 * @param  string $table    [要操作的数据表]
 * @param  string $dbconfig [config文件中数据库配置的数组名]
 * @return [object]           [object]
 *例子：
 *    查询  $res=ociclob()->table('tt')->select();        或者  $res=ociclob('tt')->select();
 *    新增  $res=ociclob('tt')->data($data)->insert();    或者  $res=ociclob('tt')->insert($data);
 *    更新        $res=ociclob('tt','db2')->where('id=2')->data($data)->update();     
 *        或者    $res=ociclob('tt','db2')->where('id=2')->update($data); 
 */
function OCI($table='',$dbconfig='database')
{
    return new \Oci($table,$dbconfig);
}
/******************************ORACLE 字段操作方法 结束***************************/


function unlink_dir( $dir ,$deldir=false)
{
  if ( $handle = opendir( $dir ) )
  {
    while ( false !== ( $item = readdir( $handle ) ) )
    {
      if ( $item != "." && $item != ".." )
      {
        if ( is_dir( "$dir/$item" ) )
        {
          unlink_dir( "$dir/$item" );
        }
        else
        {
          @unlink( "$dir/$item" ) ;
        }
      }
    }
    closedir( $handle );
    if ($deldir) {
        @rmdir( $dir ) ;
    }
    return true;
  }
}


function wx_userinfo($openid)
{
    $access_token=get_newtoken();
    $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access_token&openid=$openid&lang=zh_CN";
    $result = curl($url);
    $result = json_decode($result,true);
    return $result;
}

function get_curl()
{
    $url = $_SERVER['REQUEST_SCHEME'].'://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    return $url;
}

function createShareQR_A($url)
{
    $access_token=get_token();
    $path=$url;
    $width=430;
    $post_data='{"path":"'.$path.'","width":'.$width.'}';
    $url="https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=".$access_token;//数量有限100000
    $result=curl($url,$post_data);
    return $result;
}
/**
 * [createShareQR_B description]
 * @param  
 * scene    String      最大32个可见字符，只支持数字，大小写英文以及部分特殊字符：!#$&'()*+,/:;=?@-._~，其它字符请自行编码为合法字符（因不支持%，中文无法使用 urlencode 处理，请使用其他编码方式）
 *page    String      必须是已经发布的小程序页面，例如 "pages/index/index" ,根路径前不要填加'/',不能携带参数（参数请放在scene字段里），如果不填写这个字段，默认跳主页面
width   Int 430 二维码的宽度
auto_color  Bool    false   自动配置线条颜色，如果颜色依然是黑色，则说明不建议配置主色调
line_color  Object  {"r":"0","g":"0","b":"0"}   auto_color 为 false 时生效，使用 rgb 设置颜色 例如 {"r":"xxx","g":"xxx","b":"xxx"}
 * @return [type]        [description]
 */
 function createShareQR_B($param)
{
    $access_token=get_token();
    $auto_color=$param['auto_color']?($param['auto_color']==true?true:false):true;
    $path=$param['path'];
    $width=intval($param['width'])?intval($param['width']):430;
    $post_data='{"scene":"'.$param['scene'].'","path":"'.$path.'","width":'.$width.',"auto_color":"'.$auto_color.'","line_color":"'.$line_color.'"}';
    $url="https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token;//数量不限
    $result=curl($url,$post_data);
    return $result;
}


function create_path_by_date()
{
   return  date("Y") . "/" . date("m") . '/' . date("d") . '/';
}

