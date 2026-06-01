define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        history: function () {
            Table.api.init({
                extend: {
                    index_url: 'douyinlinklog/index?link_id=' + Config.link_id,
                    table: 'douyin_link_log'
                }
            });

            var table = $("#history-table");

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [[
                    {field: 'id', title: 'ID'},
                    {field: 'check_time', title: '抓取时间'},
                    {field: 'author_name', title: '作者'},
                    {field: 'author_fans', title: '粉丝'},
                    {field: 'author_likes', title: '获赞'},
                    {field: 'video_like_count', title: '点赞'},
                    {field: 'comment_count', title: '评论'},
                    {field: 'status', title: '状态'},
                    {field: 'check_status', title: '检测状态'},
                    {field: 'check_error', title: '失败原因'},
                    {
                        field: 'operate',
                        title: '操作',
                        formatter: function (value, row, index) {
                            return '<a href="javascript:;" class="btn btn-xs btn-info btn-detail" data-id="' + row.id + '">详情</a>';
                        },
                        events: {
                            'click .btn-detail': function (e, value, row, index) {
                                e.stopPropagation();
                                Fast.api.open('douyinlinklog/detail/id/' + row.id, '历史记录详情');
                            }
                        }
                    }
                ]]
            });

            Table.api.bindevent(table);
        }
    };

    return Controller;
});
