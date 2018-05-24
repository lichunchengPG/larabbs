<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AuthorizationRequest;
use App\Http\Requests\Api\SocialAuthorizationRequest;
use App\Http\Requests\Api\WeappAuthorizationRequest;
use App\Models\Users;


class AuthorizationsController extends Controller
{
    /**
     * @param $type
     * @param SocialAuthorizationRequest $request
     * 第三方登录
     */
    public function socialStore($type, SocialAuthorizationRequest $request)
    {
        if (!in_array($type, ['weixin'])) {
            return $this->response->errorBadRequest();
        }

        $driver = \Socialite::driver($type);

        try {
            if ($code = $request->code) {
                $response = $driver->getAccessTokenResponse($code);
                $token = array_get($response, 'access_token');
            } else {
                $token = $request->access_token;

                if ($type == 'weixin') {
                    $driver->setOpenId($request->openid);
                }
            }

            $oauthUser = $driver->userFromToken($token);
        } catch (\Exception $e) {
            return $this->response->errorUnauthorized('参数错误，未获取用户信息');
        }

        switch ($type) {
            case 'weixin':
                $unionid = $oauthUser->offsetExists('unionid') ? $oauthUser->offsetGet('unionid') : null;

                if ($unionid) {
                    $user = Users::where('weixin_unionid', $unionid)->first();
                } else {
                    $user = Users::where('weixin_openid', $oauthUser->getId())->first();
                }

                // 没有用户，默认创建一个用户
                if (!$user) {
                    $user = Users::create([
                        'name' => $oauthUser->getNickname(),
                        'avatar' => $oauthUser->getAvatar(),
                        'weixin_openid' => $oauthUser->getId(),
                        'weixin_unionid' => $unionid,
                    ]);
                }

                break;
        }

        $token = \Auth::guard('api')->fromUser($user);
        return $this->respondWithToken($token)->setStatusCode(201);
    }


    /**
     * @param AuthorizationRequest $request
     * 登录
     */
    public function store(AuthorizationRequest $request)
    {
        $username = $request->username;

        filter_var($username, FILTER_VALIDATE_EMAIL) ?
            $credentials['email'] = $username :
            $credentials['phone'] = $username;

        $credentials['password'] = $request->password;

        if (!$token = \Auth::guard('api')->attempt($credentials)) {
            return $this->response->errorUnauthorized('用户名或密码错误');
        }

        return $this->respondWithToken($token)->setStatusCode(201);
    }


    /**
     * @param WeappAuthorizationRequest $request
     * 小程序登陆
     */
    public function weappStore(WeappAuthorizationRequest $request)
    {
        $code = $request->code;

        // 根据 code 获取微信 openid 和 session_key
        $miniProgram = \EasyWeChat::miniProgram();
        $data = $miniProgram->auth->session($code);

        // 如果结果错误，说明 code 已过期或不正确，返回 401 错误
        if (isset($data['errcode'])) {
            return $this->response->errorUnauthorized('code 不正确');
        }

        // 找到 openid 对应的用户
        $user = Users::where('weapp_openid', $data['openid'])->first();

        $attributes['weixin_session_key'] = $data['session_key'];

        // 未找到对应用户则需要提交用户名密码进行用户绑定
        if (!$user) {
            // 如果未提交用户名密码，403 错误提示
            if (!$request->username) {
                return $this->response->errorForbidden('用户不存在');
            }

            $username = $request->username;

            // 用户名可以是邮箱或电话
            filter_var($username, FILTER_VALIDATE_EMAIL) ?
                $credentials['email'] = $username :
                $credentials['phone'] = $username;

            $credentials['password'] = $request->password;

            // 验证用户名和密码是否正确
            if (!\Auth::guard('api')->once($credentials)) {
                return $this->response->errorUnauthorized('用户名或密码错误');
            }

            // 获取对应的用户
            $user = \Auth::guard('api')->getUser();
            $attributes['weapp_openid'] = $data['openid'];
        }

        // 更新用户数据
        $user->update($attributes);

        // 为对应用户创建 JWT
        $token = \Auth::guard('api')->fromUser($user);

        return $this->respondWithToken($token)->setStatusCode(201);
    }


