<?php
/**
 * 绘制地图类
 * liukelin
 */


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