<?php

namespace app\admin\model;

use think\Db;
use think\Model;

class DouyinLink extends Model
{
    protected $table = 'fa_douyin_link';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';

    /**
     * 检测单条链接并保存历史
     */
    public function checkLink($row)
    {
        $now = date('Y-m-d H:i:s');

        try {
            $data = $this->parseDouyinLink($row['url']);

            if (empty($data['success'])) {
                $errorMessage = $data['error'] ?? '解析失败';
                $this->saveHistory($row, [
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
                    'check_time' => $now,
                ]);

                $this->updateRetryState($row, $errorMessage, $now);
                return ['success' => false, 'error' => $errorMessage];
            }

            $this->saveHistory($row, [
                'final_url' => $data['final_url'] ?? '',
                'video_id' => $data['video_id'] ?? '',
                'author_name' => $data['author_name'] ?? '',
                'author_fans' => (int)($data['author_fans'] ?? 0),
                'author_likes' => (int)($data['author_likes'] ?? 0),
                'video_like_count' => (int)($data['video_like_count'] ?? 0),
                'comment_count' => (int)($data['comment_count'] ?? 0),
                'status' => $data['status'] ?? 'normal',
                'check_status' => 'success',
                'check_error' => '',
                'check_time' => $now,
            ]);

            $row->save([
                'final_url' => $data['final_url'] ?? '',
                'video_id' => $data['video_id'] ?? '',
                'author_name' => $data['author_name'] ?? '',
                'author_fans' => (int)($data['author_fans'] ?? 0),
                'author_likes' => (int)($data['author_likes'] ?? 0),
                'video_like_count' => (int)($data['video_like_count'] ?? 0),
                'comment_count' => (int)($data['comment_count'] ?? 0),
                'status' => $data['status'] ?? 'normal',
                'check_status' => 'success',
                'check_error' => '',
                'check_times' => Db::raw('check_times+1'),
                'last_check_time' => $now,
                'next_check_time' => date('Y-m-d H:i:s', time() + 3600),
                'update_time' => time(),
            ]);

            return $data;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->saveHistory($row, [
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
                'check_time' => $now,
            ]);

            $this->updateRetryState($row, $errorMessage, $now);
            return ['success' => false, 'error' => $errorMessage];
        }
    }

    protected function updateRetryState($row, $errorMessage, $now)
    {
        $times = (int)$row['check_times'] + 1;
        if ($times == 1) {
            $delay = 600;
        } elseif ($times == 2) {
            $delay = 1800;
        } elseif ($times == 3) {
            $delay = 7200;
        } else {
            $delay = 86400;
        }

        $row->save([
            'check_status' => 'fail',
            'check_error' => $errorMessage,
            'check_times' => Db::raw('check_times+1'),
            'last_check_time' => $now,
            'next_check_time' => date('Y-m-d H:i:s', time() + $delay),
            'update_time' => time(),
        ]);
    }

    protected function saveHistory($row, array $data)
    {
        Db::name('douyin_link_log')->insert([
            'link_id' => (int)$row['id'],
            'url' => $row['url'] ?? '',
            'final_url' => $data['final_url'] ?? '',
            'video_id' => $data['video_id'] ?? '',
            'author_name' => $data['author_name'] ?? '',
            'author_fans' => (int)($data['author_fans'] ?? 0),
            'author_likes' => (int)($data['author_likes'] ?? 0),
            'video_like_count' => (int)($data['video_like_count'] ?? 0),
            'comment_count' => (int)($data['comment_count'] ?? 0),
            'status' => $data['status'] ?? 'pending',
            'check_status' => $data['check_status'] ?? 'pending',
            'check_error' => $data['check_error'] ?? '',
            'check_time' => $data['check_time'] ?? date('Y-m-d H:i:s'),
            'create_time' => time(),
        ]);
    }

    public function parseDouyinLink($url)
    {
        $finalUrl = $this->getFinalUrl($url);
        if (!$finalUrl) {
            return ['success' => false, 'error' => '无法解析短链'];
        }

        $html = $this->httpGet($finalUrl);
        if (!$html) {
            return ['success' => false, 'error' => '无法获取作品页面'];
        }

        preg_match('/video\/(\d+)/', $finalUrl, $m);
        $videoId = $m[1] ?? '';

        return [
            'success' => true,
            'final_url' => $finalUrl,
            'video_id' => $videoId,
            'author_name' => '未知作者',
            'author_fans' => 0,
            'author_likes' => 0,
            'video_like_count' => 0,
            'comment_count' => 0,
            'status' => 'normal',
            'error' => ''
        ];
    }

    protected function getFinalUrl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (!$response) {
            return false;
        }

        if (!empty($info['redirect_url'])) {
            return $info['redirect_url'];
        }

        if (preg_match('/Location:\s*(.+?)\r\n/i', $response, $match)) {
            return trim($match[1]);
        }

        return false;
    }

    protected function httpGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}
