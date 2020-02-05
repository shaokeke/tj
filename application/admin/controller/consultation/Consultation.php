<?php

namespace app\admin\controller\consultation;

use app\common\controller\Backend;
use fast\Safe;
use think\Db;
/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class Consultation extends Backend
{

    protected $relationSearch = true;


    /**
     * @var \app\admin\model\User
     */
    protected $model = null;
	protected $searchFields = 'id,name,group.name';//指定实时搜索的字段
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Consultation');
    }

    /**
     * 查看
     */
    public function index()
    {	
		$uid=$this->auth->id;
		if($uid!=1){
			$hid=Db::name('Admin')->where('id',$uid)->value('hospital_id');
			if($hid){
				$hid=explode(',',$hid);
				$condition['hospital_id']=array('in',$hid);
			}else{
				$condition['hospital_id']='';
			}
		}else{
			$condition['hospital_id']=array('gt',0);
		}
		
		$chapter=$this->auth->check('consultation/consultation/verify2');
		$gid=$this->auth->getGroupIds();
		$pid=Db::name('AuthGroup')->where('id',$gid[0])->value('pid');

		if($chapter){
			$is_del=1;
		}else{
			$is_del=0;
		}
				
        //设置过滤方法
		$this->request->filter(['strip_tags']);

        if ($this->request->isAjax())
        {

            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
			
			if($pid!=0){
				if($chapter){
					//中心账号下的列表
					//$total = $this->model->with('group')->where($where)->where('consultation.status','gt',2)->where($condition)->order($sort, $order)->count();
					//$list = $this->model->with('group')->where($where)->where('consultation.status','gt',2)->where($condition)->order($sort, $order)->limit($offset, $limit)->select();
					$total = $this->model->with('group')->where($where)->where('consultation.status','gt',2)->order($sort, $order)->count();
					$list = $this->model->with('group')->where($where)->where('consultation.status','gt',2)->order($sort, $order)->limit($offset, $limit)->select();
				}else{
					//代理账号下的列表
					$total = $this->model->with('group')->where($where)->where($condition)->order($sort, $order)->count();
					$list = $this->model->with('group')->where($where)->where($condition)->order($sort, $order)->limit($offset, $limit)->select();
				}
			}else{
				$total = $this->model->with('group')->where($where)->where($condition)->order($sort, $order)->count();

				$list = $this->model->with('group')->where($where)->where($condition)->order($sort, $order)->limit($offset, $limit)->select();
			}
			
			
            if($list){
				foreach ($list as $k => $v){					
					$list[$k]['sex']=($v['sex']>1)?'女':'男';
					$list[$k]['is_pay']=($v['is_pay']>1)?'已付款':'未付款';
					
					if($v['type']==2){
						$list[$k]['status']='待发布';
					}else{
						switch($v['status']){
							case 0:
								$status='待付款';break;
							case 1:
								$status='待取切片';break;
							case 2:
								$status='已取切片';break;
							case 3:
								$status='待补资料';break;
							case 4:
								$status='已初审';break;
							case 5:
								$status='增加项付费';break;
							case 7:
								$status='待审核';break;	
							default:
								$status='会诊完成';break;
						}
						$list[$k]['status']=$status;
					}
						
					if($v['report']){
						$v['report']=explode(',',$v['report']);
						$list[$k]['report']='http://'.$_SERVER['SERVER_NAME'] . DS . 'uploads' .DS . $v['report'][0];
					}else{
						$list[$k]['report']='';
					}
					if($v['other_info']){
						$v['other_info']=explode(',',$v['other_info']);
						$list[$k]['other_info']='http://'.$_SERVER['SERVER_NAME'] . DS . 'uploads' .DS . $v['other_info'][0];
					}else{
						$list[$k]['other_info']='';
					}
					if($v['supply']){
						$v['supply']=explode(',',$v['supply']);
						$list[$k]['supply']='http://'.$_SERVER['SERVER_NAME'] . DS . 'uploads' .DS . $v['supply'][0];
					}else{
						$list[$k]['supply']='';
					}
					
					//解密
					$v['card']=Safe::instance()->decode($v['card']);
					
				}
			}else{
				$list=array();
			}
			
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
		$this->view->assign('is_del',$is_del);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {	
		parent::_initialize();
		//获取某个方法，对应当前登录者是否有权限进行操作，true or false ,存入config字段，供前台js使用
		$chapter=$this->auth->check('consultation/consultation/verify2');
		$this->assignconfig('chapter', $chapter); 
		//为了JS能获取到，同时判读权限 判断是否有consultation/consultation/verify2权限
	 
        $row = $this->model->get($ids);
		
        if (!$row){
			$this->error(__('No Results were found'));
		}else{
			$report=explode(',',$row['report']);
			$other=explode(',',$row['other_info']);
			$supply=explode(',',$row['supply']);
			$row['sex']=($row['sex']==1)?'男':'女';
			$row['hospital_id']=Db::name('Hospital')->where('id',$row['hospital_id'])->value('name');//var_dump($row['hospital_id']);exit;
			$this->view->assign('report',$report);
			$this->view->assign('other',$other);
			$this->view->assign('supply',$supply);
		}
		$row['card']=Safe::instance()->decode($row['card']);
		
		
		$gid=$this->auth->getGroupIds();
		$pid=Db::name('AuthGroup')->where('id',$gid[0])->value('pid');
        $this->view->assign('pid',$pid); 
		$this->view->assign('row',$row);
		$this->view->assign('chapter',$chapter);
        $this->view->assign('list', build_select('row[hospital_id]', \app\admin\model\Hospital::column('id,name'), $row['hospital_id'], ['class' => 'form-control selectpicker']));
        if($this->auth->id==1){
			return $this->view->fetch('edit');
		}else{
			//return parent::edit($ids);
			return $this->view->fetch('edit');
		}
		//return parent::edit($ids);
		//return $this->view->fetch('edit');
    }
	
	public function verify($ids = NULL){
		$data=$this->request->request('');

		if(isset($data['extypes'])){
       		$this->model->where('id','=',$data['id'])->setField('status',7);
       		$this->success('保存成功');
		}else{
			if(isset($data['row']['h_code'])){
				if($data['row']['h_code']==$data['row']['code']){
					$res = $this->model->where('id','=',$data['row']['id'])->setField('status',2);
				}else{
					$this->error('核销码不正确');
					return false;
				}
			}
			if(isset($data['row']['express_company'])&&isset($data['row']['express_number'])){
				if($data['row']['express_company']&&$data['row']['express_number']){
					$data2['express_company']=$data['row']['express_company'];
					$data2['express_number']=$data['row']['express_number'];
					$res = $this->model->where('id','=',$data['row']['id'])->update($data2);
					// 发送短信通知病理中心
					$params = array(
			            'mobile' =>'18217209156',
			            'template' =>'SMS_128636095',
			            'param'=>array(
			                'expert' =>'佟宝宝',
			                'patient'=>'test病人',
			                'cid'=>$data['row']['id']
			            ),
        			);
        			$this->smsSend($params);
					$this->model->where('id','=',$data['row']['id'])->setField('status',7);
				}else{
					$this->error('请填写快递公司名称或者单号');
					return false;
				}
			}
			$this->success('保存成功');
		}
	}
	
	public function verify2(){
		$data=$this->request->request('');
		if(isset($data['type'])){
			if($data['type']==1){
				$res=$this->model->where('id','=',$data['id'])->setField('status',3);
				$this->model->where('id','=',$data['id'])->setField('supply','');
				$this->model->where('id','=',$data['id'])->setField('explain',$data['explain']);
				$this->success('保存成功');
			}elseif($data['type']==2){
				$res=$this->model->where('id','=',$data['id'])->setField('status',4);
				$this->success('保存成功');
			}elseif($data['type']==3){
				$data2['add_content']=$data['add_content'];
				$data2['add_price']=$data['add_price'];
				$data2['status']=5;
 				$resuid=Db::name('consultation')->where('id',$data['id'])->value('uid');
	            $user_id = $this->auth->id;
	            $orderid = date("YmdHis") . mt_rand(100000, 999999);
	            $datapay = [
	                'user_id'     => $resuid,
	                'orderid'     => $orderid,
	                'c_id'        =>  $data['id'],
	                'title'       => "会诊费支付",
	                'amount'      => $data2['add_price'],
	                'createtime'      =>time(),
	                'payamount'   => '',//
	                'paytype'     => '3',
	                'ordertype'     => '2',
	                'status'=>0,//已支付//
	                //'ip'          => $request->ip(0, false),
	                //'useragent'   => substr($request->server('HTTP_USER_AGENT'), 0, 255),
	                'statustype'      => 'created'
	           ];
	            $r =Db::name('order')->insert($datapay);


				$res=$this->model->where('id','=',$data['id'])->update($data2);
				$this->success('保存成功');
			}else{
				$data2['content']=$data['content'];
				$data2['status']=6;
				$res = $this->model->where('id','=',$data['id'])->update($data2);
				$this->success('保存成功');
			}
		}

	}
}
