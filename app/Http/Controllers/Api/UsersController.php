<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserRequest;
use App\Models\Users;


class UsersController extends Controller
{

    /**
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response|void
     * 用户注册 （验证码）
     */
    public function store(UserRequest $request)
    {
        $data = $request->only('verification_key', 'name', 'password', 'verification_code');
        $verifyData = \Cache::get($request->get('verification_key'));

        if(!$verifyData){
            // 422 验证失效
            return $this->response->error('验证已失效', 422);
        }

        // 防止时序攻击
        if(!hash_equals($verifyData['code'], $data['verification_code'])){
            // 401
            return $this->response->errorUnauthorized('验证码错误');
        }

        $user = Users::create([
            'name' => $data['name'],
            'phone' => $verifyData['phone'],
            'password' => bcrypt($data['password']),
        ]);

        // 清除缓存验证码
        \Cache::forget($data['verification_key']);

        return $this->response->created();
    }
}
