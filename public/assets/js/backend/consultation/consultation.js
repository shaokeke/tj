define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {



    var Controller = {
        index: function () {
            // 初始化表格参数配置
			var cc=$('#cc').val();
			if(cc==1){
				Table.api.init({
					extend: {
						index_url: 'consultation/consultation/index',
						add_url: 'consultation/consultation/add',
						edit_url: 'consultation/consultation/edit',						
						del_url: 'consultation/consultation/del',						
						multi_url: 'consultation/consultation/multi',
						table: 'consultation',
					}
				});
			}else{
				Table.api.init({
					extend: {
						index_url: 'consultation/consultation/index',
						add_url: 'consultation/consultation/add',
						edit_url: 'consultation/consultation/edit',						
						//del_url: 'consultation/consultation/del',						
						multi_url: 'consultation/consultation/multi',
						table: 'consultation',
					}
				});
			}
            

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'consultation.id',
				//dblClickToEdit:true,
				//clickToSelect:true,
				//search:false,//实时搜索框,可以在后台控制器中指定搜索字段
                columns: [
					[
						{checkbox: true},
						{field: 'id', title: __('Id'), sortable: true, width:80},
						{field: 'is_pay', title: '付款状态',  searchList: {1: '未付款', 2: '已付款'}},
						{field: 'status', title: '会诊状态',  searchList: {0: '待付款', 1: '待取切片', 2: '已取切片', 3: '待补资料', 4: '已初审', 5: '增加项付费', 6: '会诊完成', 7: '待审核'}},
						{field: 'aim', title: '会诊目的', operate: 'LIKE'},
						{field: 'number', title:'会诊编号'},
						{field: 'name', title: '患者姓名', operate: 'LIKE'},
						{field: 'sex', title: '性别', searchList: {1: __('Male'), 2: __('Female')}},
						{field: 'group.name', width:'250', title: '送检医院', operate: 'LIKE'},
						{field: 'bl_num', title: '原单位病理号'},
						{field: 'department', title:'科室'},						
						{field: 'seek_num', title: '就诊号'},
						{field: 'card', title: '身份证号'},
						{field: 'report', title: '病理报告', formatter: Table.api.formatter.image, operate: false},
						{field: 'body', title: '取材部位', operate: 'LIKE'},
						{field: 'other_info', title: '其它信息', formatter: Table.api.formatter.image, operate: false},						
						{field: 'form', title: '备注', operate: 'LIKE'},
						{field: 'addtime', title: '添加时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
						// {fixed: 'right', field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
					]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {			
			//点击事件写在这里
			//操作提交事件
			$(".change-pj").on("click",function(e){
				//获取data的 type 类型以及提交的方法名，可加多个
				var formdata=$("form[role=form]").serializeArray();
				var t={};
				$.each(formdata, function() {
				  t[this.name] = this.value;
				});
				var type=e.currentTarget.dataset.type;
				var id=e.currentTarget.dataset.id;
				var data={};
				var url="consultation/consultation/verify2";
				data['type']=type;
				data['id']=id;
				if(type==4){			
					if(t['content'] == "" || t['content'] == null || t['content'] == undefined){
						layer.msg('请填写报告');
						return false;
					}
					data['content']=t['content'];
				}
				if(type==3){
					//新增费和备注不能为空
					if(t['add_content'] == "" || t['add_price'] == "" ){
						layer.msg('请填写新增费用或新增说明');
						return false;
					}
					data['add_content']=t['add_content'];
					data['add_price']=t['add_price'];					
				}
				if(type==1){
					if(t['explain'] == "" || t['explain'] == null || t['explain'] == undefined){
						layer.msg('请填写待补说明');
						return false;
					}
					data['explain']=t['explain'];
				}
				//不需要快递信息
				if(type==5){
					$('#edit-form').attr('action','');
					data['extypes']=2;
					url="consultation/consultation/verify"
				}
		
				//ajax请求格式、相对路径
					Fast.api.ajax({
					   url:url,
					   data:data
					}, function(data, ret){
						console.log(ret)
						layer.msg(ret.msg);
					   //成功的回调
					   Fast.api.close("consultation"); 
					   window.parent.location.reload();
					   return false;
					}, function(data, ret){
					   //失败的回调
					   layer.msg(ret.msg);
					   return false;
					});
			});
			
			Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});