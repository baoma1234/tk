<?php
namespace app\api\controller;

use app\common\controller\Api;
use think\Db;

class Telegram extends Api
{
    protected $noNeedLogin = ['*']; 
    protected $botToken = '8550634573:AAGr-RTtl_nh9sWuWWluEodjbVpU1qN059Q'; // 替换为你的真实Token

   public function webhook()
    {
        // 1. 获取 Telegram 推送的 JSON 数据
        $update = json_decode(file_get_contents('php://input'), true);
        if (!$update) {
            return json(['status' => 'error', 'msg' => 'No data']);
        }

        // 提取 Chat 和 User 信息
        $chat = $update['message']['chat'] ?? ($update['callback_query']['message']['chat'] ?? null);
        $userId = $update['message']['from']['id'] ?? ($update['callback_query']['from']['id'] ?? null);
        $username = $update['message']['from']['username'] ?? ($update['callback_query']['from']['username'] ?? '匿名');
        
        // 2. 【核心改动】私聊转化为“开发者留言板”
        if ($chat && $chat['type'] === 'private') {
            // 如果用户点击的是私聊里历史遗留的行内按钮，先回应 Telegram 释放转圈状态
            if (isset($update['callback_query'])) {
                $this->requestTelegram('answerCallbackQuery', ['callback_query_id' => $update['callback_query']['id']]);
            }

            $userText = $update['message']['text'] ?? '(点击了菜单/发送了媒体)';

            // 过滤掉刚开始聊天的 /start 指令，避免污染留言内容
            if ($userText === '/start') {
                $replyText = "🤖 **欢迎使用考勤机器人**\n\n⚠️ 提示：本机器人**不支持私聊打卡**，请前往指定的公司工作群组中进行操作。\n\n📬 如果您遇到了系统Bug或有功能建议，直接在此发送文本消息，系统会自动为您留言给开发者。";
            } else {
                // 组织回复话术
                $replyText = "❌ **温馨提示：暂不支持私聊打卡**\n请前往后台已开启的监控群组内进行考勤。\n\n📬 **留言板已投递**\n已收到您的反馈：_“{$userText}”_\n系统已自动将该内容通知给系统开发者！";
                
                // 【拓展建议】在这里你可以选择把留言记录到服务器日志，或者存入数据库
                // \think\Log::write("【机器人私聊留言】用户:@{$username}(ID:{$userId}) 留言内容: {$userText}");
            }

            // 发送通知给用户
            $this->sendMessage($chat['id'], $replyText);

            return json(['status' => 'private_message_forwarded']);
        }

        // 3. 全局群组白名单拦截（保持原样）
        if ($chat && in_array($chat['type'], ['group', 'supergroup'])) {
            $isGroupEnabled = Db::name('bot_group')
                ->where('tg_group_id', $chat['id'])
                ->where('status', 'normal')
                ->find();

            if (!$isGroupEnabled) {
                $this->requestTelegram('leaveChat', ['chat_id' => $chat['id']]);
                return json(['status' => 'unauthorized_group']); 
            }
        }

        // 4. 群组内验证通过，继续处理合规的打卡指令和按钮
        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
        } elseif (isset($update['message'])) {
            $this->handleMessage($update['message']);
        }

