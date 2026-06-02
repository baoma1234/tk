<?php

namespace app\admin\model;

use think\Db;
use think\Model;

class Douyinlink extends Model
{
    protected $table = 'fa_douyin_link';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';

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
        } catch (\Throwable $e) {
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
        $delay = $times <= 1 ? 600 : ($times == 2 ? 1800 : ($times == 3 ? 7200 : 86400));

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
        $normalizedUrl = trim($url);
        if (!$normalizedUrl) {
            return ['success' => false, 'error' => '链接为空'];
        }

        if (!preg_match('#^https?://#i', $normalizedUrl)) {
            $normalizedUrl = 'https://' . ltrim($normalizedUrl, '/');
        }

        $finalUrl = $this->resolveShortUrl($normalizedUrl);
        if (!$finalUrl) {
            return ['success' => false, 'error' => '无法解析短链跳转地址'];
        }

        $html = $this->httpGet($finalUrl);
        if (!$html) {
            return ['success' => false, 'error' => '无法获取作品页面'];
        }

        $videoId = '';
        if (preg_match('#/video/(\d+)#', $finalUrl, $m)) {
            $videoId = $m[1];
        } elseif (preg_match('#item_ids?=([0-9]+)#', $finalUrl, $m)) {
            $videoId = $m[1];
        } elseif (preg_match('#"aweme_id"\s*:\s*"?(\d+)"?#', $html, $m)) {
            $videoId = $m[1];
        }

        $authorName = $this->extractByRegex($html, '/"nickname"\s*:\s*"([^"]+)"/');
        if (!$authorName) {
            $authorName = $this->extractByRegex($html, '/"author"\s*:\s*\{[^\}]*"nickname"\s*:\s*"([^"]+)"/s');
        }

        $authorFans = $this->extractNumberByRegex($html, '/"follower_count"\s*:\s*(\d+)/');
        if ($authorFans === 0) {
            $authorFans = $this->extractNumberByRegex($html, '/"fans_count"\s*:\s*(\d+)/');
        }

        $authorLikes = $this->extractNumberByRegex($html, '/"total_favorited"\s*:\s*(\d+)/');
        if ($authorLikes === 0) {
            $authorLikes = $this->extractNumberByRegex($html, '/"favorited_count"\s*:\s*(\d+)/');
        }

        $videoLikeCount = $this->extractNumberByRegex($html, '/"digg_count"\s*:\s*(\d+)/');
        if ($videoLikeCount === 0) {
            $videoLikeCount = $this->extractNumberByRegex($html, '/"like_count"\s*:\s*(\d+)/');
        }

        $commentCount = $this->extractNumberByRegex($html, '/"comment_count"\s*:\s*(\d+)/');
        $status = 'normal';

        if (stripos($html, '该视频不存在') !== false || stripos($html, '作品已删除') !== false) {
            $status = 'deleted';
        } elseif (stripos($html, '私密') !== false || stripos($html, 'private') !== false) {
            $status = 'private';
        } elseif (stripos($html, '审核中') !== false) {
            $status = 'reviewing';
        }

        return [
            'success' => true,
            'final_url' => $finalUrl,
            'video_id' => $videoId,
            'author_name' => $authorName ?: '未知作者',
            'author_fans' => $authorFans,
            'author_likes' => $authorLikes,
            'video_like_count' => $videoLikeCount,
            'comment_count' => $commentCount,
            'status' => $status,
            'error' => ''
        ];
    }

    protected function resolveShortUrl($url)
    {
        $current = $url;
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

        for ($i = 0; $i < 5; $i++) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $current,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT => $ua,
                CURLOPT_ENCODING => '',
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                    'Connection: keep-alive',
                    'Cache-Control: no-cache',
                    'Pragma: no-cache',
                    'Upgrade-Insecure-Requests: 1',
                ],
            ]);

            $response = curl_exec($ch);
            $info = curl_getinfo($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($errno) {
                continue;
            }

            if (!empty($info['redirect_url'])) {
                $current = $info['redirect_url'];
                if (preg_match('#^https?://#i', $current) && stripos($current, 'v.douyin.com') === false) {
                    return $current;
                }
                continue;
            }

            if (is_string($response) && preg_match('/^HTTP\/\d\.\d\s+30\d/m', $response) && preg_match('/Location:\s*(.+?)\r?\n/i', $response, $m)) {
                $current = trim($m[1]);
                if (preg_match('#^https?://#i', $current) && stripos($current, 'v.douyin.com') === false) {
                    return $current;
                }
                continue;
            }

            if (preg_match('#^https?://#i', $current) && stripos($current, 'v.douyin.com') === false) {
                return $current;
            }
        }

        return false;
    }

    protected function httpGet($url)
    {
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => $ua,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                'Connection: keep-alive',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
            ],
        ]);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    protected function extractByRegex($html, $pattern)
    {
        if (preg_match($pattern, $html, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    protected function extractNumberByRegex($html, $pattern)
    {
        if (preg_match($pattern, $html, $m)) {
            return (int)$m[1];
        }
        return 0;
    }
}
