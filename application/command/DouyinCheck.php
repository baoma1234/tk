<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class DouyinCheck extends Command
{
    protected function configure()
    {
        $this->setName('douyin:check')->setDescription('Check Douyin links and save history');
    }

    protected function execute(Input $input, Output $output)
    {
        $startTime = date('Y-m-d H:i:s');
        $output->writeln("[{$startTime}] douyin:check started.");

        $model = new \app\admin\model\Douyinlink();

        $list = Db::name('douyin_link')
            ->whereNull('delete_time')
            ->where(function ($query) {
                $query->where('check_status', 'pending')
                      ->whereOr('next_check_time', '<=', date('Y-m-d H:i:s'));
            })
            ->order('id asc')
            ->limit(20)
            ->select();

        if (empty($list)) {
            $output->writeln('No records to check.');
            $output->writeln('[]');
            return 0;
        }

        $output->writeln('Found ' . count($list) . ' records.');

        foreach ($list as $row) {
            $output->writeln("Checking ID={$row['id']} URL={$row['url']}");

            try {
                $result = $model->checkLink($row);
                if (!empty($result['success'])) {
                    $output->writeln("ID={$row['id']} success.");
                } else {
                    $output->writeln("ID={$row['id']} fail: " . ($result['error'] ?? 'unknown error'));
                }
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                $output->writeln("ID={$row['id']} exception: {$errorMessage}");

                Db::name('douyin_link_log')->insert([
                    'link_id' => (int)$row['id'],
                    'url' => $row['url'],
                    'final_url' => '',
                    'video_id' => '',
                    'author_name' => '',
                    'author_fans' => 0,
                    'author_likes' => 0,
                    'video_like_count' => 0,
                    'comment_count' => 0,
                    'status' => 'parse_fail',
                    'check_status' => 'fail',
                    'check_error' => $errorMessage,
                    'check_time' => date('Y-m-d H:i:s'),
                    'create_time' => time(),
                ]);

                Db::name('douyin_link')
                    ->where('id', $row['id'])
                    ->update([
                        'check_status' => 'fail',
                        'check_error' => $errorMessage,
                        'check_times' => Db::raw('check_times+1'),
                        'last_check_time' => date('Y-m-d H:i:s'),
                        'next_check_time' => date('Y-m-d H:i:s', time() + 600),
                        'update_time' => time(),
                    ]);
            }
        }

        $endTime = date('Y-m-d H:i:s');
        $output->writeln("[{$endTime}] douyin:check finished.");
        return 0;
    }
}