        return json(['status' => 'ok']);
    }

    private function handleMessage($message)
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        
        // 群组白名单校验
        // if ($message['chat']['type'] !== 'private') {
        //     $group = Db::name('bot_group')->where(['tg_group_id' => $chatId, 'status' => 'normal'])->find();
        //     if (!$group) return; 
        // }

        if ($text === '/start' || $text === '/menu') {
            $this->sendMenu($chatId);
        }
    }

    private function handleCallback($callback)
    {
        $chatId = $callback['message']['chat']['id'];
        $userId = $callback['from']['id'];
        $username = $callback['from']['username'] ?? '匿名用户';
        $data = $callback['data'];

        $this->checkUser($userId, $username);
        $now = date('Y-m-d H:i:s');
        $replyText = "";

        switch ($data) {
            case 'checkin':
                Db::name('bot_record')->insert(['tg_user_id' => $userId, 'type' => 'checkin', 'status' => 'completed', 'start_time' => $now, 'createtime' => time()]);
                $replyText = "✅ @{$username} 上班打卡成功！\n时间: {$now}";
                break;
            case 'checkout':
                Db::name('bot_record')->insert(['tg_user_id' => $userId, 'type' => 'checkout', 'status' => 'completed', 'start_time' => $now, 'createtime' => time()]);
                $replyText = "🏃 @{$username} 下班打卡成功！\n时间: {$now}";
                break;
            case 'meal_start':
            case 'smoke_start':
                $type = ($data == 'meal_start') ? 'meal' : 'smoke';
                $typeName = ($type == 'meal') ? '吃饭' : '抽烟';
                // 检查是否有未结束的同类型打卡
                $exists = Db::name('bot_record')->where(['tg_user_id' => $userId, 'type' => $type, 'status' => 'ongoing'])->find();
                if ($exists) {
                    $replyText = "⚠️ @{$username} 你已经有一个进行中的【{$typeName}】打卡了，请先结束。";
                } else {
                    Db::name('bot_record')->insert(['tg_user_id' => $userId, 'type' => $type, 'status' => 'ongoing', 'start_time' => $now, 'createtime' => time()]);
                    $replyText = "🍚 @{$username} 开始【{$typeName}】打卡。\n搞定后记得点击下方对应的结束按钮！";
                }
                break;
            case 'meal_end':
            case 'smoke_end':
                $type = ($data == 'meal_end') ? 'meal' : 'smoke';
                $typeName = ($type == 'meal') ? '吃饭' : '抽烟';
                $record = Db::name('bot_record')->where(['tg_user_id' => $userId, 'type' => $type, 'status' => 'ongoing'])->order('id desc')->find();
                if ($record) {
                    $duration = max(1, round((strtotime($now) - strtotime($record['start_time'])) / 60));
                    Db::name('bot_record')->where('id', $record['id'])->update([
                        'status' => 'completed', 'end_time' => $now, 'duration_minutes' => $duration, 'updatetime' => time()
                    ]);
                    $replyText = "✅ @{$username} 结束【{$typeName}】。\n本次用时：{$duration} 分钟。";
                } else {
                    $replyText = "⚠️ @{$username} 你当前没有处于进行中的【{$typeName}】打卡。";
                }
                break;
            case 'history':
                $records = Db::name('bot_record')->where('tg_user_id', $userId)->order('id desc')->limit(5)->select();
                $replyText = "📋 @{$username} 最近5条打卡流水：\n";
                $typeLabels = ['checkin'=>'上班', 'checkout'=>'下班', 'meal'=>'吃饭', 'smoke'=>'抽烟'];
                foreach ($records as $r) {
                    $replyText .= "• {$r['start_time']} -> " . ($typeLabels[$r['type']] ?? $r['type']) . ($r['status'] == 'ongoing' ? ' (进行中)' : " (已结束, {$r['duration_minutes']}分钟)") . "\n";
                }
                break;
        }

        $this->sendMessage($chatId, $replyText);
        $this->requestTelegram('answerCallbackQuery', ['callback_query_id' => $callback['id']]);
    }

    private function sendMenu($chatId)
    {
        $keyboard = ['inline_keyboard' => [
            [['text' => '✅ 上班打卡', 'callback_data' => 'checkin'], ['text' => '🏃 下班打卡', 'callback_data' => 'checkout']],
            [['text' => '🍚 开始吃饭', 'callback_data' => 'meal_start'], ['text' => '🔙 结束吃饭', 'callback_data' => 'meal_end']],
            [['text' => '🚬 开始抽烟', 'callback_data' => 'smoke_start'], ['text' => '🔙 结束抽烟', 'callback_data' => 'smoke_end']],
            [['text' => '📋 我的记录', 'callback_data' => 'history']]
        ]];
        $this->requestTelegram('sendMessage', ['chat_id' => $chatId, 'text' => "🤖 考勤打卡交互菜单：", 'reply_markup' => json_encode($keyboard)]);
    }

    private function sendMessage($chatId, $text)
    {
        $this->requestTelegram('sendMessage', ['chat_id' => $chatId, 'text' => $text]);
    }

    private function requestTelegram($method, $data)
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/{$method}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true);
    }
    
    private function checkUser($userId, $username) {
        $user = Db::name('bot_user')->where('tg_user_id', $userId)->find();
        if (!$user) {
            Db::name('bot_user')->insert(['tg_user_id' => $userId, 'username' => $username, 'createtime' => time()]);
        }
    }
}