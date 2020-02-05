<?php

namespace app\admin\model;

use app\common\model\MoneyLog;
use think\Model;

class Consultation extends Model
{

    // 表名
    protected $name = 'consultation';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    /*protected $append = [
        'prevtime_text',
        'logintime_text',
        'jointime_text'
    ];*/

	public function getOriginData()
    {
        return $this->origin;
    }
	
	public function group()
    {
        return $this->belongsTo('Hospital', 'hospital_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

}
