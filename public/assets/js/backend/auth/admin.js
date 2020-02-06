define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'auth/admin/index',
                    add_url: 'auth/admin/add',
                    edit_url: 'auth/admin/edit',
                    del_url: 'auth/admin/del',
                    multi_url: 'auth/admin/multi',
                }
            });

            var table = $("#table");

            //在表格内容渲染完成后回调的事件
            table.on('post-body.bs.table', function (e, json) {
                $("tbody tr[data-index]", this).each(function () {
                    if (parseInt($("td:eq(1)", this).text()) == Config.admin.id) {
                        $("input[type=checkbox]", this).prop("disabled", true);
                    }
                });
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                columns: [
                    [
                        {field: 'state', checkbox: true, },
                        {field: 'id', title: 'ID'},
                        {field: 'username', title: __('Username')},
                        {field: 'nickname', title: __('Nickname')},
                        {field: 'groups_text', title: __('Group'), operate:false, formatter: Table.api.formatter.label},
                        {field: 'mobile', title: __('手机号')},
                        {field: 'email', title: __('Email')},
                        {field: 'status', title: __("Status"), formatter: Table.api.formatter.status},
                        {field: 'logintime', title: __('Login time'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: function (value, row, index) {
                                if(row.id == Config.admin.id){
                                    return '';
                                }
                                return Table.api.formatter.operate.call(this, value, row, index);
                            }}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {   
			//console.log(Config.list.name)
		    $("input[name='row[type]']").click(function(){
				var val=$("input[name='row[type]']:checked").val();
				if(val==1){
					$('#shospital').show();
				}else{
					$('#shospital').hide();
				}
			});
			
			//绑定fachoose选择附件事件               
			$(".fachoosed", $("form[role=form]")).on('click', function () {
				var obj = $(".selectpicker option:selected");
				var  artime_val='',arr=[];
				obj.each(function(item,i){
					artime_val+=i.value+',';
					arr.push(i.value);
				});
				
				var group_id=arr;
				parent.Fast.api.open("hospital/hospital/select?uid=0"+ "&gid=" + group_id+ "&type=1" , __('Choose'), {
					callback: function (data) {
						console.log(data);										
						var nameArr = [];						
						nameArr.push(data.name);						
						var result = nameArr.join(",");
						var res = result.split(',');
						
						var idArr = [];						
						idArr.push(data.id);						
						var idArr = idArr.join(",");
						var ids = idArr.split(',');
						var array=[];
						$('#hospital :input').each(function(e){
							array.push($(this).val());							
						})
						
						var html='';
						$.each(res, function (k, v) {
							var aaa=$.inArray(ids[k], array);
							if(aaa<0){
								html+='<input type="checkbox" name="row[hospital_id][]" value="'+ids[k]+'" checked/>'+v+'';	
							}
						});						
						$('#hospital').append(html);
					}
				});
				return false;
				
			});
			
			$(".selectpicker option:selected").on("click",function(e){
				alert();
			});
			
			Form.api.bindevent($("form[role=form]"));
		},
        edit: function () {
        	//初始化进来获取选中权限
	        var obj = $(".selectpicker option:selected");
			var  artime_val='',arr=[];
			obj.each(function(item,i){
				artime_val+=i.value+',';
				arr.push(i.value);
			});console.log(arr);
			$('#fselect').val(artime_val);
			
			var datalist=Config.list.name;
			var info=Config.info.name;				
			var htmls='';

			$.each(info,function(key,val){				
				htmls+='<div class="form-group" id="'+val['group_id']+'"><label for="password" class="control-label col-xs-12 col-sm-2">'+val['name']+'的医院:</label>';
				htmls+='<div class="col-xs-12 col-sm-8" id="selects_">';
				$.each(datalist,function(k,v){
					if(v.hospital_id==val['hospital_id']){
						htmls+='<a class="selects_'+val['group_id']+'"  data-multiple="true"><input type="radio" checked name="row['+val['group_id']+']" value="'+v.hospital_id+'"/>'+v.name+'</a>';
					}
					
				})
				htmls+='</div></div>';				
			})	
			$('#svavs').html(htmls);
	        	
			$("input[name='row[type]']").click(function(){
				var val=$("input[name='row[type]']:checked").val();
				if(val==1){
					$('#shospital').show();
				}else{
					$('#shospital').hide();
				}
			})
			
			
			//绑定fachoose选择附件事件               
			$(".fachoosed", $("form[role=form]")).on('click', function () {
				var uid=$('#id').val();
				
				var obj = $(".selectpicker option:selected");
				var  artime_val='',arr=[];
				obj.each(function(item,i){
					artime_val+=i.value+',';
					arr.push(i.value);
				});
				
				var group_id=arr;
				parent.Fast.api.open("hospital/hospital/select?uid="+uid+ "&gid=" + group_id+ "&type=1", __('Choose'), {
					callback: function (data) {
						console.log(data);				
						var nameArr = [];						
						nameArr.push(data.name);						
						var result = nameArr.join(",");
						var res = result.split(',');
						
						var idArr = [];						
						idArr.push(data.id);						
						var idArr = idArr.join(",");
						var ids = idArr.split(',');
						var array=[];
						$('#hospital :input').each(function(e){
							array.push($(this).val());
							
						})
						
						var html='';
						$.each(res, function (k, v) {
							var aaa=$.inArray(ids[k], array);
							if(aaa<0){
								html+='<input type="checkbox" name="row[hospital_id][]" value="'+ids[k]+'" checked/>'+v+'';	
							}
						});						
						$('#hospital').append(html);
					}
				});
				return false;
				
			});
			
			//新增
			$.each(arr, function (key, val) {								
				$(document).on('click','.selects_'+val+'',function(){					
					parent.Fast.api.open("hospital/hospital/select?uid=0"+ "&gid=" + val+ "&type=2" , __('Choose'), {
						callback: function (data) {										
							var nameArr = [];						
							nameArr.push(data.name);						
							var result = nameArr.join(",");
							var res = result.split(',');
							
							var idArr = [];						
							idArr.push(data.id);						
							var idArr = idArr.join(",");
							var ids = idArr.split(',');
							
							
							var html='';
							$.each(res, function (k, v) {
								html+='<input type="radio" name="row['+val+']" value="'+ids[k]+'" checked/>'+v+'';
							});						
							$('.selects_'+val+'').html(html);
						}
					});
					return false;					
				});
			});
			
            Form.api.bindevent($("form[role=form]"));
			
        },
		
		
		
		
		
                
            
        
		
    };
    return Controller;
});