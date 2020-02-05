<?php

namespace app\admin\controller\hospital;

use app\common\controller\Backend;
use fast\Safe;
use think\Db;
/**
 * 医院管理
 *
 * @icon fa fa-user
 */
class Hospital extends Backend
{

    protected $relationSearch = true;


    /**
     * @var \app\admin\model\Hospital
     */
    protected $model = null;
	protected $searchFields = 'id,name';//指定实时搜索的字段
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Hospital');
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
		
			//查询其他代理选择过的医院，然后返回剩余未选择的
			$uid=$this->request->request('uid');
			$gid=$this->request->request('gid');
			if($gid==2){
				$res=Db::name('AuthHospital')->where('type',1)->select();		
				foreach($res as $key=>$val){
					$groupid=Db::name('AuthGroupAccess')->where('uid',$val['uid'])->value('group_id');
					if($groupid!=2){
						unset($res[$key]);
					}
				}
				$res=array_column($res,'hospital_id');		
				if($res){
					$condition['hospital.id']=array('not in',$res);
				}
			}else{
				$condition['hospital.id']=array('gt',0);
			}
			
		
			
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
			if(isset($uid)){
				$total = $this->model->with('group')->where($where)->where($condition)->order($sort, $order)->count();
				$list = $this->model->with('group')->where($where)->where($condition)->order($sort, $order)->limit($offset, $limit)->select();
			}else{
				$total = $this->model->with('group')->where($where)->order($sort, $order)->count();
				$list = $this->model->with('group')->where($where)->order($sort, $order)->limit($offset, $limit)->select();
			}
			
