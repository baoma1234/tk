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
        $lockFile = $this->getLockFile();

        if ($this->isLocked($lockFile)) {
            $this->logLine($output, 'another process is running, exit', true);
            return 0;
        }

        $this->createLock($lockFile);

        try {
            $this->logLine($output, 'douyin:check started', true);

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
                $this->logLine($output, 'No records to check', true);
                $this->logLine($output, 'douyin:check finished', true);
                return 0;
            }

            $this->logLine($output, 'Found ' . count($list) . ' records', true);

            foreach ($list as $row) {
                $this->logLine($output, 'Checking ID=' . $row['id'] . ' URL=' . $row['url']);

                try {
                    $result = $model->checkLink($row);
                    if (!empty($result['success'])) {
                        $this->logLine($output, 'ID=' . $row['id'] . ' success', true);
                    } else {
                        $this->logLine($output, 'ID=' . $row['id'] . ' fail: ' . ($result['error'] ?? 'unknown error'), true);
                    }
                } catch (\Throwable $e) {
                    $errorMessage = $e->getMessage();
                    $this->logLine($output, 'ID=' . $row['id'] . ' exception: ' . $errorMessage, true);
                    $this->saveFailure($row, $errorMessage);
                }
            }

            $this->logLine($output, 'douyin:check finished', true);
            return 0;
        } catch (\Throwable $e) {
            $this->logLine($output, 'fatal error: ' . $e->getMessage(), true);
            return 1;
        } finally {
            $this->removeLock($lockFile);
        }
    }

    protected function logLine(Output $output, $message, $newline = true)
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;

        if ($newline) {
            $output->writeln($line);
        } else {
            $output->write($line);
        }

        $this->appendLog($line);
    }

    protected function getRuntimeDir()
    {
        if (defined('RUNTIME_PATH')) {
            return rtrim(RUNTIME_PATH, DIRECTORY_SEPARATOR);
        }

        return rtrim(ROOT_PATH . 'runtime', DIRECTORY_SEPARATOR);
    }

    protected function getLockFile()
    {
        return $this->getRuntimeDir() . DIRECTORY_SEPARATOR . 'douyin_check.lock';
    }

    protected function isLocked($lockFile)
    {
        if (!file_exists($lockFile)) {
            return false;
        }

        $mtime = @filemtime($lockFile);
        if ($mtime === false) {
            return false;
        }

        // 超过30分钟的锁视为失效，防止异常退出后一直锁住
        if (time() - $mtime > 1800) {
            @unlink($lockFile);
            return false;
        }

        return true;
    }

    protected function createLock($lockFile)
    {
        $dir = dirname($lockFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        @file_put_contents($lockFile, getmypid() . '|' . date('Y-m-d H:i:s'));
    }

    protected function removeLock($lockFile)
    {
        if (file_exists($lockFile)) {
            @unlink($lockFile);
        }
    }

    protected function appendLog($line)
    {
        $runtimeDir = $this->getRuntimeDir();
        $logDir = $runtimeDir . DIRECTORY_SEPARATOR . 'log';
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'douyin_check.log';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
    }

    protected function saveFailure($row, $errorMessage)
    {
        try {
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
        } catch (\Throwable $e) {
            $this->appendLog('history insert failed for ID=' . $row['id'] . ': ' . $e->getMessage());
        }

        try {
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
        } catch (\Throwable $e) {
            $this->appendLog('main table update failed for ID=' . $row['id'] . ': ' . $e->getMessage());
        }
    }
}