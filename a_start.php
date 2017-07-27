<?php
set_time_limit(5);
ini_set("display_errors", "On");
error_reporting(E_ALL | E_STRICT);

/**
 * 基于a-start的最短距离计算demo
 * A*寻路
 * liukelin
 * 2017.7.23 
 */

// $_REQUEST = array(
//         'map_width'=>5,
//         'map_height'=>5,
//         'location_hindrance'=>'|0-1',
//         'location_begin'=>'0-0',
//         'location_end'=>'0-3',
//         'is_agree'=>1
//     );


// 接受参数
$map_width = (int)$_REQUEST['map_width']; 
$map_height = (int)$_REQUEST['map_height']; 
$location_hindrance = $_REQUEST['location_hindrance']; // 障碍物坐标  |x-y|x-y
$location_begin = $_REQUEST['location_begin']; // 起点物坐标 x-y
$location_end = $_REQUEST['location_end']; // 终点坐标  x-y
$is_agree = $_REQUEST['is_agree']==1?1:0;// 是否允许斜向通过

if (!$location_begin) {
    exit(json_encode(constants(-1001)));
}
if (!$location_end) {
    exit(json_encode(constants(-1002)));
}

$location_begin = explode('-', $location_begin);
$location_end = explode('-', $location_end);
if (count($location_begin)<2) {
    exit(json_encode(constants(-1001)));
}
if (count($location_end)<2) {
    exit(json_encode(constants(-1002)));
}

// 地图大小
$map_width = $map_width;  // x
$map_height = $map_height; // y

// 是否允许障碍物边界斜向通过 
$is_agree = $is_agree; // 0/1

// 消耗 
$cost = array(10, 14); //左右, 对角 消耗值 

// 设置起始和结束坐标 
$location_begin = $location_begin;
$location_end = $location_end;

// 设置障碍物坐标 
$hindrance = array(); 
if ($location_hindrance) {
    $location_hindrance = array_filter(explode('|', $location_hindrance));
    foreach ($location_hindrance as $key => $val) {
        $hindrance[$key] = explode('-', $val);
    }
}



/**
 * 地图类接口定义
 * 必要实现
 */
interface InterfaceMaps {
    // 创建地图
    public function createMaps(); 
    // 获取地图大小
    public function mapsSize();
    // 判断坐标是否出界
    public function isOutMap($location);  
    // 判断坐标属性 1起点/9终点/-1障碍/0正常   
    public function locationType($location);  
    // 获取地图
    public function getMaps();
}  

/**
 * 地图类
 * func 创建地图
 * func 判断坐标是否出界
 * func 判断坐标属性 起点/终点/障碍/正常
 * 
 */
class Maps implements InterfaceMaps {

    public $width;  // [地图宽]
    public $height; // [地图高]
    public $hindrance; // [障碍物坐标集合]
    public $begin; // [起点坐标]
    public $end; // [终点坐标]
    private $maps; // [maps]
    
    /**
     * [__construct description]
     * @param  [int] $width     地图宽
     * @param  [int] $height    地图高
     * @param  [array] $hindrance 障碍物坐标集合
     * @param  [array] $begin 起点
     * @param  [array] $end 终点
     */
    public function __construct($width, $height, $hindrance, $begin, $end) {
        $this->width = $width; 
        $this->height = $height;
        $this->hindrance = $hindrance;
        $this->begin = $begin;
        $this->end = $end;
        $this->createMaps(); // 初始化地图
    }

