define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function() {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'barcode_info/index' + location.search,
                    add_url: '',
                    edit_url: 'barcode_info/edit',
                    del_url: '',
                    multi_url: '',
                    import_url: 'barcode_info/import',
                    table: 'barcode_info',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                searchFormVisible: true,
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: __('Id'), operate: false },
                        //{ field: 'ROW_NUMBER', title: __('序号') },
                        { field: 'internalBarcode', title: __('条码号') },
                        { field: 'externalBarCode', title: __('医院流水号') },

                        { field: 'pName', title: __('Pname') },
                        { field: 'pSex', title: __('Psex'), formatter: Controller.api.formatter.sex },

                        { field: 'pAge', title: __('Page') },


                        { field: 'sendDate', title: __('Senddate'), operate: 'RANGE', addclass: 'datetimerange',formatter: Table.api.formatter.datetime,extend:'autocomplete="off"',data: 'data-date-format="YYYY-MM-DD"'},

                        // { field: 'ROW_NUMBER', title: __('Row_number') },
                        { field: 'status', title: __('Status'), searchList: { "1": __('Status 1'), "2": __('Status 2') }, formatter: Table.api.formatter.status },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            $(document).on("click", "#out", function() {
                var time = $("#sendDate").val();
                time = time.substring(0, 10);
                var url = "barcode_info/out?time=" + time;
                window.location.href = url;
                //Fast.api.open($.fn.bootstrapTable.defaults.extend.zdy_url, '编辑2', {})
            });

        },
        add: function() {
            Controller.api.bindevent();
        },
        edit: function() {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function() {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: { //渲染的方法
                // custom: function(value, row, index) {
                //     //添加上btn-change可以自定义请求的URL进行数据处理
                //     return '<a class="btn-change text-success" data-url="manage/user/change" data-id="' + row.id + '"><i class="fa ' + (value != 2 ? 'fa-toggle-off' : 'fa-toggle-on') + ' fa-2x"></i></a>';
                // },
                sex: function(value, row, index) {
                    //添加上btn-change可以自定义请求的URL进行数据处理
                    var sex = '';
                    if (value == 1) sex = '男';
                    else sex = '女';
                    return sex;
                },
            },
        }
    };
    return Controller;
});;