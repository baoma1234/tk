<?php

namespace app\admin\model;

use think\Model;

class Douyinlinklog extends Model
{
    protected $table = 'fa_douyin_link_log';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;
}