    /** 
     *  
     * [createMap 创建地图 并标记出障碍物]
     * @return [type] 地图坐标集合 X Y status 0可通过 -1障碍 1起点 9终点      
     * array(
     *     [0]=> array(
     *         [0] => array("x" => 0 , "y" => 0 , "status" => 0),
     *         ... 
     *     ),
     *     [1] => array(
     *         [0] => array("x" => 1 , "y" => 0 , "status" => -1),
     *         ...
     *     ),
     *     ...
     * }
     * 
     */
    public function createMaps() {
        
        $width = $this->width;
        $height = $this->height;
        $hindrance = $this->hindrance;
        $begin_x = ($this->begin)[0]; 
        $begin_y = ($this->begin)[1]; 
        $end_x = ($this->end)[0]; 
        $end_y = ($this->end)[1];

        $map = array(); 
        for($i=0; $i<$height; $i++) {

            for($j=0; $j<$width; $j++) {

                $map[$j][$i]['x'] = $j; 
                $map[$j][$i]['y'] = $i;

                $map[$j][$i]['status'] = 0;

                // 标记障碍物 
                if ($this->isInHindrance(array($j, $i))) { 
                    $map[$j][$i]['status'] = -1; 
                }

                // 标记起点 
                if ($j==$begin_x && $i==$begin_y) { 
                    $map[$j][$i]['status'] = 1; 
                }

                // 标记终点 
                if ($j==$end_x && $i==$end_y) { 
                    $map[$j][$i]['status'] = 9; 
                }
            } 
        }
        $this->maps = $map;
        return $map; 
    }

    /**
     * [getMaps 获取地图]
     * @return [array] [description]
     */
    public function getMaps(){
        return $this->maps;
    }

    /**
     * [mapsSize 获取地图大小]
     * @return [array] [array([width],[height])]
     */
    public function mapsSize(){
        return array($this->width, $this->height);
    }

    /**
     * [isOutMap 判断坐标是否越界]
     * @param  [array]  $location [坐标]
     * @return boolean           [description]
     */
    public function isOutMap($location){
        $map_width = $this->width;
        $map_height = $this->height;

        $x = $location[0];
        $y = $location[1];
        if($x < 0 || $y < 0 || $x>($map_width - 1) || $y > ($map_height - 1)) { 
            return true; 
        } 
        return false; 
    }
    
    /**
     * [locationType 判断坐标属性 起点/终点/障碍]
     * @param  [array] $location [坐标]
     * @return [int]           [0可通过 -1障碍 1起点 9终点]
     */
    public function locationType($location){
        $maps = $this->maps;
        $x = $location[0];
        $y = $location[1];
        return $maps[$x][$y]['status'];
    }

    /**
     * 判断坐标是否在障碍物坐标列表
     * [isInHindrance description]
     * @param  (array) (location) (坐标) 
     * @return boolean           [description]
     */
    public function isInHindrance($location) {
        $hindrance = $this->hindrance;
        $x = $location[0];
        $y = $location[1];
        foreach($hindrance as $key=>$val) { 
            if($val[0]==$x && $val[1]==$y) { 
                return true; 
            } 
        } 
        return false; 
    }
}




/**
 * 寻路类接口定义
 * 必要实现
 */
interface InterfacePaths {
    // 创建路径
    public function createPath();  
    // 获取路径
    public function getPath();  
    // 调试输出地图路径
    public function drawMapsPath();
    // 获取路径总长
    public function getPathLength();
}  

/**
 * a * 寻路类
 */
class Paths implements InterfacePaths {

    public $begin; // [起点坐标]
    public $end;   // [终点坐标]
    // public $hindrance;         // [障碍物坐标集合]
    public $is_agree = 1;         // 是否允许斜向
    public $cost = array(10, 14); // 正向、斜向 消耗值
    public $map_width;            // 地图宽
    public $map_height;           // 地图高
    public $mapsObj;              // 地图类对象
    public $maps;                 // [全地图]
    private $path;                // 路径

    /**
     * [__construct description]
     * @param [class Object] $mapsObj [地图类对象]
     * @param [array] $begin   [起点]
     * @param [array] $end     [终点]
     */
    public function __construct($mapsObj, $begin, $end) {
        $this->mapsObj = $mapsObj;
        $this->maps = $mapsObj->getMaps(); 
        $this->map_width = $mapsObj->width;
        $this->map_height = $mapsObj->height;

        $this->begin = $begin;
        $this->end = $end;
        // $this->hindrance = $hindrance;
        $this->createPath(); // 初始化路径
    }

