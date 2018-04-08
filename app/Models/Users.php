<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    // 用户表
    protected $table = 'users';

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'introduction', 'avatar',
        'weixin_openid', 'weixin_unionid'
    ];
}
