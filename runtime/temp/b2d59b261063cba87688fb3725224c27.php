<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:74:"/www/wwwroot/wx.jyzf.bio/public/../application/index/view/index/index.html";i:1778411293;}*/ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>推3返1 · 官方返现中心</title>
    
    <link rel="stylesheet" href="https://cdn.staticfile.org/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        :root {
            --primary-gold: #ffcf4d;
            --deep-gold: #b38a1d;
            --text-gold: #ffebad;
            --bg-black: #050505;
            --glass-white: rgba(255, 255, 255, 0.05);
            --neon-blue: #00f2ff;
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; outline: none; }
        body, html { 
            margin: 0; padding: 0; height: 100%; 
            background-color: var(--bg-black); 
            font-family: "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif;
            color: #fff; overflow-x: hidden;
        }

        /* 动态流动背景 */
        .cyber-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 50% 0%, #1a1510 0%, #050505 70%);
            z-index: -1;
        }
        .cyber-bg::before {
            content: ''; position: absolute; width: 100%; height: 100%;
            background-image: linear-gradient(rgba(255,207,77,0.03) 1px, transparent 1px), 
                              linear-gradient(90deg, rgba(255,207,77,0.03) 1px, transparent 1px);
            background-size: 30px 30px;
        }

        /* 手机外壳容器 (PC适配) */
        .main-shell {
            width: 100%; max-width: 450px; min-height: 100vh;
            margin: 0 auto; position: relative;
            background: rgba(10, 10, 10, 0.8);
            display: flex; flex-direction: column;
            box-shadow: 0 0 100px rgba(0,0,0,1);
        }

        @media (min-width: 501px) {
            body { display: flex; align-items: center; justify-content: center; background: #000; }
            .main-shell {
                height: 850px; border-radius: 50px; border: 12px solid #222;
                overflow-y: auto; scrollbar-width: none;
            }
        }

        /* 震撼标题区 */
        .hero-banner {
            padding: 40px 20px; text-align: center;
            background: linear-gradient(180deg, rgba(255,207,77,0.1) 0%, transparent 100%);
        }
        .hero-banner h1 {
            font-size: 32px; font-weight: 900; margin: 0;
            background: linear-gradient(180deg, #fff 30%, var(--primary-gold) 100%);
            -webkit-background-clip: text; color: transparent;
            filter: drop-shadow(0 0 15px rgba(255,207,77,0.4));
        }
        .hero-banner .sub-title {
            font-size: 14px; color: var(--neon-blue); letter-spacing: 3px;
            margin-top: 10px; font-weight: bold; opacity: 0.8;
        }

        /* 进度可视化 */
        .progress-section {
            margin: 20px; padding: 25px;
            background: var(--glass-white); border-radius: 24px;
            border: 1px solid rgba(255,207,77,0.15);
            text-align: center;
        }
        .progress-box {
            display: flex; justify-content: space-around; margin: 20px 0;
        }
        .step { position: relative; }
        .step-circle {
            width: 45px; height: 45px; border-radius: 50%;
            background: #222; border: 2px solid #444;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; color: #666; transition: 0.4s;
        }
        .step.active .step-circle {
            background: var(--primary-gold); border-color: var(--primary-gold);
            color: #4a3200; box-shadow: 0 0 15px var(--primary-gold);
        }
        .step-label { font-size: 11px; margin-top: 8px; color: #888; }
        .step.active .step-label { color: var(--primary-gold); }

        /* 规则展示卡片 */
        .rule-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 0 20px;
        }
        .rule-item {
            background: rgba(255,255,255,0.03); padding: 15px; border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .rule-item i { color: var(--primary-gold); font-size: 18px; margin-bottom: 8px; }
        .rule-item div { font-size: 13px; font-weight: bold; margin-bottom: 4px; }
        .rule-item p { font-size: 11px; color: #777; margin: 0; }

        /* 核心动作区 */
        .action-footer {
            padding: 30px 20px; text-align: center;
        }
        .id-input-wrap {
            background: #000; border: 1px solid #333; border-radius: 18px;
            padding: 5px; margin-bottom: 20px; display: flex;
        }
        .id-input-wrap input {
            flex: 1; background: transparent; border: none; color: #fff;
            padding: 15px; font-size: 16px; text-align: center;
        }
        .btn-main {
            width: 100%; padding: 20px; border: none; border-radius: 20px;
            background: linear-gradient(135deg, #ffcf4d 0%, #b38a1d 100%);
            color: #4a3200; font-size: 18px; font-weight: 900;
            box-shadow: 0 15px 40px rgba(179,138,29,0.4);
            cursor: pointer; transition: 0.3s;
        }
        .btn-main:active { transform: scale(0.96); }

        /* 全屏弹窗居中算法 */
        .modal-overlay {
            display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.98); z-index: 1000;
            display: none; align-items: center; justify-content: center;
            flex-direction: column;
        }
        .poster-container {
            width: 85%; max-width: 340px;
            position: relative;
            animation: modalShow 0.4s cubic-bezier(0.18, 0.89, 0.32, 1.28);
        }
        @keyframes modalShow { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        
        .poster-container img {
            width: 100%; border-radius: 5px; border: 1px solid #444;
            box-shadow: 0 30px 80px rgba(0,0,0,0.8);
        }

        /* 海报模板 */
        #poster-tpl {
            position: fixed; left: -9999px; width: 375px; height: 600px;
            background: #000; overflow: hidden;
        }
        .tpl-inner {
            width: 100%; height: 100%; padding: 40px;
            background: radial-gradient(circle at 50% 0%, #2a2010 0%, #000 70%);
            display: flex; flex-direction: column; align-items: center;
            border: 2px solid var(--primary-gold);
        }
    </style>
</head>
<body>

<div class="cyber-bg"></div>

<div class="main-shell">
    <header class="hero-banner">
        <div style="font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 2px;">Elite Affiliate Program</div>
        <h1>邀3友 · 全额返</h1>
        <div class="sub-title">你充多少 · 我退多少</div>
    </header>

     <div class="rule-grid">
        <div class="rule-item">
            <i class="fa fa-users"></i>
            <div>有效用户</div>
            <p>从未注册、登录、产生消费的新账号</p>
        </div>
        <div class="rule-item">
            <i class="fa fa-credit-card"></i>
            <div>达标动作</div>
            <p>新用户完成首次充值即算有效邀请</p>
        </div>
        <div class="rule-item">
            <i class="fa fa-refresh"></i>
            <div>1倍流水</div>
            <p>返现金额进入会员账户，1倍流水可提</p>
        </div>
        <div class="rule-item">
            <i class="fa fa-shield"></i>
            <div>风控稽查</div>
            <p>严禁刷单、外挂，一经发现封禁账号</p>
        </div>
    </div>

   

    <div class="action-footer">
        <div class="id-input-wrap">
            <input type="text" id="game_id" placeholder="请输入您的游戏ID以激活" value="<?php echo $game_id; ?>">
        </div>
        <button class="btn-main" onclick="doBind()">
            <i class="fa fa-share-alt mr-1"></i>  激活并生成专属海报
        
        </button>
        
<div style="margin-top: 30px; position: relative;">
    <a href="<?php echo $customer_url; ?>" style="text-decoration: none; display: block;">
        <div style="
            position: relative;
            padding: 2px; /* 留出流光边框的空间 */
            background: linear-gradient(90deg, transparent, #00f2ff, #ffcf4d, transparent);
            background-size: 200% 100%;
            animation: border-flow 3s linear infinite;
            border-radius: 16px;
        ">
            <div style="
                background: #111; 
                border-radius: 15px; 
                padding: 15px 20px; 
                display: flex; 
                align-items: center; 
                justify-content: space-between;
            ">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="
                        width: 45px; 
                        height: 45px; 
                        background: #00f2ff; 
                        border-radius: 12px; 
                        display: flex; 
                        align-items: center; 
                        justify-content: center;
                        box-shadow: 0 0 15px rgba(0,242,255,0.3);
                    ">
                        <i class="fa fa-headphones" style="color: #000; font-size: 24px;"></i>
                    </div>
                    
                    <div style="text-align: left;">
                        <div style="color: #00f2ff; font-weight: 900; font-size: 16px; display: flex; align-items: center; gap: 6px;">
                            官方客服 · 登记审核中心
                            <span style="
                                display: inline-block; 
                                width: 8px; 
                                height: 8px; 
                                background: #00ff00; 
                                border-radius: 50%; 
                                animation: blink 1s infinite;
                                box-shadow: 0 0 5px #00ff00;
                            "></span>
                        </div>
                        <p style="color: #888; font-size: 12px; margin: 3px 0 0;">满3人达标后点此提交ID审核到账</p>
                    </div>
                </div>
                <i class="fa fa-angle-right" style="color: #00f2ff; font-size: 20px; opacity: 0.5;"></i>
            </div>
        </div>
    </a>
</div>

<style>
/* 流光边框动画 */
@keyframes border-flow {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

/* 呼吸灯动画 */
@keyframes blink {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.3; transform: scale(0.8); }
}
</style>
    </div>
<div class="progress-section">
        <div style="font-size: 14px; font-weight: bold; margin-bottom: 5px;">我的裂变进度</div>
        <div style="font-size: 11px; color: #666;">达成 3 人有效邀请，立即返现到账</div>
        <div class="progress-box">
            <div class="step active"><div class="step-circle"><i class="fa fa-check"></i></div><div class="step-label">获得资格</div></div>
            <div class="step"><div class="step-circle">1</div><div class="step-label">首位邀请</div></div>
            <div class="step"><div class="step-circle">2</div><div class="step-label">再接再厉</div></div>
            <div class="step"><div class="step-circle">3</div><div class="step-label">全额返现</div></div>
        </div>
    </div>

    <div id="poster-mask" class="modal-overlay">
        <div class="poster-container" id="poster-res">
            <div style="text-align: center; color: var(--primary-gold);">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
                <p>专属海报渲染中...</p>
            </div>
        </div>
        <div style="width: 85%; max-width: 340px; margin-top: 30px; text-align: center;">
            <button class="btn-main" style="height: 55px; font-size: 16px;" onclick="copyAction()">复制专属链接</button>
            <p style="margin-top: 15px; color: #888; font-size: 13px;" onclick="$('#poster-mask').css('display','none')">
                <i class="fa fa-times-circle"></i> 关闭预览
            </p>
        </div>
    </div>
</div>

<div id="poster-tpl">
    <div class="tpl-inner">
        <div style="color: var(--primary-gold); font-size: 46px; font-weight: 900; letter-spacing: 2px;">全额返现</div>
        <div style="color: #fff; font-size: 16px; opacity: 0.6; margin-top: 10px;">CASHBACK REWARD</div>
        
        <div style="margin-top: 50px; border: 1px solid var(--primary-gold); padding: 8px 25px; border-radius: 50px; color: #fff;">
            推荐人ID: <span id="draw-id" style="color: var(--primary-gold); font-weight: bold;"></span>
        </div>

        <div id="qrcode-area" style="
            position: absolute; bottom: 33%; 
            left: 50%; transform: translateX(-50%);
            background: #fff; padding: 12px; border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.8);">
        </div>

        <div style="position: absolute; bottom: 23%; width: 100%; text-align: center; color: #fff;">
            <div style="font-size: 22px; font-weight: bold; letter-spacing: 2px;">扫码注册 · 玩游戏钱全退</div>
        </div>

        <div style="position: absolute; bottom: 40px; color: rgba(255,255,255,0.2); font-size: 11px;">
            — 官方合作伙伴认证证书 —
        </div>
    </div>
</div>

<textarea id="copyHelper" style="position:absolute;left:-9999px;"></textarea>

<script src="https://cdn.staticfile.org/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.staticfile.org/jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script src="https://cdn.staticfile.org/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
    var finalInviteUrl = "";

    function doBind() {
        var gid = $('#game_id').val().trim();

// 校验：必须是 8 位数字
if (!/^\d{8}$/.test(gid)) {
    return alert('请输入 8 位数字 ID');
}

        $.post('<?php echo url("index/index/bind_id"); ?>', {game_id: gid}, function(res) {
            finalInviteUrl = "<?php echo $invite_base; ?>" + "?agentCode=" + gid;
            $('#draw-id').text(gid);
            
            // 物理居中显示
            $('#poster-mask').css('display','flex');
            
            // 1. 生成二维码
            $('#qrcode-area').empty().qrcode({ 
                width: 150, height: 150, text: finalInviteUrl, render: "canvas" 
            });

            // 2. 渲染海报
            setTimeout(function() {
                html2canvas(document.querySelector("#poster-tpl"), {
                    scale: 3, useCORS: true, backgroundColor: "#000"
                }).then(canvas => {
                    var img = canvas.toDataURL("image/png");
                    $('#poster-res').html('<img src="' + img + '">');
                });
            }, 800);
        });
    }

    function copyAction() {
        var helper = document.getElementById("copyHelper");
        helper.value = finalInviteUrl;
        helper.select();
        document.execCommand("copy");
        alert("邀请链接已复制，赶快去拉好友吧！");
    }
</script>
</body>
</html>