    /**
     * [a_start 生成路径]
     * @return [array]  [路径集合]
     */
    public function createPath(){

        $maps = $this->maps;
        $begin = $this->begin;
        $end = $this->end;
        $map_width = $this->map_width;
        $map_height = $this->map_height;
        $is_agree = $this->is_agree;
        $cost = $this->cost;
        // $hindrance = $this->hindrance;

        $begin_x = $begin[0];
        $begin_y = $begin[1];
        $end_x = $end[0];
        $end_y = $end[1];


        // 初始化 
        $open_arr = array();  // 开启坐标集合
        $close_arr = array(); // 关闭坐标集合
        $path = array();      // 路径坐标集合

        // 把起始格添加到开启列表 
        $open_arr[0]['x'] = $begin_x; 
        $open_arr[0]['y'] = $begin_y; 
        $open_arr[0]['G'] = 0; 
        $open_arr[0]['H'] = $this->getH($begin_x,$begin_y,$end_x,$end_y); 
        $open_arr[0]['F'] = $open_arr[0]['H']; 
        $open_arr[0]['p_node'] = array('x'=>$begin_x, 'y'=>$begin_y);

        // 穷举
        while(1) {

            // 取得最小F值的格子作为当前格 
            $cur_node = $this->getLowestFNode($open_arr);

            // 从开启列表中删除此格子 
            $open_arr = $this->removeNode($open_arr, $cur_node['x'], $cur_node['y']);

            // 将当前点加入到关闭列表 
            $close_arr[] = $cur_node;

            //取周边节点
            $round_list = $this->getRoundNode($cur_node['x'], $cur_node['y']); 
            $round_num = count($round_list);
            // var_dump($round_list);die();
            
            for($i=0; $i<$round_num; $i++) {
                //所有周边节点中第i和节点的x,y
                $pos_arr = $round_list[$i];

                // 跳过已在关闭列表中的格子，障碍格子和 夹角转角格子 
                if( $this->mapsObj->isOutMap(array($pos_arr[0], $pos_arr[1]))
                    ||  $this->isNodeClose($close_arr, $pos_arr[0], $pos_arr[1]) 
                    ||  $this->isHindrance($pos_arr[0], $pos_arr[1]) 
                    ||  $this->isCorner($pos_arr[0], $pos_arr[1], $cur_node['x'], $cur_node['y'])
                ){ 
                    continue; 
                }

                $new_g =  $this->getG($pos_arr[0],$pos_arr[1],$cur_node['x'],$cur_node['y']); 
                $total_g = $new_g + $cur_node['G'];

                // 如果节点已在开启列表中，重新计算一下G值 ，否则返回false
                $rs_open =  $this->isNodeOpen($open_arr, $pos_arr[0], $pos_arr[1]); 
                if(!$rs_open) { 

                    //不在opne列表
                    $arr[$i] = array(); 
                    $arr[$i]['x'] = $pos_arr[0]; 
                    $arr[$i]['y'] = $pos_arr[1]; 
                    $arr[$i]['G'] = $total_g; 
                    $arr[$i]['H'] = $this->getH($pos_arr[0], $pos_arr[1], $end_x, $end_y); 
                    $arr[$i]['F'] = $arr[$i]['G'] + $arr[$i]['H']; 
                    $arr[$i]['p_node']['x'] = $cur_node['x']; 
                    $arr[$i]['p_node']['y'] = $cur_node['y']; 
                    $open_arr[] = $arr[$i]; 

                
                } else { 
                    //在opne列表 G值重估
                    $k = $rs_open['index']; 
                    if($total_g < $open_arr[$k]['G']) {

                        $open_arr[$k]['G'] = $open_arr[$k]['G']; 
                        $open_arr[$k]['F'] = $total_g + $open_arr[$k]['H']; 
                        $open_arr[$k]['p_node']['x'] = $cur_node['x']; 
                        $open_arr[$k]['p_node']['y'] = $cur_node['y']; 
                    
                    } else { 
                        $total_g = $open_arr[$k]['G']; 
                    } 
                } 
            }

            // 到达终点
            if($cur_node['x'] == $end_x && $cur_node['y'] == $end_y) {
                
                $path =  $this->drawPath($close_arr); 
                if(!empty($path)) {
                    break; 
                } 
            }

            if(empty($open_arr)) {
                break; 
            } 
        }

        // print_r($close_arr);
        $this->path = $path;
        return $path;
    }

