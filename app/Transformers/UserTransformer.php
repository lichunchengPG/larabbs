<?php

namespace App\Transformers;


use App\Models\Users;
use League\Fractal\TransformerAbstract;


/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/10
 * Time: 15:43
 */
class UserTransformer extends TransformerAbstract
{

    public function transform(Users $user )
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'introduction' => $user->introduction,
            'bound_phone' => $user->phone ? true : false,
            'bound_wechat' => ($user->weixin_unionid || $user->weixin_openid) ? true : false,
            'last_actived_at' => isset($user->last_actived_at) ? $user->last_actived_at->toDateTimeString() : null,
            'created_at' => isset($user->created_at) ? $user->created_at->toDateTimeString() : null,
            'updated_at' => isset($user->updated_at) ? $user->updated_at->toDateTimeString() : null,
        ];
    }


}