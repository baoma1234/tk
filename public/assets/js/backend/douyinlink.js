define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'douyinlink/index',
                    add_url: 'douyinlink/add',
                    edit_url: 'douyinlink/edit',
                    del_url: 'douyinlink/del',
                    table: 'douyin_link',
                }
            });

            var table = $("#table");

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                toolbar: '#toolbar',
                pk: 'id',
                sortName: 'id',
                search: false,
                columns: [[
                    {checkbox: true},
                    {field: 'id', title: 'ID', sortable: true},
                    {field: 'remark', title: '备注', operate: 'LIKE'},
                    {field: 'url', title: '短链', operate: 'LIKE'},
                    {field: 'author_name', title: '作者名字', operate: 'LIKE'},
                    {field: 'author_fans', title: '粉丝数', operate: false},
                    {field: 'author_likes', title: '获赞数', operate: false},
                    {field: 'video_like_count', title: '作品点赞数', operate: false},
                    {field: 'comment_count', title: '评论数', operate: false},
                    {field: 'status', title: '状态', operate: false},
                    {field: 'check_status', title: '检测状态', operate: false},
                    {field: 'last_check_time', title: '最近检测时间', operate: 'RANGE'},
                    {
                        field: 'operate',
                        title: '操作',
                        events: {
                            'click .btn-view-history': function (e, value, row, index) {
                                e.stopPropagation();
                                Fast.api.open('douyinlinklog/history?link_id=' + row.id, '查看历史记录');
                            },
                            'click .btn-check-now': function (e, value, row, index) {
                                e.stopPropagation();
                                Fast.api.ajax({
                                    url: 'douyinlink/checknow/ids/' + row.id
                                }, function () {
                                    Toastr.success('检测完成');
                                    Table.api.refreshContent();
                                });
                            }
                        },
                        formatter: function (value, row, index) {
                            return [
                                '<a href="javascript:;" class="btn btn-xs btn-info btn-view-history"><i class="fa fa-history"></i> 查看历史</a>',
                                ' <a href="javascript:;" class="btn btn-xs btn-warning btn-check-now"><i class="fa fa-refresh"></i> 立即检测</a>'
                            ].join('');
                        }
                    }
                ]]
            });

            Table.api.bindevent(table);
        },

        batchadd: function () {
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
