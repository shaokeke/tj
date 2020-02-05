<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use think\Config;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class BarcodeInfo extends Backend
{
    
    /**
     * BarcodeInfo模型对象
     * @var \app\admin\model\BarcodeInfo
     */
    protected $model = null;
    protected $db = null;
    public function _initialize()
    {
        $config = Config::get('db2');   //读取第二个数据库配置
        $this->db =  Db::connect($config);    //连接数据库
        parent::_initialize();
        $this->model = new \app\admin\model\BarcodeInfo;
        $this->view->assign("statusList", $this->model->getStatusList());
    }
    /**
     * 导出所有用户数据
     * 直接url访问，不能使用ajax，因为ajax要求返回数据，和PHPExcel一会浏览器输出冲突！将数据作为参数
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function out(){
        //dump($this->request->param());
        $data = $this->request->get();
        if($data['time']){
            $todayStart = date('Y-m-d 00:00:00', strtotime($data['time']));
            $todayEnd = date('Y-m-d 23:59:59', strtotime($data['time']));
        }else{
            $todayStart= date('Y-m-d 00:00:00', time()); //2019-01-17 00:00:00  昨天
            $todayEnd= date('Y-m-d 23:59:59', time()); //2019-01-17 23:59:59
        }

        
        $where['sendDate'] = array('between', array($todayStart,$todayEnd));
        $where['status'] = 1;
        $result=Db::table('xb_barcode_info')->where($where)->field('ROW_NUMBER,internalBarcode,externalBarCode,pName,pAge,pSex')->select();


        $filename = "RenAi_".date('Y-m-d', strtotime($todayStart));
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        //设置保存版本格式
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
 
        //设置表头
        $objPHPExcel->getActiveSheet()->setCellValue('A1','序号');
        $objPHPExcel->getActiveSheet()->setCellValue('B1','条码号');
        $objPHPExcel->getActiveSheet()->setCellValue('C1','流水号');
        $objPHPExcel->getActiveSheet()->setCellValue('D1','病人姓名');
        $objPHPExcel->getActiveSheet()->setCellValue('E1','年龄');
        $objPHPExcel->getActiveSheet()->setCellValue('F1','性别');
        $objPHPExcel->getActiveSheet()->setCellValue('G1','诊断结果');
        $objPHPExcel->getActiveSheet()->setCellValue('H1','报告医生');

        $objPHPExcel->getDefaultStyle()->getFont()->setName( '宋体');
        $objPHPExcel->getDefaultStyle()->getFont()->setSize(11);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        
        //改变此处设置的长度数值
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(18);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(18);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(16);
        //$objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('B')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        // $objPHPExcel->getActiveSheet()->getStyle('A2:AC2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        //输出表格
        $i=0;
        $count = Db::table('xb_barcode_info')->where($where)->field('ROW_NUMBER,internalBarcode,externalBarCode,pName,pAge,pSex')->order('ROW_NUMBER desc')->count();
        if(!$count){
            $count = 0;
        }
        foreach ($result as $key => &$val) {
 
            // $count = Db::table('xb_barcode_info')->where($where)->field('ROW_NUMBER,internalBarcode,externalBarCode,pName,pAge,pSex')->order('ROW_NUMBER desc')->count();
            // if(!$count){
            //     $count = 0;
            // }
            $i=$key+2;//表格是从2开始的
            if($val['pSex']==1){
                $sex = "男";
            }else{
                $sex = "女";
            }
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$i,$val['ROW_NUMBER']);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.$i,''.$val['internalBarcode']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.$i,''.$val['externalBarCode']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.$i,$val['pName']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.$i,$val['pAge']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.$i,$sex);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.$i,'');
            $objPHPExcel->getActiveSheet()->setCellValue('H'.$i,'');
            $i++;
        }
        $style_array = array(
                'borders' => array(
                    'allborders' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN
                    )
                ) );
        if($count<1){
            $i=2;
        }
        $objPHPExcel->getActiveSheet()->getStyle('A1:H'.($i-1))->applyFromArray($style_array);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename='.$filename.'.xls');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

    public function import1(){
        echo $todayStart= date('Y-m-d 00:00:00', time());
        echo date('Y-m-d 00:00:00', strtotime("1900-01-01 00:00:00.000"));
        
        exit;
        return parent::import();
        if(request() -> isPost())
        {
            vendor("PHPExcel.PHPExcel"); 
            $objPHPExcel =new \PHPExcel();
            //获取表单上传文件
            $file = request()->file('excel');
            var_dump($file);
            $info = $file->validate(['ext' => 'xls'])->move(ROOT_PATH . 'public');  //上传验证后缀名,以及上传之后移动的地址  E:\wamp\www\bick\public
            if($info)
            {
                $exclePath = $info->getSaveName();  //获取文件名
                var_dump($exclePath);exit;
                $file_name = ROOT_PATH . 'public' . DS . $exclePath;//上传文件的地址
                $objReader =\PHPExcel_IOFactory::createReader("Excel2007");
                $obj_PHPExcel =$objReader->load($file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8
                $excel_array=$obj_PHPExcel->getSheet(0)->toArray();   //转换为数组格式
                array_shift($excel_array);  //删除第一个数组(标题);
                $data = [];
                $i=0;
                var_dump(excel_array);exit;
                foreach($excel_array as $k=>$v) {
                    $data[$k]['recieveTime'] = $v[0];
                    $data[$k]['registerTime'] = $v[1];
                    $data[$k]['registerName'] = $v[2];
                    $data[$k]['reportTime'] = $v[3];
                    $i++;
                }
                Db::name("area_code")->insertAll($city);
            }else
            {
                echo $file->getError();
            }
        }
    }
    /**
     * 导入
     */
    public function import()
    {
        vendor('PHPExcel.PHPExcel');
        $file = $this->request->request('file');
        if (!$file) {
            $this->error(__('Parameter %s can not be empty', 'file'));
        }
        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        if (!is_file($filePath)) {
            $this->error(__('No results were found'));
        }
        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            $this->error(__('Unknown data format'));
        }
        if ($ext === 'csv') {
            $this->error(__('Unknown data format'));
        } elseif ($ext === 'xls') {
            
        } else {
            $this->error(__('Unknown data format'));
        }

        $objReader =\PHPExcel_IOFactory::createReader("Excel5");
        $obj_PHPExcel =$objReader->load($filePath, $encode = 'utf-8');  //加载文件内容,编码utf-8
        $excel_array=$obj_PHPExcel->getSheet(0)->toArray();   //转换为数组格式
        array_shift($excel_array);  //删除第一个数组(标题);
        $data_info = [];
        $data_res = [];
        $i=0;
        //var_dump($excel_array);exit;
        //$v[6] 诊断结果
        foreach($excel_array as $k=>$v) {
            if(empty($v[6])||empty($v[7])){
                continue;
            }
            $data = Db::name("barcode_info")->where('externalBarCode',$v[2])->select();

            $data_info[$k]['sampleNO'] = $data[0]['externalBarCode'];
            $data_info[$k]['externalBarCode'] = $data[0]['externalBarCode'];
            $data_info[$k]['hospitalCode'] = $data[0]['hospitalCode'];
            $data_info[$k]['pName'] = $data[0]['pName'];
            $data_info[$k]['pSex'] = $data[0]['pSex'];
            $data_info[$k]['pAge'] = $data[0]['pAge'];
            $data_info[$k]['ageUnit'] = $data[0]['ageUnit'];
            $data_info[$k]['identityCardNo'] = $data[0]['identityCardNo'];
            $data_info[$k]['pType'] = $data[0]['pType'];
            $data_info[$k]['admNo'] = $data[0]['admNo'];
            $data_info[$k]['admDate'] = $data[0]['admDate'];
            $data_info[$k]['department'] = $data[0]['department'];
            $data_info[$k]['doctorName'] = $data[0]['doctorName'];
            $data_info[$k]['diagnose'] = $data[0]['diagnose'];
            $data_info[$k]['specimensType'] = $data[0]['specimensType'];
            $data_info[$k]['tubes'] = $data[0]['tubes'];
            $data_info[$k]['specimensStatus'] = $data[0]['specimensStatus'];
            $data_info[$k]['sendDate'] = $data[0]['sendDate'];
            $data_info[$k]['testDest'] = $data[0]['testDest'];
            // $data_info[$k]['microFlag'] = 1;

            $data_info[$k]['internalBarCode'] = $data[0]['internalBarcode'];
            $data_info[$k]['patientID'] = $data[0]['patientId'];
            $data_info[$k]['pNameENG'] = $data[0]['pNameEng'];
            $data_info[$k]['dateOfbirth'] = $data[0]['dateOfBirth'];
            $data_info[$k]['bedNO'] = $data[0]['bedNo'];

            
            $data_info[$k]['registerTime'] = date('Y-m-d 00:00:00', time());
            $data_info[$k]['reportName'] = $v[7];
            $data_info[$k]['reportTime'] = date('Y-m-d h:i:s', time());
            $data_info[$k]['collectTime']=date('Y-m-d 00:00:00', strtotime("1900-01-01 00:00:00.000"));
            
            //dump($data_info[$k]);

            //报告信息
            $data_res[$k]['sampleNO']=$data[0]['externalBarCode'];
            $data_res[$k]['internalBarCode']=$data[0]['internalBarcode'];
            $data_res[$k]['externalBarCode']=$data[0]['externalBarCode'];
            $data_res[$k]['hospitalCode']=1;

            $data_res[$k]['testCode']="PAP001";
            $data_res[$k]['testName']="宫颈刮片";
            //$data_res[$k]['ItemNameEN']=$data[0]['externalBarCode'];

            //$data_res[$k]['areaCode']=$data[0]['externalBarCode'];
            $data_res[$k]['testResult']=$v[6];
            //$data_res[$k]['itemRef']=$data[0]['externalBarCode'];
           // $data_res[$k]['resultTip']=$data[0]['externalBarCode'];
            //$data_res[$k]['itemUnit']=$data[0]['externalBarCode'];
            //$data_res[$k]['resultOrder']=$data[0]['externalBarCode'];
           // $data_res[$k]['criticalFlag']=$data[0]['externalBarCode'];
            //$data_res[$k]['testMethod']=$data[0]['testMethod'];
            
            
             // $config = Config::get('db2');   //读取第二个数据库配置
             // $db =  Db::connect($config);    //连接数据库
            if($data[0]['status']==2||$data[0]['status']=='2'){
                //更新本地
                Db::name("report_info")->where('externalBarCode',$data[0]['externalBarCode'])->update(['reportName'=>$v[7]]);
                Db::name("report_jyresult")->where('sampleNO',$data[0]['externalBarCode'])->update(['testResult'=>$v[6]]);

               //更新远程sqlserver
                $this->db->table("ws_report_info")->where('externalBarCode',$data[0]['externalBarCode'])->update(['reportName'=>$v[7]]);
                $this->db->table("ws_report_jyResult")->where('sampleNO',$data[0]['externalBarCode'])->update(['testResult'=>$v[6]]);
                
            }else{

                //插入本地
                $r = Db::name("report_info")->insert($data_info[$k]);
				//原来有
				$report = $this->db->table("ws_report_info")->where('sampleNO',$data[0]['externalBarCode'])->select();
				if($report){
					$str = "导入".$data[0]['externalBarCode']."失败,重复导入";
					$this->error($str);
				}
                $res = $this->db->table("ws_report_info")->insert($data_info[$k]);
				
                if($res&&$r){
                    $this->db->table("ws_report_jyResult")->insert($data_res[$k]);
                    Db::name("report_jyresult")->insert($data_res[$k]);
                    Db::name("barcode_info")->where('externalBarCode',$v[2])->setField('status',2);
                }else{
                    $this->error("导入report_info失败");
                }
            }
           
        }
        // //插入info表
        // Db::name("report_info")->insertAll($city);
        // //插入jyResult
        // Db::name("report_jyResult")->insertAll($city);
        $this->success("提交成功");
    }
    //刷新数据
    public function auto()
    {
        
        //echo date('Y-m-d',strtotime("2018-04-14 12:38:53.000"));exit;
        //$todayStart= date('Y-m-d 00:00:000', time()); //2019-01-17 00:00:00 今天
        //$todayEnd= date('Y-m-d 23:59:59', time()); //2019-01-17 23:59:59

        $todayStart= date('Y-m-d 00:00:00', strtotime("-1 day")); //2019-01-17 00:00:00  昨天
        $todayEnd= date('Y-m-d 23:59:59', strtotime("-1 day")); //2019-01-17 23:59:59

        $where['sendDate'] = array('between', array($todayStart,$todayEnd));
        
        $result=$this->db->table('ws_barCode_info')->where($where)->select();
        //dump($result);exit;
        if($result){
            foreach($result as $k=>$v) {
                $data = Db::name("barcode_info")->where('externalBarCode',$v['externalBarCode'])->select();
                if(!empty($data)||$v['testDest']!="宫颈刮片"){
                    continue;
                }

                Db::table('xb_barCode_info')->insert($v);
                
            }
        }else{
            $this->success("没有新的数据");
        }
        
        //$a = Db::table('xb_barCode_info')->insertAll($result,true);
        $this->success("刷新成功");
        
    }

}
