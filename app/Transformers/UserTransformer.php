<?php

namespace App\Transformers;

use App\Models\Users;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['roles'];

    public function transform(Users $user)

    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'introduction' => $user->introduction,
            'bound_phone' => $user->phone ? true : false,
            'bound_wechat' => ($user->weixin_unionid || $user->weixin_openid) ? true : false,
            'created_at' => $user->created_at->toDateTimeString() ?? '',
            'updated_at' => $user->updated_at->toDateTimeString() ?? '',
            'last_actived_at' => $user->last_actived_at ?? '',
        ];
    }
}
