<?php

namespace app\admin\controller\pk;

use app\admin\model\PkLost;
use app\admin\model\PkCode;
use app\common\controller\Backend;
use think\Db;

/**
 * 统计数据
 *
 * @icon fa fa-circle-o
 */
class Count extends Backend
{
    
    /**
     * PkCount模型对象
     */
    protected $model = null;
    protected $last_dx_status = [];
    protected $last_ds_status = [];
    protected $dx_repeat = [];
    protected $ds_repeat = [];
    protected $dx_str = [];
    protected $ds_str = [];
    protected $rowdata = null;
    protected $rate = 0.995;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('PkCount');

    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     * @param string $start
     * @param string $end
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function count_data($start='', $end='')
    {
        $pos_key = ['one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight',
            'nine', 'ten'];

        $datetime = self::parse_datetime($start, $end);
        $PkCode = new PkCode();
        foreach($datetime as $item){
            foreach($pos_key as $index)
            {
                $this->dx_repeat[$index] = 0;
                $this->ds_repeat[$index] = 0;
                $this->last_ds_status = [];
                $this->last_dx_status = [];
                $this->dx_str = [];
                $this->ds_str = [];
            }

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
                    $last_period = $period;
                }else{
                    $last_period = $period;
                }
                $this->count_item_detail($item);
            }

            $open_time = $res[$len-1]['open_time'];
            foreach($pos_key as $index){
                $this->save_count_data($index, $this->last_ds_status[$index], $open_time,
                    $this->ds_repeat[$index], $this->ds_str[$index]);
                $this->save_count_data($index, $this->last_dx_status[$index], $open_time,
                    $this->dx_repeat[$index], $this->dx_str[$index]);
            }
        }

        return json(['code'=>200, 'msg'=>'Ok']);
    }

    /**
     * @param $data
     */
//SELECT * FROM `pk_code` WHERE open_time>'2018-03-08 00:00:00' and open_time <= '2018-03-08 59:59:59';
    public function count_item_detail($data)
    {
        $pos_key = ['one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight',
            'nine', 'ten'];
        foreach($pos_key as $item){
            $number = intval($data[$item]);
            $period = $data['period'];
            $open_time = $data['open_time'];
            $dx_status = $number > 5 ? '大' : '小';
            $ds_status = $number % 2 == 0 ? '偶' : '奇';
            //大小
            if(!array_key_exists($item, $this->last_dx_status)){
                $this->last_dx_status[$item] = $dx_status;
                $this->dx_repeat[$item] = 1;
                $this->dx_str[$item] = $period . '(' . $number . ')';
            }else if($this->last_dx_status[$item] != $dx_status){
                $this->save_count_data($item, $this->last_dx_status[$item], $open_time,
                    $this->dx_repeat[$item], $this->dx_str[$item]);
                $this->last_dx_status[$item] = $dx_status;
                $this->dx_str[$item] = $period . '(' . $number . ')';
                $this->dx_repeat[$item] = 1;
            }else{
                $this->dx_repeat[$item]++;
                $this->dx_str[$item] .= ','. $period. '(' . $number . ')';
            }
//           奇偶
            if(!array_key_exists($item, $this->last_ds_status)){
                $this->last_ds_status[$item] = $ds_status;
                $this->ds_repeat[$item] = 1;
                $this->ds_str[$item] = $period . '(' . $number . ')';
            }else if($this->last_ds_status[$item] != $ds_status){
                $this->save_count_data($item, $this->last_ds_status[$item], $open_time,
                    $this->ds_repeat[$item], $this->ds_str[$item]);
                $this->last_ds_status[$item] = $ds_status;
                $this->ds_str[$item] = $period . '(' . $number . ')';
                $this->ds_repeat[$item] = 1;
            }else{
                $this->ds_repeat[$item]++;
                $this->ds_str[$item] .= ','. $period. '(' . $number . ')';
            }
        }
    }

    public function save_lost_data($open_time, $last_period, $period)
    {
        $PkLost = new PkLost();
        $period_str = $last_period.'';
        for($i = $last_period +1; $i<$period; $i++){
            $period_str .= ','.$i;
        }
        $data = ['open_time'=>$open_time, 'period'=>$period_str];
        $PkLost->data($data)->isUpdate(false)->save();
    }

    public function save_count_data($position, $type, $open_time, $repeat_num, $raw_data)
    {
        $data = ['position'=>$position, 'type'=>$type, 'open_time'=>$open_time,
            'repeat_num'=>$repeat_num, 'raw_data'=>$raw_data];
        $this->model->data($data)->isUpdate(false)->save();
    }

    protected static function parse_datetime($start, $end)
    {
        $data = [];
        $data[] = $start;
        $int_start = strtotime($start);
        $int_end = strtotime($end);
        $days = ($int_end - $int_start) / (24*60*60);
        for($i=0; $i<$days; $i++){
            $data[] = date('Y-m-d', strtotime('+'. ($i+1) .' day', $int_start));
        }
        return $data;
    }

    public function list_repeat($start='2018-01-01', $end='2018-04-05')
    {
        $data = [];
        $sql = "select repeat_num, count(id) as total from pk_count where open_time>'"
            . $start . " 00:00:00' and open_time<'". $end ." 23:59:59'  group by repeat_num";
//        $sql = "select repeat_num, count(id) as total from pk_count group by repeat_num";
        $res = Db::query($sql);
        foreach ($res as $row) {
            $repeat_num = $row['repeat_num'];
            $data[$repeat_num] = $row['total'];
        }
        return json_encode($data);
    }

    public function calc_income($start, $end)
    {
        $income = 0;
        $lost = 0;
        $data = $this->rowdata;
        if ($data==null){
            $data = json_decode($this->list_repeat(), true);
        }
        for($i=$start; $i<=$end; $i++){
            if(array_key_exists($i, $data)){
                $income += $data[$i] * pow($this->rate, ($i+1-$start));
            }
        }
        for($i=$end+1; $i<30; $i++)
        {

            if(array_key_exists($i, $data)){
                $lost += $data[$i] * (pow(2, $end-$start+1) - 1);
            }
        }
        $income = $income - $lost;
        return $income;
    }

    public  function calc_income2($start=1, $end=3)
    {
        $income = 0;
        $lost = 0;
        $data = $this->rowdata;
        if ($data==null){
            $data = json_decode($this->list_repeat($start, $end), true);
        }
        for($i=1; $i<=3; $i++){
            if(array_key_exists($i, $data)){
                $lost += $this->calc_lost($i, $data);
                $income += $data[$i]*0.995;
            }
        }

        $income = $income - $lost;
        return $income;
    }

    public function calc_lost($start, $data)
    {
        $lost = 0;
        for($i=$start+1; $i<30; $i++)
        {
            if(array_key_exists($i, $data)){
                $lost += $data[$i];
            }
        }
        return $lost;
    }

    public function analysis($len=4)
    {
        $data = [];
        $max_key = '';
        $max_value = -999999;
        $this->rowdata = json_decode($this->list_repeat(), true);
        for($i=1; $i<=30; $i++){
            for($j=$i; $j<=30; $j++){
                if($j-$i+1<=$len){
                    $key = $i . '-' . $j;
                    $value = $this->calc_income($i, $j);
                    $data[$key] = $value;
                    if($value > $max_value){
                        $max_key = $key;
                        $max_value = $value;
                    }
                }
            }
        }
        $js = ['区间长度'=>$len, 'max_key'=>$max_key, 'max_value'=>$max_value, 'data'=>$data];
        return json($js);
    }



}