			foreach($list as $key=>$val){
				$list[$key]['consignee_id'] = $this->model->where('id',$val['consignee_id'])->value('name');
				if(!$list[$key]['consignee_id']){
					$list[$key]['consignee_id']='';
				}
				$list[$key]['assigned_id'] = Db::name('admin')->where('id',$val['assigned_id'])->value('nickname');
				if(!$list[$key]['assigned_id']){
					$list[$key]['assigned_id']='';
				}
				$list[$key]['change_id'] = $this->model->where('id',$val['change_id'])->value('name');
				if(!$list[$key]['change_id']){
					$list[$key]['change_id']='';
				}
				$list[$key]['frozen_id'] = $this->model->where('id',$val['frozen_id'])->value('name');
				if(!$list[$key]['frozen_id']){
					$list[$key]['frozen_id']='';
				}
				//解密
				if($val['mobile']){
					$list[$key]['mobile']=Safe::instance()->decode($val['mobile']);
				}else{
					$list[$key]['mobile']='';
				}
			}
        
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {		 
        $row = $this->model->get($ids);
		
        if (!$row){
			$this->error(__('No Results were found'));
		}
        $list=Db::name('hospital')->where('status','normal')->where('id','neq',$row['id'])->field('id,name')->select();//发起会诊默认承接方
        $names=Db::name('group_hospital')->where('group_id',5)->where('hospital_id',$row['id'])->column('uid');//承接时默认接诊专家
        if($names){
            foreach ($names as $key => $value) {
                $namess[$key]['id']=$value;
                $namess[$key]['nickname']=Db::name('admin')->where('id',$value)->value('nickname');
            }
            $this->view->assign('namess',$namess);
        }else{
			//return false;
		}
		
		$audit=Db::name('group_hospital')->where('group_id',9)->where('hospital_id',$row['id'])->column('uid');//审核专家
        if($audit){
            foreach ($audit as $key => $value) {
                $audits[$key]['id']=$value;
                $audits[$key]['nickname']=Db::name('admin')->where('id',$value)->value('nickname');
            }
            $this->view->assign('audits',$audits);
        }else{
			//return false;
		}
		
		$director=Db::name('group_hospital')->where('group_id',12)->where('hospital_id',$row['id'])->column('uid');//默认科主任
        if($director){
            foreach ($director as $key => $value) {
                $directors[$key]['id']=$value;
                $directors[$key]['nickname']=Db::name('admin')->where('id',$value)->value('nickname');
            }
            $this->view->assign('directors',$directors);
        }else{
			//return false;
		}
		
		$last=Db::name('group_hospital')->where('group_id',11)->where('hospital_id',$row['id'])->column('uid');//终审专家
        if($last){
            foreach ($last as $key => $value) {
                $lasts[$key]['id']=$value;
                $lasts[$key]['nickname']=Db::name('admin')->where('id',$value)->value('nickname');
            }
            $this->view->assign('lasts',$lasts);
        }else{
			//return false;
		}
        $this->view->assign('list',$list);
        
        return parent::edit($ids);
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isAjax()){
            $params = $this->request->post("row/a");
			if($params['mobile']){
				$params['mobile']=Safe::instance()->encode($params['mobile']);
			}
						
            $res=$this->model->save($params);
            $this->success('添加成功');
        }
        return $this->view->fetch();
    }
	
	public function change(){
		$data = $this->request->post("");
		$list=Db::name('hospital')->where('status','normal')->where('id','neq',$data['id'])->where('id','neq',$data['hospital_id'])->field('id,name')->select();
		$this->success('返回成功','',$list);
	}
	
	public function select()
    {
        $uid=$this->request->request('uid');
		$gid=$this->request->request('gid');
		$type=$this->request->request('type');
		$this->assignconfig('uid',['uid'=>$uid]);
		$this->assignconfig('gid',['gid'=>$gid]);
		$this->view->assign('type',$type);
        return $this->view->fetch();
    }
	
	/**
     * 导入
     */
    public function import()
    {
        $file = $this->request->request('file');
        if (!$file) {
            $this->error(__('Parameter %s can not be empty', 'file'));
        }
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        if (!is_file($filePath)) {
            $this->error(__('No results were found'));
        }
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new \PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                $PHPReader = new \PHPExcel_Reader_CSV();
                if (!$PHPReader->canRead($filePath)) {
                    $this->error(__('Unknown data format'));
                }
            }
        }

        //导入文件首行类型,默认是注释,如果需要使用字段名称请使用name
        $importHeadType = isset($this->importHeadType) ? $this->importHeadType : 'comment';

        $table = $this->model->getQuery()->getTable();
        $database = \think\Config::get('database.database');
        $fieldArr = [];
        $list = db()->query("SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?", [$table, $database]);
        foreach ($list as $k => $v) {
            if ($importHeadType == 'comment') {
                $fieldArr[$v['COLUMN_COMMENT']] = $v['COLUMN_NAME'];
            } else {
                $fieldArr[$v['COLUMN_NAME']] = $v['COLUMN_NAME'];
            }
        }

        $PHPExcel = $PHPReader->load($filePath); //加载文件
        $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
        $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
        $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
        $maxColumnNumber = \PHPExcel_Cell::columnIndexFromString($allColumn);
        for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
            for ($currentColumn = 0; $currentColumn < $maxColumnNumber; $currentColumn++) {
                $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                $fields[] = $val;
            }
        }
        $insert = [];
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $values = [];
            for ($currentColumn = 0; $currentColumn < $maxColumnNumber; $currentColumn++) {
                $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                $values[] = is_null($val) ? '' : $val;
            }
            $row = [];
            $temp = array_combine($fields, $values);
			if($temp['手机号码']){
				$temp['手机号码']=Safe::instance()->encode($temp['手机号码']);
			}
			$temp['是否预约']='normal';
			$temp['是否禁用']='normal';
            foreach ($temp as $k => $v) {
                if (isset($fieldArr[$k]) && $k !== '') {
                    $row[$fieldArr[$k]] = $v;
                }
            }
            if ($row) {
                $insert[] = $row;
            }
        }
        if (!$insert) {
            $this->error(__('No rows were updated'));
        }
        try {
            $this->model->saveAll($insert);
        } catch (\think\exception\PDOException $exception) {
            $this->error($exception->getMessage());
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success();
    }
	
}
