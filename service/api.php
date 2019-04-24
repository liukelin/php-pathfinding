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

// 起始和结束坐标 
$location_begin = $location_begin;
$location_end = $location_end;

// 障碍物坐标 
$hindrance = array(); 
if ($location_hindrance) {
    $location_hindrance = array_filter(explode('|', $location_hindrance));
    foreach ($location_hindrance as $key => $val) {
        $hindrance[$key] = explode('-', $val);
    }
}

@include_once('Maps.php');
@include_once('Paths.php');
// 生成地图 对象 并标记障碍物、起点、终点
$mapsObj = new Maps($map_width, $map_height, $hindrance, $location_begin, $location_end);

// 支持重新设置
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
$pathObj->cost = $cost; // 配置正向、斜向 消耗值
$pathObj->createPath(); // 初始化路径, 修改了配置需要重新初始化路径

$path = $pathObj->getPath(); //获取路径

//返回json
$ret = constants(0);
$ret['path'] = $path;
exit(json_encode($ret));

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
