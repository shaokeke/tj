<?php

namespace app\admin\controller\auth;

use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use app\admin\model\AuthHospital;
use app\admin\model\GroupHospital;
use app\common\controller\Backend;
use fast\Random;
use fast\Tree;
use think\Db;
/**
 * 管理员管理
 *
 * @icon fa fa-users
 * @remark 一个管理员可以有多个角色组,左侧的菜单根据管理员所拥有的权限进行生成
 */
class Admin extends Backend
{

    /**
     * @var \app\admin\model\Admin
     */
    protected $model = null;
    protected $childrenGroupIds = [];
    protected $childrenAdminIds = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Admin');

        $this->childrenAdminIds = $this->auth->getChildrenAdminIds(true);
        $this->childrenGroupIds = $this->auth->getChildrenGroupIds(true);

        $groupList = collection(AuthGroup::where('id', 'in', $this->childrenGroupIds)->select())->toArray();

        Tree::instance()->init($groupList);
        $groupdata = [];
        if ($this->auth->isSuperAdmin())
        {
            $result = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
            foreach ($result as $k => $v)
            {
                $groupdata[$v['id']] = $v['name'];
            }
        }
        else
        {
            $result = [];
            $groups = $this->auth->getGroups();
            foreach ($groups as $m => $n)
            {
                $childlist = Tree::instance()->getTreeList(Tree::instance()->getTreeArray($n['id']));
                $temp = [];
                foreach ($childlist as $k => $v)
                {
                    $temp[$v['id']] = $v['name'];
                }
                $result[__($n['name'])] = $temp;
            }
            $groupdata = $result;
        }
	
