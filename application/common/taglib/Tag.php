<?php
/**
 * Created by nango
 * User: WANG
 * Date: 2016-08-11
 * Time: 9:58
 */

namespace app\common\taglib;

use think\template\TagLib;
use think\Db;

class Tag extends TagLib
{
    protected $tags = [
        'article' => ['attr' => 'name,limit,order,field','close' => 1],
        'select' => ['attr' => 'dict,name,selectVal,defaultTxt,defaultVal,css,type,codetype,datakey,dataval','close' => 0],
    ];
    public function tagSelect($tag)
    {
        $selectVal = isset($tag['selectVal']) ? $tag['selectVal'] : '';
        $defaultTxt = isset($tag['defaultTxt']) ? $tag['defaultTxt'] : '';
        $defaultVal = isset($tag['defaultVal']) ? $tag['defaultVal'] : '';
        $datakey = isset($tag['datakey']) ? $tag['datakey'] : '';
        $dataval = isset($tag['dataval']) ? $tag['dataval'] : '';
        $dict = isset($tag['dict']) ? $tag['dict'] : '';
        $css = isset($tag['css']) ? $tag['css'] : '';
        $name = $tag['name'];
        $html="<select id='$name' $css name='$name'>";
        if(!empty($defaultTxt))
        {
            $html.="<option value='".htmlspecialchars($defaultVal)."'>".htmlspecialchars($defaultTxt)."</option>";
        }
        if($tag['type']=='data')
        {
            $data = $this->autoBuildVar($tag['data']);
            $html .= '<?php ';
            $html .= 'foreach('.$data.' as $k=>$v): ;?>';
            $html .= '<option value="{$v["'.$datakey.'"]}" ';
            if(!empty($tag['selectVal']))
            {
                $selectVal = $this->autoBuildVar($tag['selectVal']);
                    $html .= <<<EOF
                    <?php
                        if(is_array($selectVal))
                        {
                            if(in_array(\$v['$datakey'],$selectVal))
                            {
                                echo 'selected="selected"';
                            }
                        }
                        else
                        {
                            if(\$v['$datakey']==$selectVal)
                            {
                                echo 'selected="selected"';
                            }
                        }
                    ?>
EOF;
            }
            $html .= '>';
            $html .= '{$v["'.$dataval.'"]}</option>';
            $html .= '<?php endforeach; ?>';
            $html .= '</select>';
            return $html;
        }
        if($tag['type']=='dict')
        {
            $data=config('dict')[$dict];
        }
        elseif($tag['type']=='codetype')
        {
            $sql="select c.id,c.codename from code c inner join codetype t on c.type_id=t.id where t.typekey='{$tag['codetype']}' order by c.sortno asc ";
            $rst=Db::query($sql);
            foreach ($rst as $k => $v) {
                $data[$v['id']]=$v['codename'];
            }
        }
        foreach ($data as $k => $v) {
            $html .= '<option value="'.$k.'" ';
               if(!empty($tag['selectVal']))
                {
                   $selectVal = $this->autoBuildVar($tag['selectVal']);
                       $html .= <<<EOF
                       <?php
                           if(is_array($selectVal))
                           {
                               if(in_array('$k',$selectVal))
                               {
                                   echo 'selected="selected"';
                               }
                           }
                           else
                           {
                               if('$k'==$selectVal)
                               {
                                   echo 'selected="selected"';
                               }
                           }
                       ?>
EOF;
                }
            $html .= '>';
            $html .= $v.'</option>';
        }
        $html .= '</select>';
        return $html;
    }

    public function tagArticle($tag, $content)
    {
        $name = $tag['name']; 
        $limit= isset($tag['limit']) ? $tag['limit'] : 0;
        $order = isset($tag['orderby']) ? $tag['orderby'] : '';
        $field = isset($tag['field']) ? $tag['field'] : '';
                $parse = <<<EOF
                <?php
                        \$list = think\Db::name('article');

                        if("$field" != ''){
                            \$list=\$list->field("$field");
                        }

                        if("$order" != ''){
                            \$list=\$list->order("$order");
                        }

                        if($limit != 0){
                            \$list=\$list->limit($limit);
                        }

                        \$list=\$list->select();

                        \$__LIST__ = \$list;
                ?>
EOF;
        $parse .= '{volist name="__LIST__" id="' . $name . '"}';
        $parse .= $content;
        $parse .= '{/volist}';
        return $parse;
    }

}