define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'pk/code/index',
                    add_url: 'pk/code/add',
                    edit_url: 'pk/code/edit',
                    del_url: 'pk/code/del',
                    multi_url: 'pk/code/multi',
                    table: 'pk_code',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'speriod', title: __('Speriod')},
                        {field: 'open_time', title: __('Open_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'one', title: __('One')},
                        {field: 'two', title: __('Two')},
                        {field: 'three', title: __('Three')},
                        {field: 'four', title: __('Four')},
                        {field: 'five', title: __('Five')},
                        {field: 'six', title: __('Six')},
                        {field: 'seven', title: __('Seven')},
                        {field: 'eight', title: __('Eight')},
                        {field: 'nine', title: __('Nine')},
                        {field: 'ten', title: __('Ten')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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