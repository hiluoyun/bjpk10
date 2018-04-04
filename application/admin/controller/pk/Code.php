<?php

namespace app\admin\controller\pk;

use app\common\controller\Backend;
use fast\Http;
use app\admin\model\PkCode;

/**
 * 原始数据
 *
 * @icon fa fa-circle-o
 */
class Code extends Backend
{
    
    /**
     * PkCode模型对象
     */
    protected $model = null;
    protected $last_dx_status = [];
    protected $last_ds_status = [];
    protected $dx_repeat = [];
    protected $ds_repeat = [];
    protected $dx_str = "";
    protected $ds_str = "";

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('PkCode');

    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    public function test()
    {
//        return 'hello world';
        $date = date('Y-m-d');
        $time = strtotime('2018-04-01 09:08:00');
        return $time;
    }



    public function spider_data($date='')
    {
        $pos_key = ['one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight',
            'nine', 'ten'];
        $url = 'http://www.8kai8.com/history/detailgeth';
        if($date == ''){
            $date = date('Y-m-d', time());
        }
        $param = ['lotid'=>1028, 'size'=>500, 'time'=>$date];
        $result = Http::sendRequest($url, $param, 'POST');
        $result = json_decode($result['msg'], true);
        if($result['success'] == 'Y'){
            $result = $result['content'];
            if(!$result){
                return json(['code'=>500, 'msg'=>'插入失败', 'data'=>json_encode($result)]);
            }
            foreach($result as $item){
                $data = [];
                $data['period'] = $item['speriod'];
                $open_code = $item['sopencode'];
                $data['open_time'] = $item['dopen_time'];
//                dump($data['open_time']);exit;
//                $data['open_time'] = date('Y-m-d H:i:s', time());
                $open_code = explode(',', $open_code);
                $i = 0;
                foreach($open_code as $row){
                    $data[$pos_key[$i]] = $row;
                    $i++;
                }
//                dump($data);exit;
                $this->save_pk_data($data);

            }
            return json(['code'=>200, 'msg'=>'插入成功']);
        }else{
            sleep(1);
            $this->spider_data($date);
        }
    }

    public function save_pk_data($data)
    {
        $PkCode = new PkCode();
        $check = PkCode::get(['period'=>$data['period']]);
        if (!$check){
            $res = $PkCode->data($data)->isUpdate(false)->save();
            if ($res){
                return true;
            }
        }
        return false;
    }


    public function Async_data($start='', $end='')
    {
        $url = 'http://localhost/bjpk10/public/index.php/admin/pk/code/spider_data';
        $start = explode('-', $start);
        $end = explode('-', $end);
        $sy = intval($start[0]);
        $sm = intval($start[1]);
        $sd = intval($start[2]);
        $ey = intval($end[0]);
        $em = intval($end[1]);
        $ed = intval($end[2]);
        for($i = $sy; $i<= $ey; $i++){
            for($j = $sm; $j<=$em; $j++){
                for($k = $sd; $k<=$ed; $k++){
                    $year = $i < 10 ? '0'.$i : $i;
                    $month = $j < 10 ? '0'.$j : $j;
                    $day = $k < 10 ? '0'.$k : $k;
                    $date = $year . '-' . $month . '-' . $day;
                    $this->spider_data($date);
//                    Http::sendAsyncRequest($url, ['date'=>$date], 'GET');
                }
            }
        }
        return 'ok';
    }

    /**
     * @param string $start
     * @param string $end
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function count_data($start='', $end='')
    {
        $pos_key = ['one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight',
            'nine', 'ten'];
        foreach($pos_key as $index)
        {
            $this->dx_repeat[$index] = 0;
            $this->ds_repeat[$index] = 0;
        }
        $datetime = self::parse_datetime($start, $end);
        $PkCode = new PkCode();
        foreach($datetime as $item){
            $begin = strtotime($item);
            $over = strtotime( '+1 day', $begin) - 1;
            $begin = date('Y-m-d H:i:s', $begin);
            $over = date('Y-m-d H:i:s', $over);
            $res = $PkCode->where('open_time', '>=', $begin)
                ->where('open_time', '<=', $over)
                ->order('open_time asc')
                ->select();

            if (!$res) continue;
            $last_period = null;
            $period = null;

            $len = count($res);
            for($i=0; $i<$len; $i++){
                $item = $res[$i];
                $period = $item['period'];
                $open_time = $item['open_time'];
                if ($last_period == null){
                    $last_period = $period;
                }else if($period - $last_period != 1){
                    $this->save_lost_data($open_time, $last_period, $period);
                }
                $this->count_item_detail($item);
            }
        }
    }

    /**
     * @param $data
     */
    public function count_item_detail($data)
    {
        $pos_key = ['one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight',
            'nine', 'ten'];
        foreach($pos_key as $item){
            $number = intval($data[$item]);
            $dx_status = $number > 5 ? '大' : '小';
            if(!array_key_exists($item, $this->last_dx_status)){
                $this->last_dx_status[$item] = $dx_status;
            }else if($this->last_dx_status[$item] != $dx_status){
                $this->last_dx_status[$item] = $dx_status;

            }else{
                $this->dx_str .= $data['period'] . '(' . $number . ')';
            }
        }
    }

    public function save_lost_data($open_time, $last, $now)
    {

    }

    public function save_dx_data($period, $open_time, $repeat_num)
    {

    }

    protected static function parse_datetime($start, $end)
    {
        $start = explode('-', $start);
        $end = explode('-', $end);
        $sy = intval($start[0]);
        $sm = intval($start[1]);
        $sd = intval($start[2]);
        $ey = intval($end[0]);
        $em = intval($end[1]);
        $ed = intval($end[2]);
        $data = [];
        for($i = $sy; $i<= $ey; $i++) {
            for ($j = $sm; $j <= $em; $j++) {
                for ($k = $sd; $k <= $ed; $k++) {
                    $year = $i < 10 ? '0'.$i : $i;
                    $month = $j < 10 ? '0'.$j : $j;
                    $day = $k < 10 ? '0'.$k : $k;
                    $date = $year . '-' . $month . '-' . $day;
                    $data[] = $date;
                }
            }
        }
        return $data;
    }

    public function list_data()
    {
        $url = 'http://localhost/bjpk10/public/index.php/admin/pk/code/spider_data?date=2018-04-04';

        Http::sendAsyncRequest($url, [], 'POST');
    }





}
