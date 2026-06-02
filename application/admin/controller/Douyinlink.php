<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;

class Douyinlink extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Douyinlink;
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->where($where)->count();
            $list = $this->model->where($where)->order($sort, $order)->limit($offset, $limit)->select();
            return json(['total' => $total, 'rows' => $list]);
        }

        return $this->view->fetch();
    }

    public function batchAdd()
    {
        return $this->batch_add();
    }

    public function batch_add()
    {
        if ($this->request->isPost()) {
            $content = $this->request->post('content/s', '');
            if (!$content) {
                $this->error('请输入批量内容');
            }

            $lines = preg_split('/\r\n|\r|\n/', trim($content));
            $insertData = [];
            $now = time();

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $parts = explode('|', $line, 2);
                $url = trim($parts[0] ?? '');
                $remark = trim($parts[1] ?? '');
                if (!$url) {
                    continue;
                }

                $exists = $this->model->where('url', $url)->find();
                if ($exists) {
                    continue;
                }

                $insertData[] = [
                    'url' => $url,
                    'remark' => $remark,
                    'final_url' => '',
                    'video_id' => '',
                    'author_name' => '',
                    'author_fans' => 0,
                    'author_likes' => 0,
                    'video_like_count' => 0,
                    'comment_count' => 0,
                    'status' => 'pending',
                    'check_status' => 'pending',
                    'check_error' => '',
                    'check_times' => 0,
                    'last_check_time' => null,
                    'next_check_time' => date('Y-m-d H:i:s', $now),
                    'create_time' => $now,
                    'update_time' => $now,
                ];
            }

            if (empty($insertData)) {
                $this->error('没有可插入的数据');
            }

            Db::name('douyin_link')->insertAll($insertData);
            $this->success('批量添加成功，共插入 ' . count($insertData) . ' 条');
        }

        return $this->view->fetch('batch_add');
    }

    public function checkNow($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error('记录不存在');
        }

        $result = $this->model->checkLink($row);
        if (!empty($result['success'])) {
            $this->success('检测成功');
        } else {
            $this->error($result['error'] ?? '检测失败');
        }
    }

    public function checkBatch()
    {
        $ids = $this->request->post('ids/a', []);
        if (empty($ids)) {
            $this->error('请选择记录');
        }

        $list = $this->model->where('id', 'in', $ids)->select();
        foreach ($list as $row) {
            $this->model->checkLink($row);
        }

        $this->success('批量检测完成');
    }

    /**
     * 软删除/停检
     */
    public function delete($ids = null)
    {
        if (!$ids) {
            $this->error('参数错误');
        }

        $idsArr = is_array($ids) ? $ids : explode(',', (string)$ids);
        $now = date('Y-m-d H:i:s');

        Db::name('douyin_link')
            ->where('id', 'in', $idsArr)
            ->update([
                'status' => 'invalid',
                'check_status' => 'fail',
                'check_error' => '手动删除',
                'next_check_time' => null,
                'update_time' => time(),
            ]);

        foreach ($idsArr as $id) {
            $row = Db::name('douyin_link')->where('id', (int)$id)->find();
            if ($row) {
                Db::name('douyin_link_log')->insert([
                    'link_id' => (int)$row['id'],
                    'url' => $row['url'],
                    'final_url' => $row['final_url'] ?? '',
                    'video_id' => $row['video_id'] ?? '',
                    'author_name' => $row['author_name'] ?? '',
                    'author_fans' => (int)($row['author_fans'] ?? 0),
                    'author_likes' => (int)($row['author_likes'] ?? 0),
                    'video_like_count' => (int)($row['video_like_count'] ?? 0),
                    'comment_count' => (int)($row['comment_count'] ?? 0),
                    'status' => 'invalid',
                    'check_status' => 'fail',
                    'check_error' => '手动删除',
                    'check_time' => $now,
                    'create_time' => time(),
                ]);
            }
        }

        $this->success('删除成功，已停止后续检测');
    }
}
