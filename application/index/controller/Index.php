<?php

namespace app\index\controller;


use app\common\controller\Frontend;
use think\Session;
use think\Db;
use think\Config;
class Index extends Frontend
{


    protected $noNeedLogin = ['index','index2']; // 首页允许游客访问以记录邀请标识

    public function index()
    {
        // 1. 处理邀请后缀逻辑 (保持不变)
        // 记录邀请来源
        $url_id = $this->request->get('id');
        if ($url_id) {
            Session::set('active_invite_id', $url_id);
        }

        $user = $this->auth->getUser();
        $game_id = $user ? (Session::get('user_game_id') ?: $user->bio) : '';

        // 从系统配置读取链接
        $site = Config::get('site')['categorytype'];
        $invite_base = $site['invite_base_url'] ?? $this->request->domain();
        $customer_url = $site['customer_service_url'] ?? '#';
        
        $this->assign([
            'user'         => $user,
            'game_id'      => $game_id,
            'count'        => $user ? Db::name('invite_log')->where('inviter_id', $user->id)->where('is_recharge', 1)->count() : 0,
            'invite_base'  => $invite_base,
            'customer_url' => $customer_url
        ]);

        return $this->view->fetch('index');
    }

public function index2()
    {
        // 1. 处理邀请后缀逻辑 (保持不变)
        // 记录邀请来源
        $url_id = $this->request->get('id');
        if ($url_id) {
            Session::set('active_invite_id', $url_id);
        }

        $user = $this->auth->getUser();
        $game_id = $user ? (Session::get('user_game_id') ?: $user->bio) : '';

        // 从系统配置读取链接
        $site = Config::get('site');
        $invite_base = $site['invite_base_url'] ?? $this->request->domain() . '/index/promote/index';
        $customer_url = $site['customer_service_url'] ?? '#';

        $this->assign([
            'user'         => $user,
            'game_id'      => $game_id,
            'count'        => $user ? Db::name('invite_log')->where('inviter_id', $user->id)->where('is_recharge', 1)->count() : 0,
            'invite_base'  => $invite_base,
            'customer_url' => $customer_url
        ]);

        return $this->view->fetch('index');
    }
    /**
     * 绑定游戏ID并激活推广资格
     */
    /**
     * 绑定ID接口
     */
    public function bind_id()
    {
        if (!$this->auth->isLogin()) $this->error('请先登录');

        $game_id = $this->request->post('game_id');
        if (!$game_id) $this->error('ID不能为空');

        $user = $this->auth->getUser();

        // 存数据库
        Db::name('user')->where('id', $user->id)->update([
            'bio' => $game_id,
            'is_promoter' => 1
        ]);

        // 存 Session
        Session::set('user_game_id', $game_id);

        $this->success('激活成功', null, ['game_id' => $game_id]);
    }
}