    /**
     * [getPath 获取路径]
     * @return [array] [路径集合]
     */
    public function getPath(){
        return $this->path;
    }

    /**
     * [getPathLength 获取路径总长]
     * @return [int] [长度]
     */
    public function getPathLength(){
        return count($this->path);
    }

    /**
     * 回溯路径 
     * @param  [array] $close_arr 关闭坐标集合
     * @return [array]            路径坐标集合
     * Array
     *   (
     *       [0] => Array
     *           (
     *               [x] => 0
     *               [y] => 1
     *           )
     *       ...
     *   )
     */
    function drawPath($close_arr) { 
        $begin_x = ($this->begin)[0];
        $begin_y = ($this->begin)[1];

        $path = array(); 
        $p = $close_arr[count($close_arr)-1]['p_node']; 

        $path[] = $p; 
        while(1) { 

            for($i=0; $i<count($close_arr); $i++) { 

                if($close_arr[$i]['x']==$p['x'] && $close_arr[$i]['y']==$p['y']) { 
                    $p = $close_arr[$i]['p_node']; 
                    $path[] = $p; 
                } 
            }

            if($p['x']==$begin_x && $p['y']==$begin_y) { 
                break; 
            } 
        }
        return $path; 
    }

    /**
     * [getRoundNode 取坐标周边节点 （包括斜向周边）] 
     *  需要切换 4方向 8方向
     * @param  [int] $x [X坐标]
     * @param  [int] $y [y坐标]
     * @return [array]    [坐标集合]
     */
    function getRoundNode($x, $y) { 

        $round_arr = array(
                array($x-1,$y),    //左
                array($x,$y-1),    //下
                array($x,$y+1),    //上
                array($x+1,$y),    //右
            );

        if ($this->is_agree==1) {
            $round_arr[] = array($x-1,$y-1);  //左下
            $round_arr[] = array($x-1,$y+1);  //左上
            $round_arr[] = array($x+1,$y-1);  //右下
            $round_arr[] = array($x+1,$y+1);  //右上
        }

        return $round_arr; 
    }


    /** 
     * 判断是否超出地图 
     * 
     * @param (类型) (x轴)
     * @param (类型) (y轴) 
     * @param (类型) (地图宽) 
     * @param (类型) (地图高)
     */ 
    // function isOutMap($x, $y, $map_width, $map_height) { 
    //     if($x < 0 || $y < 0 || $x>($map_width - 1) || $y > ($map_height - 1)) { 
    //         return true; 
    //     }
    //     return false; 
    // }

    /**
     * 判断是否是转角点 
     * @param  [type]  $x     [所有周边节点中第i和节点的x,y]
     * @param  [type]  $y     []
     * @param  [type]  $cur_x [前节点的 cur_x,cur_y]
     * @param  [type]  $cur_y []
     * @return boolean        []
     */
    function isCorner($x, $y, $cur_x, $cur_y) { 
        if($x > $cur_x) { 
            if($y > $cur_y) { 
                if($this->isHindrance($x - 1, $y) || $this->isHindrance($x, $y - 1)) { 
                    return true; 
                } 
            } elseif($y < $cur_y) { 
                if($this->isHindrance($x - 1, $y) || $this->isHindrance($x, $y + 1)) { 
                    return true; 
                } 
            } 
        }

        if($x < $cur_x) { 
            if($y < $cur_y) { 
                if($this->isHindrance($x + 1, $y) || $this->isHindrance($x, $y + 1)) { 
                    return true; 
                } 
            } 
            elseif($y > $cur_y) { 
                if($this->isHindrance($x + 1, $y) || $this->isHindrance($x, $y - 1)) { 
                    return true; 
                } 
            } 
        }

        return false; 
    }

