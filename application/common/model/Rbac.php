<?php

namespace app\common\model;

use think\Model;
use think\Db;

class Rbac extends Model
{
    //当前选中的菜单项
    private $current_menu_id;
   
    // 当前权限的所有父级权限id,数组格式
    private $all_pid;

    /** 判断当前用户是否有权限对当前的action路径有权限
     * @return bool
     */
    public function check_auth()
    {
        //判断url是否需要检测
        $act_code = strtolower(request()->module() . "/" . request()->controller() . "/" . request()->action());
        $action_id = Db::name('action')->where('actcode', $act_code)->value('id');//
        session('rbac_action_id', $action_id);
        //action表中存在这个actcode，则需要判断权限，否则默认为公开的权限
        if (!empty($action_id)) {  
            //root用户拥有所有权限，不进行判断
            if ( isRoot()) return true;
            $my_action_ids = session('admin_action_ids');
            if (!in_array($action_id, $my_action_ids)) {
                //没有权限
                return false;
            }
        }
        return true;
    }



    /**
     * 初始化菜单，取得当前顶部菜单 ID，当前左侧菜单ID，当前左侧菜单的所有父ID。
     */
    public function init_menu()
    {
        $actionid = session('rbac_action_id');
        $this->current_menu_id = $this->get_current_menu_id($actionid);
        $this->all_pid = $this->get_all_pid($actionid);
    }

    
    /**
     * 获取所有上级的菜单
     * @param int $id 上级菜单的id
     * @return array $pids 数组
     *
     */
      function get_all_pid($id)
    {
        $pids = [];
        $pid = Db::name('action')->where('id', $id)->value('pid');
        if (  $pid > 0) {
            $pids[] = $pid;
            $pids = array_merge($pids, $this->get_all_pid($pid));
        }else if($pid===0){  
           //PID为0的表示第一级菜单，需要把它的ID加到入all_pid
            $pids[]=$id;
        }
        return $pids;
    }

    /**
     * @return array $sidebar 多维数组
     * 获取用户左侧栏菜单
     */
      function get_side_menu($pid)
    {
        if(isRoot())
        {
            $menu = Db::name('action')->where('ismenu', 1)->order('sortno asc')->select();
        }else
        {
            $action_ids = session('admin_action_ids');
            $menu = Db::name('action')->where('ismenu', 1)->where('id', 'in', $action_ids)->order('sortno asc')->select();
        }

        $sidebar=[];
        if($pid>-1)
         { $sidebar = $this->get_sub_menu($menu, $pid);
         }
        return $sidebar;
    }

    /**
     * @param array $menu 所有有权限的菜单
     * @param int $pid
     * @return array $arr 多维数组
     * 获取左侧每个菜单的子菜单
     */
      function get_sub_menu($menu, $pid)
    {
        $arr = [];
        foreach ($menu as $k => $v) {
            if ($v['pid'] == $pid) {
                $v['child'] = $this->get_sub_menu($menu, $v['id']);
                $arr[] = $v;
            }
        }
        return $arr;
    }

    /**
     * @param int $id 取得要作为当前显示的菜单,如新增、修改等权限，不是菜单，就会把它的父级权限（查询界面）作为当前菜单。
     * @return   int $id
     */
      function get_current_menu_id($id)
    {
        $menu = Db::name('action')->where('id', $id)->find();
        if ($menu['ismenu'] === 0) {  
            $id = $this->get_current_menu_id($menu['pid']);
        }
        return $id;
    }

    /**
     * 生成admin 用户 左侧菜单
     * @param int $pid  从哪个节点开始取子菜单
     * @return string HTML
     */
   public function get_sidebar_html($pid)
    {
        $sidebar = $this->get_side_menu($pid);
        $menu = '';
        if (!empty($sidebar)) {
            foreach ($sidebar as $k => $v) {

                if (empty($v['child'])) {
                    $css = $v['id'] == $this->current_menu_id ? ' active ' : ' ';
                    $menu .= '<li class="menu_item ' . $css . '" >
                            <a href="' . url($v['menuurl']) . '" class="'.$v['menuclass'].'" 
                            target="' . $v['menutarget'] . '" data-menuid="'.$v['id'].'" data-menutext="'. $v['menutext'] .'">
                            <i class="menu-icon fa ' . $v['iconclass'] . '"></i>
                            <span class="menu-text">' . $v['menutext'] . '</span>
                            </a>
                            <b class="arrow"></b></li>';

                } else {
                    $css = in_array($v['id'], $this->all_pid) ? ' active open ' : ' ';
                    $menu .= '<li class="menu_item ' . $css . '" >
                           <a href="#" class="dropdown-toggle '.$v['menuclass'].'">
                           <i class="menu-icon fa ' . $v['iconclass'] . '"></i>
                           <span class="menu-text">' . $v['menutext'] . '</span>
                           <b class="arrow fa fa-angle-right"></b></a>
                           <b class="arrow"></b>' . $this->get_sub_menu_html($v['child']) . '</li>';

                }
            }
        }
        return $menu;
    }

    /**
     * @param $child
     * @return string
     * 生成用户左侧菜单的子菜单
     */
    function get_sub_menu_html($child)
    {
        $cmenu = '<ul class="submenu ">';
        foreach ($child as $k => $v) {
            if (empty($v['child'])) {
                $css = $v['id'] == $this->current_menu_id ? 'active' : '';
                $iconcss=$v['iconclass']==''?'fa-caret-right':$v['iconclass'];
                $cmenu .= '<li class="menu_item ' . $css . '"  >
                        <a href="' . url($v['menuurl']) . '" class="'.$v['menuclass'].'" 
                        target="' . $v['menutarget'] . '" data-menuid="'.$v['id'].'" data-menutext="'. $v['menutext'] .'">
                        <i class="menu-icon fa '. $iconcss.'"></i>
                        <span class="menu-text">' . $v['menutext'] . '</span></a>
                        <b class="arrow"></b></li>';

            } else {
                $css = in_array($v['id'], $this->all_pid) ? ' active open ' : ' ';
                  $iconcss=$v['iconclass']==''?'fa-caret-right':$v['iconclass'];
                $cmenu .= '<li class="menu_item ' . $css . '"  >
                        <a href="#" class="dropdown-toggle '.$v['menuclass'].'">
                        <i class="menu-icon fa '.$iconcss.'"></i>
                        <span class="menu-text">' . $v['menutext'] . '</span>
                        <b class="arrow fa fa-angle-right"></b></a>
                        <b class="arrow"></b>' . $this->get_sub_menu_html($v['child']) . '</li>';
            }
        }
        $cmenu .= '</ul>';
        return $cmenu;
    }

}