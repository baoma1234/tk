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
        $this->safeWrite($output, 'douyin:check started', true);

        $model = new \app\admin\model\Douyinlink();
        $now = date('Y-m-d H:i:s');

        $list = Db::name('douyin_link')
            ->whereNull('delete_time')
            ->where(function ($query) use ($now) {
                $query->where('check_status', 'pending')
                    ->whereOr('next_check_time', '<=', $now)
                    ->whereOr('next_check_time', null);
            })
            ->order('id asc')
            ->limit(20)
            ->select();

        if (empty($list)) {
            $this->safeWrite($output, 'No records to check');
            return 0;
        }

        $this->safeWrite($output, 'Found ' . count($list) . ' records');

        foreach ($list as $row) {
            $this->safeWrite($output, 'Checking ID=' . $row['id'] . ' URL=' . $row['url']);

            try {
                $result = $model->checkLink($row);
                if (!empty($result['success'])) {
                    $this->safeWrite($output, 'ID=' . $row['id'] . ' success');
                } else {
                    $this->safeWrite($output, 'ID=' . $row['id'] . ' fail: ' . ($result['error'] ?? 'unknown error'));
                }
            } catch (\Throwable $e) {
                $errorMessage = $e->getMessage();
                $this->safeWrite($output, 'ID=' . $row['id'] . ' exception: ' . $errorMessage);
                $this->logFailure($row, $errorMessage);
            }
        }

        $this->safeWrite($output, 'douyin:check finished', true);
        return 0;
    }

    protected function safeWrite(Output $output, $message, $newline = false)
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        if ($newline) {
            $output->writeln($line);
        } else {
            $output->writeln($line);
        }

        if (function_exists('write_log')) {
            write_log($line, 'info');
        } else {
            @file_put_contents(runtime_path() . 'log/douyin_check.log', $line . PHP_EOL, FILE_APPEND);
        }
    }

    protected function logFailure($row, $errorMessage)
    {
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

        $times = (int)$row['check_times'] + 1;
        $delay = ($times <= 1) ? 600 : (($times == 2) ? 1800 : (($times == 3) ? 7200 : 86400));

        Db::name('douyin_link')
            ->where('id', $row['id'])
            ->update([
                'check_status' => 'fail',
                'check_error' => $errorMessage,
                'check_times' => Db::raw('check_times+1'),
                'last_check_time' => date('Y-m-d H:i:s'),
                'next_check_time' => date('Y-m-d H:i:s', time() + $delay),
                'update_time' => time(),
            ]);
    }
}