    /**
     * [removeNode 删除节点 ]
     * @param  [array] $open_arr [开启坐标集合]
     * @param  [int] $x        [x]
     * @param  [int] $y        [y]
     * @param  string $status   [description]
     * @return [type]           [description]
     */
    function removeNode($open_arr, $x, $y, $status='') { 
        foreach($open_arr as $key=>$val) { 
            if(isset($val['x']) && $val['x']==$x && isset($val['y']) && $val['y']==$y) { 
                unset($open_arr[$key]); 
            } 
        }
        return $open_arr; 
    }

    /** 
    * 计算G值 
    *  F = G + H
    *  G = 从起点A，沿着产生的路径，移动到网格上指定方格的移动耗费。
    * @param (int) (begin_x) (终点x) 
    * @param (int) (begin_y) (终点y) 
    * @param (int) (parent_x) (当前坐标x) 
    * @param (int) (parent_y) (当前坐标y) 
    */ 
    function getG($begin_x, $begin_y, $parent_x, $parent_y) { 
        $cost_1 = ($this->cost)[0];
        $cost_2 = ($this->cost)[1]; 
        if(($begin_x - $parent_x) * ($begin_y - $parent_y) != 0) { 
            return $cost_2; 
        } else { 
            return $cost_1; 
        } 
    }

    /** 
    * 计算H值   H = 从网格上那个方格移动到终点B的预估移动耗费。
    * F = G + H
    * @param (int) (begin_x) (终点x) 
    * @param (int) (begin_y) (终点y) 
    * @param (int) (parent_x) (当前坐标x) 
    * @param (int) (parent_y) (当前坐标y) 
    */ 
    function getH($begin_x, $begin_y, $end_x, $end_y, $cost=10) { 
        $h_cost = abs(($end_x - $begin_x)*$cost); 
        $v_cost = abs(($end_y - $begin_y)*$cost);
        $c=$h_cost+$v_cost;
        // echo "$begin_x, $begin_y, $end_x, $end_y, $cost^^^";
        // echo $h_cost.'---'.$v_cost.'-'.$c.'>>>';
        // die();
        return $h_cost+$v_cost; 
    }

    /**
     * [sortOpenList 对列表排序 usort函数 排序规则函数] 
     * @param  [array] $a [description]
     * @param  [array] $b [description]
     * @return [type]    [description]
     */
    function sortOpenList($a, $b) {

        if ($a['F'] == $b['F']) return 0; 
        return ($a['F'] > $b['F']) ? -1 : +1; 
    }


    /**
     * [getLowestFNode 取得最小F值的点]
     * @param  [array] $open_arr [开启坐标集合]
     * @return [array]           [坐标array]
     */
    function getLowestFNode($open_arr) { 
        usort($open_arr, array("Paths", "sortOpenList")); 
        $node = array(); 
        $i = 0; 
        foreach($open_arr as $key=>$val) { 

            if($i == 0) {
                $node = $val; 
            } else { 
                if($val['F'] <= $node['F']) { 
                    $node = $val; 
                } 
            } 
            $i++; 
        }
        return $node; 
    }


    /**
     * [isNodeClose 判断节点是否已关闭]
     * @param  [array]  $close_arr [关闭坐标集合]
     * @param  [int]  $node_x    [x]
     * @param  [int]  $node_y    [y]
     * @return boolean            [description]
     */
    function isNodeClose($close_arr, $node_x, $node_y) { 
        // global $close_arr; 
        foreach($close_arr as $key=>$val) {
            if(isset($val['x']) && $val['x'] == $node_x && isset($val['y']) && $val['y'] == $node_y) { 
                return true; 
            } 
        } 
        return false; 
    }

