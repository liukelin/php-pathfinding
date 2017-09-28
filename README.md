一个基于a-start的最小路径demo.
    
    网上找到使用php实现的案例做了简单修改，因为JS不是太熟练，所有暂时没有改成JS版本。

    将table每个td座位一个坐标，规定起始坐标、终点坐标、和 设置的障碍物坐标。

基本原理
    
    1、寻路开始，获取当前路径节点的周边所有相邻节点。（8个相邻方位）
    2、将坐标周边记录 -> 开启坐标集合（可通过坐标 $open_arr)  
    3、对当前坐标周边所有坐标集合（$open_arr） 计算： 
        G = 从起点A，沿着产生的路径，移动到网格上指定方格的移动耗费。
        H = 从网格上那个方格移动到终点B的预估移动耗费。
        F = G + H
        (这个可以粗略理解为：根据 "终点坐标" 相对当前坐标 方位，计算出最符合终点方向 所在的那个周边相邻节点。)
    4、将 F值最小的作为 使用的路径坐标 ->路径坐标集合(path) 、其余周边坐标 ->关闭坐标集合（不可通过坐标 $close_arr）
    5、最终到达终点坐标，结束.

## 目录结构

~~~

├─service/         处理方法
│  ├─api.php       web接口
│  ├─Maps.php      绘制地图类
│  └─Paths.php     寻路生成类
└─index.html       页面

~~~


demo地址：

    [https://demo.liukelin.top/php-pathfinding/a_start.html](https://demo.liukelin.top/php-pathfinding)<br />
