<?php

namespace app\admin\model;

use think\Model;

class Hospital extends Model
{

    // 表名
    protected $name = 'hospital';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';


    public function getOriginData()
    {
        return $this->origin;
    }
	
	public function getStatusList()
    {
        return ['normal' => __('Normal'), 'hidden' => __('Hidden')];
    }

	public function group()
    {
        return $this->belongsTo('Admin', 'uid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