    /**
     * [isNodeOpen 判断节点是否已在开启列表中]
     * @param  [array]  $open_arr [开启坐标集合]
     * @param  [int]  $node_x   [x坐标]
     * @param  [int]  $node_y   [y坐标]
     * @return boolean           [description]
     */
    function isNodeOpen($open_arr, $node_x, $node_y) { 
        // global $open_arr; 
        foreach($open_arr as $key=>$val) {

            if(isset($val['x']) && $val['x'] == $node_x && isset($val['y']) && $val['y'] == $node_y) {
                $rs['index'] = $key;
                return $rs; 
            } 
        } 
        return false; 
    }

    /** 
    * 判断结点是否是障碍物 
    * 
    * @param (int) (node_x) (x坐标) 
    * @param (int) (node_y) (y坐标) 
    */ 
    function isHindrance($node_x, $node_y) { 
        // $area = $this->maps; 
        // if(isset($area[$node_x][$node_y]['status']) && $area[$node_x][$node_y]['status']==-1) { 
        //     return true; 
        // }
        if ($this->mapsObj->locationType(array($node_x, $node_y))==-1) {
            return true; 
        }
        return false;
    }

    /** 
    * 检查某结点是否在寻路路径中 
    * 
    * @param (array) ($parent_arr) (寻路路径集合) 
    * @param (int) ($x) (x坐标) 
    * @param (int) ($y) (y坐标) 
    */ 
    function isInPath($parent_arr, $x, $y) { 
        foreach($parent_arr as $key=>$val) {

            if(isset($val['x']) && $val['x'] == $x && isset($val['y']) && $val['y'] == $y) { 
                return true; 
            } 
        } 
        return false; 
    }

    /**
     * [drawMapsPath 调试输出地图 path]
     * @return [string] [html]
     */
    public function drawMapsPath(){
        $path = $this->path;
        $area = $this->maps;
        foreach ($area as $key => $value) {

            echo '<div style="width:1600px; height:30px;">';
            
            foreach ($area[$key] as $akey => $avalue) {
                
                // 默认地图坐标颜色
                $bgcolor = 'background-color: #cdd;';
                
                //障碍物颜色
                if ($avalue['status']=='-1') {
                    $bgcolor = 'background-color: #cad;';
                }

                //轨迹高亮
                foreach ($path as $pkey => $pvalue) {

                    if ($pvalue['x']==$avalue['x'] && $pvalue['y']==$avalue['y']) {
                        $bgcolor = ' background-color: green; ';
                    }
                }

                echo '<span style="width:80px; height:30px; '.$bgcolor .'line-height:30px; display: block; float:left; padding-right: 0px;">'.
                ($avalue['x']).'-'.$avalue['y'].'-('.$avalue['status'].')  </span>';
            }
            echo '</div>';
            echo '<br>';
        }
        // print_r($path);//$path里存放的就是寻路的结果路径
    }

}

function constants($code){
    $CONSTANTS = array(
                0=>'success',
                -1001=>'请选择起点',
                -1002=>'请选择终点'
            );
    return array(
                'c'=>$code,
                'msg'=>isset($CONSTANTS[$code])?$CONSTANTS[$code]:$CONSTANTS[0],
            );
}



// 生成地图 对象 并标记障碍物、起点、终点
$mapsObj = new Maps($map_width, $map_height, $hindrance, $location_begin, $location_end);

// 重新设置
// $mapsObj->width = $map_width; 
// $mapsObj->height = $map_height;
// $mapsObj->hindrance = $hindrance;
// $mapsObj->begin = $location_begin;
// $mapsObj->end = $location_end;
// $mapsObj->createMaps(); // 重新初始化地图
$maps = $mapsObj->getMaps(); // 获取地图


// 生成路径
$pathObj = new Paths($mapsObj, $location_begin, $location_end);

// 重新配置
$pathObj->is_agree = $is_agree; // 配置是否斜面
$pathObj->cost = array(10, 14); // 配置正向、斜向 消耗值
$pathObj->createPath(); // 初始化路径, 修改了配置需要重新初始化路径

$path = $pathObj->getPath(); //获取路径


//返回json
$ret = constants(0);
$ret['path'] = $path;
exit(json_encode($ret));