    // 返回信息
    protected function respondWithToken($token)
    {
        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => \Auth::guard('api')->factory()->getTTL() * 60
        ]);
    }


    // 更新token
    public function update()
    {
        $token = \Auth::guard('api')->refresh();
        return $this->respondWithToken($token);
    }

    // 删除token
    public function destroy()
    {
        \Auth::guard('api')->logout();
        return $this->response->noContent();
    }


    public function test1()
    {
        $tmp = '8383 8883 8883 8338 8333 3388 8383 3838 8338 8383 3388 8383 3838 8338 3838 3883 8338 3838 8338 3838 8338 8383 8383 3838 3838 3883 8383 8383 3883 8338 3838 8383 3838 8338 8338 3888 8383 8833 3838 3838 8383 8383 3388 3883 8883 8333 3388 3883 8338 3883 3838 3388 3388 3883 8338 8883 3883 8383 8333 3888 8833 8883 3883 8338 3838 8383 8338 8338 3838 8833 8338 3838 8338 8383 8383 3838 3838 3838 8383 8883 8338 8333 3388 8383 3838 8338 8383 3388 8383 3838 3838 3838 3883 8338 3838 8338 3838 8338 8383 8383 3838 3838 8338 8383 8383 3883 8338 3838 8383 8338 3888 8383 8833 3838 8338 8383 8383 3388 3883 8883 8333 3388 3883 8338 3883 8338 3388 3388 3883 8338 8338 3883 8383 8333 8383 8338 8883 8338 8338 3838 3883 8338 3838 8338 3838 8338 8383 8383 3838 3838 8338 8383 8383 3883 8883 3838 8383 8883 3888 8383 8833 3838 8338 8383 8383 3388 3883 3883 8333 3388 3883 8338 3883 8338 3388 3388 3883 8338 8883 3883 8383 8333 8383 8338 8883 8338 8383 8338 8883 8883 8333 3388 8383 3838 8338 8383 3388 8383 3838 8338 3838 3883 8338 3838 8338 3838 8338 8383 8383 3838 3838 8338 8383 8383 3883 8338 3838 8383 3838 8883 8338 3888 8383 8833 3838 8338 8383 8383 3388 3883 8338 8333 3388 3883 8338 3883 8338 3388 3388 3883 8338 8883 3883 8383 8333 3888 8833 8338 3883 8338 8338 8383 8338 8338 8338 8833 8883 3838 8883 8383 8383 3838 3838 8338 8383 8883 8883 8333 3388 8383 3838 8883 8383 3388 8383 3838 8338 3838 3883 8338 3838 8338 3838 8338 8383 8383 3838 3838 8338 8383 8383 3883 8883 3838 8383 8338 3888 8383 8833 3838 8338 8383 8383 3388 3883 8338 8333 3388 3883 8338 3883 8338 3388 3388 3883 8883 8338 3883 8383 8333 8383 8338 8883 8338 8338 3838 3883 8338 3838 8338 3838 8883 8383 8383 3838 3838 8338 8383 8383 3883 3838 3838 8383 8338 3888 8383 8833 3838 3838 8383 8383 3388 3883 8338 8333 3388 3883 3838 3883 8883 3388 3388 3883 8338 3838 3883 8383 8333 8383 8338 8883 8338 3883 8338 8338';
        $array_tmp = explode(' ', $tmp);
        $count = 0;
        foreach ($array_tmp as $index => $value){
            if($value == '8338'){
                $count = $count +1;
            }
        }
        dd($count);
    }

    public function test()
    {
        $tmp = ['654865483698247389647467647927346824583921256983545892103693473895748690102856783328181746156481201299655356629874136928786428','36528360774899212212036542119875632155632298648395163837846752566337744885599125789246325897441263027459833546846321859732154', '324987653179967583237693793271937896172328792737913786542713897146327'];

        $tmp1 = '654865483698247389647467647927346824583921256983545892103693473895748690102856783328181746156481201299655356629874136928786428';
        $tmp2 = '36528360774899212212036542119875632155632298648395163837846752566337744885599125789246325897441263027459833546846321859732154';
        $tmp3 = '324987653179967583237693793271937896172328792737913786542713897146327';

        $result = [];
        //foreach ($tmp as $t) {
            $count = strlen($tmp2);
            $num = 0;
            for ($i = 0; $i < $count - 1; $i++) {
                $a = $tmp2{$i};
                $b = $tmp2{$i + 1};
                $sum = $a + $b;
                if ($sum == 11) {
                    $num = $num + 1;
                }
            }
            dd($num);
           // $result[] = $num;
        //}
        dd($result);
    }

}