        $this->view->assign('groupdata', $groupdata);
        $this->assignconfig("admin", ['id' => $this->auth->id]);
    }

    /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            $childrenGroupIds = $this->childrenGroupIds;
            $groupName = AuthGroup::where('id', 'in', $childrenGroupIds)
                    ->column('id,name');
            $authGroupList = AuthGroupAccess::where('group_id', 'in', $childrenGroupIds)
                    ->field('uid,group_id')
                    ->select();

            $adminGroupName = [];
            foreach ($authGroupList as $k => $v)
            {
                if (isset($groupName[$v['group_id']]))
                    $adminGroupName[$v['uid']][$v['group_id']] = $groupName[$v['group_id']];
            }
            $groups = $this->auth->getGroups();
            foreach ($groups as $m => $n)
            {
                $adminGroupName[$this->auth->id][$n['id']] = $n['name'];
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->where($where)
                    ->where('id', 'in', $this->childrenAdminIds)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->where($where)
                    ->where('id', 'in', $this->childrenAdminIds)
                    ->field(['password', 'salt', 'token'], true)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            foreach ($list as $k => &$v)
            {
                $groups = isset($adminGroupName[$v['id']]) ? $adminGroupName[$v['id']] : [];
                $v['groups'] = implode(',', array_keys($groups));
                $v['groups_text'] = implode(',', array_values($groups));
            }
            unset($v);
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
			
			$datas=$this->request->post("row/a");
			
			unset($datas['username']);
			unset($datas['email']);
			unset($datas['nickname']);
            unset($datas['mobile']);
			unset($datas['password']);
			unset($datas['loginfailure']);
			unset($datas['type']);			
			unset($datas['hospital_id']);				
			unset($datas['status']);
			unset($datas['department']);
		
            if ($params)
            {
                $params['salt'] = Random::alnum();
                $params['password'] = md5(md5($params['password']) . $params['salt']);
                $params['avatar'] = '/assets/img/avatar.png'; //设置新管理员默认头像。
				if(isset($params['hospital_id'])){
					$params['hospital_id']=implode(',',$params['hospital_id']);
					$hid=explode(',',$params['hospital_id']);
				}
			
                $result = $this->model->validate('Admin.add')->allowField(true)->save($params);
                if ($result === false)
                {
                    $this->error($this->model->getError());
                }
                $group = $this->request->post("group/a");
	
                //过滤不允许的组别,避免越权
                $group = array_intersect($this->childrenGroupIds, $group);
                $dataset = [];
                foreach ($group as $value)
                {
                    $dataset[] = ['uid' => $this->model->id, 'group_id' => $value];
                }
                model('AuthGroupAccess')->saveAll($dataset);
				if(isset($hid)){
					foreach ($hid as $value){
						if($params['type']==1){
							$data[] = ['uid' => $this->model->id, 'hospital_id' => $value,'type'=>1];
						}else{
							$data[] = ['uid' => $this->model->id, 'hospital_id' => $value,'type'=>0];
						}
						
					}
				
					model('AuthHospital')->saveAll($data);
				}
				
				$param = [];
				foreach($datas as $key=>$val){								
					$param[] = ['uid' => $this->model->id, 'group_id' => $key, 'hospital_id' => $val];				
				}
				model('GroupHospital')->saveAll($param);
				
                $this->success();
            }
            $this->error();
        }
		$list=Db::name('hospital')->field('id as hospital_id,name')->select();
		$this->view->assign('list',$list);
		$this->assignconfig('list',['name'=>$list]);
        return $this->view->fetch();
    }
	
	public function hospital(){
		$data=$this->request->request('');
		$list=Db::name('hospital')->field('id as hospital_id,name')->select();
		
		if($data['type']==1){			
			if(isset($data['uid'])){
				$row = $this->model->get(['id' => $data['uid']]);
				$res=Db::name('AuthHospital')->where('uid','neq',$data['uid'])->where('type',1)->select();
			}else{
				$res=Db::name('AuthHospital')->select();
			}			
		}else{
			$res=array();
		}	
		
		if($res){
			foreach($res as $key=>$val){
				foreach($list as $k=>$v){
					if($v['hospital_id']==$val['hospital_id']){
						unset($list[$k]);
					}
				}
			}
			$list=array_values($list);
			$this->success('返回成功','',$list);
		}else{
			$this->success('返回成功','',$list);
		}
	}

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get(['id' => $ids]);
        if (!$row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
			$datas=$this->request->post("row/a");
			unset($datas['username']);
			unset($datas['email']);
			unset($datas['nickname']);
			unset($datas['password']);
			unset($datas['loginfailure']);
			unset($datas['type']);
			unset($datas['hospital_id']);
			unset($datas['status']);
			unset($datas['department']);
			
			
            if ($params)
            {
                if ($params['password'])
                {
                    $params['salt'] = Random::alnum();
                    $params['password'] = md5(md5($params['password']) . $params['salt']);
                }
                else
                {
                    unset($params['password'], $params['salt']);
                }
                //这里需要针对username和email做唯一验证
                $adminValidate = \think\Loader::validate('Admin');
                $adminValidate->rule([
                    'username' => 'require|max:50|unique:admin,username,' . $row->id,
                    'email'    => 'require|email|unique:admin,email,' . $row->id
                ]);
				if(isset($params['hospital_id'])){
					$params['hospital_id']=implode(',',$params['hospital_id']);
					$hid=explode(',',$params['hospital_id']);
				}
							
				
                $result = $row->validate('Admin.edit')->allowField(true)->save($params);
                if ($result === false)
                {
                    $this->error($row->getError());
                }

                // 先移除所有权限
                model('AuthGroupAccess')->where('uid', $row->id)->delete();

                $group = $this->request->post("group/a");

                // 过滤不允许的组别,避免越权
                $group = array_intersect($this->childrenGroupIds, $group);

                $dataset = [];
                foreach ($group as $value)
                {
                    $dataset[] = ['uid' => $row->id, 'group_id' => $value];
                }
                model('AuthGroupAccess')->saveAll($dataset);
				
				model('AuthHospital')->where('uid',$ids)->delete();
				if(isset($hid)){
					foreach ($hid as $value){
						if($params['type']==1){
							$data[] = ['uid' => $row->id, 'hospital_id' => $value,'type'=>1];
						}else{
							$data[] = ['uid' => $row->id, 'hospital_id' => $value,'type'=>0];
						}
						
					}
					model('AuthHospital')->saveAll($data);
				}
				
				
				
				model('GroupHospital')->where('uid', $row->id)->delete();
				$param = [];
				foreach($datas as $key=>$val){								
					$param[] = ['uid' => $row->id, 'group_id' => $key, 'hospital_id' => $val];				
				}
				model('GroupHospital')->saveAll($param);
				
                $this->success();
            }
            $this->error();
        }
        $grouplist = $this->auth->getGroupss($row['id']);
        $groupids = [];
        foreach ($grouplist as $k => $v)
        {
            $groupids[] = $v['id'];
        }
	
		$row['hospital_id']=explode(',',$row['hospital_id']);
		$hospital_id=implode(',',$row['hospital_id']);
		$list=Db::name('hospital')->field('id as hospital_id,name')->select();
		$hospital_list=Db::name('hospital')->field('id as hospital_id,name')->select();
		if($row['type']==1){			
			foreach($list as $k=>$v){
				if(!in_array($v['hospital_id'],$row['hospital_id'])){
					unset($list[$k]);
				}
			}
			
		}

        $this->view->assign("row", $row);
		$this->view->assign("hid", $hospital_id);
        $this->view->assign("groupids", $groupids);
		
		
		$gid=Db::name('auth_group_access')->where('uid',$row['id'])->column('group_id');
		$info=Db::name('group_hospital')->where('uid',$row['id'])->field('group_id,hospital_id')->select();
		foreach($info as $key=>$val){
			$info[$key]['name']=Db::name('auth_group')->where('id',$val['group_id'])->value('name');
		}
		
		$this->view->assign('list',$list);
		$this->view->assign('info',$info);
		$this->assignconfig('list',['name'=>$hospital_list]);
		$this->assignconfig('info',['name'=>$info]);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids)
        {
			//对应关联的医院也删除
			$count=Db::name('AuthHospital')->where('uid',$ids)->count();
			$number=Db::name('GroupHospital')->where('uid',$ids)->count();
			if($count){
				model('AuthHospital')->where('uid',$ids)->delete();
			}
			if($number){
				model('GroupHospital')->where('uid',$ids)->delete();
			}
			
            // 避免越权删除管理员
            $childrenGroupIds = $this->childrenGroupIds;
            $adminList = $this->model->where('id', 'in', $ids)->where('id', 'in', function($query) use($childrenGroupIds) {
                        $query->name('auth_group_access')->where('group_id', 'in', $childrenGroupIds)->field('uid');
                    })->select();
            if ($adminList)
            {
                $deleteIds = [];
                foreach ($adminList as $k => $v)
                {
                    $deleteIds[] = $v->id;
                }
                $deleteIds = array_diff($deleteIds, [$this->auth->id]);
                if ($deleteIds)
                {
                    $this->model->destroy($deleteIds);
                    model('AuthGroupAccess')->where('uid', 'in', $deleteIds)->delete();
                    $this->success();
                }
            }			
        }
        $this->error();
    }

    /**
     * 批量更新
     * @internal
     */
    public function multi($ids = "")
    {
        // 管理员禁止批量操作
        $this->error();
    }

    /**
     * 下拉搜索
     */
    protected function selectpage()
    {
        $this->dataLimit = 'auth';
        $this->dataLimitField = 'id';
        return parent::selectpage();
    }

}
