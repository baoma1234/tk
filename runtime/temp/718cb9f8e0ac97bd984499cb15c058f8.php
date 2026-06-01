<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:72:"D:\phpstudy_pro\WWW\wx\public/../application/index\view\index\index.html";i:1776692144;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>邀3友全额返</title>
    <link rel="stylesheet" href="https://cdn.staticfile.org/twitter-bootstrap/4.6.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.staticfile.org/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: #121212; color: #fff; padding-bottom: 50px; }
        .main-card { background: #1e1e1e; border-radius: 15px; padding: 20px; margin: 15px; border: 1px solid #333; }
        .gold-txt { color: #ffcf4d; }
        .btn-gold { background: linear-gradient(180deg, #ffcf4d, #f1b400); color: #5c3b00; border: none; font-weight: bold; border-radius: 25px; width: 100%; padding: 12px; }

        /* 弹窗遮罩 */
        #poster-mask { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.95); z-index:999; flex-direction:column; align-items:center; overflow-y: auto; padding: 30px 0; }
        .poster-container { width: 85%; max-width: 400px; text-align: center; }
        #poster-res img { border-radius: 10px; width: 100%; box-shadow: 0 0 20px rgba(0,0,0,0.5); }

        /* 弹窗内的复制区 */
        .copy-box { background: #2a2a2a; border-radius: 10px; padding: 15px; margin-top: 20px; border: 1px solid #444; }
        .url-text { font-size: 12px; color: #aaa; word-break: break-all; margin-bottom: 10px; display: block; }

        /* 隐藏的绘图模板 */
        #poster-tpl { position:absolute; left:-9999px; width:375px; height:600px; background:#d43f3a; color:#fff; text-align:center; padding-top:50px; }
    </style>
</head>
<body>

<div class="main-card">
    <h4 class="gold-txt text-center">裂变推广中心</h4>
    <p class="text-center small opacity-50">邀请标识: <?php echo (\think\Request::instance()->session('active_invite_id') ?: '无'); ?></p>

    <div class="mt-4">
        <label class="small text-muted">输入游戏ID激活资格</label>
        <input type="text" id="game_id" class="form-control bg-dark border-secondary text-white mb-3" placeholder="填写您的ID" value="<?php echo $game_id; ?>">
        <button class="btn-gold shadow" onclick="doBind()">激活并生成海报</button>
        <a href="<?php echo $customer_url; ?>" class="btn btn-outline-info btn-block btn-sm mt-3 border-info text-info" style="border-radius:20px;">联系官方客服</a>
    </div>
</div>

<div id="poster-mask">
    <div class="poster-container">
        <div id="poster-res"><p>正在为您渲染海报...</p></div>

        <div class="copy-box">
            <span class="url-text" id="displayUrl"></span>
            <button class="btn btn-warning btn-sm btn-block" onclick="copyAction()">
                <i class="fa fa-copy"></i> 一键复制邀请链接
            </button>
        </div>

        <button class="btn btn-link text-white-50 mt-3" onclick="$('#poster-mask').css('display','none')">关闭预览</button>
    </div>
</div>

<div id="poster-tpl">
    <h2 style="color:#ffcf4d; font-size:35px;">全额返现劵</h2>
    <p style="font-size:18px;">推荐人ID: <span id="tpl-uid"></span></p>
    <div id="qrcode" style="background:#fff; padding:10px; display:inline-block; margin:30px 0; border-radius:10px;"></div>
    <p style="font-size:16px;">扫码注册 · 充值立返现金</p>
</div>

<textarea id="copyHelper" style="position:absolute;left:-9999px;"></textarea>

<script src="https://cdn.staticfile.org/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.staticfile.org/jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script src="https://cdn.staticfile.org/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
    var finalUrl = "";

    function doBind() {
        const gid = $('#game_id').val();
        if(!gid) return alert('请输入游戏ID');

        $.post('<?php echo url("index/promote/bind_id"); ?>', {game_id: gid}, function(res) {
            // 设置分享链接
            finalUrl = "<?php echo $invite_base; ?>" + "?id=" + gid;
            $('#displayUrl').text(finalUrl);
            $('#tpl-uid').text(gid);

            // 显示遮罩并生成
            $('#poster-mask').css('display','flex');
            makePoster(finalUrl);
        });
    }

    function makePoster(link) {
        $('#qrcode').empty().qrcode({ width: 150, height: 150, text: link });

        setTimeout(() => {
            html2canvas(document.querySelector("#poster-tpl"), { scale: 2 }).then(canvas => {
                const img = canvas.toDataURL("image/png");
                $('#poster-res').html('<img src="'+img+'">');
            });
        }, 500);
    }

    function copyAction() {
        const helper = document.getElementById("copyHelper");
        helper.value = finalUrl;
        helper.select();
        helper.setSelectionRange(0, 99999);
        try {
            document.execCommand("copy");
            alert("邀请链接已复制！您可以发给好友了。");
        } catch (err) {
            alert("复制失败，请手动长按海报保存");
        }
    }
</script>
</body>
</html>