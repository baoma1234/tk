<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;

class DouyinLinkLog extends Backend
{
    /**
     * 历史列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $linkId = $this->request->get('link_id/d', 0);

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $query = Db::name('douyin_link_log');
            if ($linkId > 0) {
                $query->where('link_id', $linkId);
            }

            $total = $query->where($where)->count();
            $list = $query->where($where)->order($sort, $order)->limit($offset, $limit)->select();

            return json([
                'total' => $total,
                'rows'  => $list
            ]);
        }

        return $this->view->fetch();
    }

    /**
     * 某条链接的历史记录
     */
    public function history($link_id = 0)
    {
        $this->assignconfig('link_id', (int)$link_id);
        return $this->view->fetch();
    }

    /**
     * 历史记录详情
     */
    public function detail($id = 0)
    {
        $row = Db::name('douyin_link_log')->where('id', $id)->find();
        if (!$row) {
            $this->error('记录不存在');
        }

        $this->view->assign('row', $row);
        return $this->view->fetch();
    }
}
