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
//        $date = date('Y-m-d');
//        $time = strtotime('2018-04-01 09:08:00');
//        $data = self::parse_datetime('2018-04-05', '2018-04-11');
//        return json($data);
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


    public function list_data()
    {
        $url = 'http://localhost/bjpk10/public/index.php/admin/pk/code/spider_data?date=2018-04-04';

        Http::sendAsyncRequest($url, [], 'POST');
    }






}
