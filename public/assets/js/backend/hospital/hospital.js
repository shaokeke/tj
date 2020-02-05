define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {



    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'hospital/hospital/index',
                    add_url: 'hospital/hospital/add',
                    edit_url: 'hospital/hospital/edit',					
                    del_url: 'hospital/hospital/del',
                    multi_url: 'hospital/hospital/multi',
					import_url:'hospital/hospital/import',
                    table: 'hospital',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'hospital.id',				
                columns: [
					[
						{checkbox: true},
						{field: 'id', title: __('Id'), sortable: true},
						{field: 'name', title:__('Name'), operate: 'LIKE'},
						{field: 'contact', title: __('Contact'), operate: 'LIKE'},
						{field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
						{field: 'tel', title: __('Tel'), operate: 'LIKE'},
						{field: 'types', title: __('Types'), operate: 'LIKE'},
						{field: 'consignee_id', title: __('Consignee'), operate: 'LIKE'},
						{field: 'assigned_id', title: __('Assigned'), operate: 'LIKE'},
						{field: 'change_id', title: __('Change'), operate: 'LIKE'},
						{field: 'frozen_id', title: __('Frozen'), operate: 'LIKE'},
						{field: 'state', title: __('State'), formatter: Table.api.formatter.status, searchList: {normal: __('Yes'), hidden: __('No')}},
						{field: 'status', title: __('Status'), formatter: Table.api.formatter.status, searchList: {normal: __('Normal'), hidden: __('Hidden')}},
						{fixed: 'right', field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
					]
                ],
                 onLoadSuccess:function(){
                    // 这里就是数据渲染结束后的回调函数
                    $(".btn-editone").data("area", ['90%','75%']);//编辑按钮的宽高
                }
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
			$("#consignee").change(function(){
				var id=$('#consignee').val();//发起会诊默认承接方id
				var hospital_id=$('#hospital_id').val();//编辑的id
				var change_id=$('#change_id').val();//转诊默认承接方id
				console.log(id);
				Fast.api.ajax({
				   url:"hospital/hospital/change",
				   data:{'id':id,'hospital_id':hospital_id}
				}, function(data, ret){
					console.log(data)
					var html='';
					html+='<select name="row[change_id]" class="form-control" >';                        						
					$.each(data,function(k,v){
						if(id==change_id){
							html+='<option value="'+v.id+'">'+v.name+'</option>';
						}else{
							if(v.id==change_id){
								html+='<option value="'+v.id+'" selected>'+v.name+'</option>';
							}else{
								html+='<option value="'+v.id+'">'+v.name+'</option>';
							}
						}
					})										   
					html+='</select>';
					$('#change').html(html);
				}, function(data, ret){
					layer.msg(ret.msg);
					return false;
				});
			})
			
			Controller.api.bindevent();
        },
		select: function () {
			var uid=Config.uid.uid;
			var gid=Config.gid.gid;
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'hospital/hospital/index?uid='+uid+'&gid='+gid,
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'id',
				showToggle: false,
				showColumns: false,
				showExport: false,
				commonSearch: false,
                columns: [
                    [
                        {checkbox: true,},
                        {field: 'id', title: __('Id'), sortable: true},
						{field: 'name', title:__('Name'), operate: 'LIKE'},                                          
                        {
                            field: 'operate', title: __('Operate'), events: {
                                'click .btn-chooseone': function (e, value, row, index) {
                                    var multiple = Backend.api.query('multiple');
                                    multiple = multiple == 'true' ? true : false;
                                    Fast.api.close({name: row.name,id:row.id, multiple: false});
								},
                            }, formatter: function () {
                                return '<a href="javascript:;" class="btn btn-danger btn-chooseone btn-xs"><i class="fa fa-check"></i> ' + __('Choose') + '</a>';
                            }
                        }
                    ]
                ]
            });

            // 选中多个
            $(document).on("click", ".btn-choose-multi", function () {
                var urlArr = new Array();
				var idArr = new Array();
                $.each(table.bootstrapTable("getAllSelections"), function (i, j) {
                    urlArr.push(j.name);
					idArr.push(j.id);
                });
				
                var multiple = Backend.api.query('multiple');
                multiple = multiple == 'true' ? true : false;
                Fast.api.close({name: urlArr.join(","),id: idArr.join(","), multiple: true});
			});

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});