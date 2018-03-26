<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\VerificationCodeRequest;
use GuzzleHttp\Exception\ClientException;
use Overtrue\EasySms\EasySms;


class VerificationCodesController extends Controller
{

    /**
     * @param VerificationCodeRequest $request
     * @param EasySms $easySms
     * 生成并发送验证码
     */
    public function store(VerificationCodeRequest $request, EasySms $easySms)
    {

        // 验证图片验证码
        $captchaData = \Cache::get($request->captcha_key);

        if (!$captchaData) {
            return $this->response->error('图片验证码已失效', 422);
        }

        if (!hash_equals($captchaData['code'], $request->captcha_code)) {
            // 验证错误就清除缓存
            \Cache::forget($request->captcha_key);
            return $this->response->errorUnauthorized('验证码错误');
        }


        $phone = $captchaData['phone'];

        // 生成4位随机数, 左侧补0
        $code = str_pad(random_int(1,9999), 4, 0, STR_PAD_LEFT);


        // 判断是否生产环境
        if (!app()->environment('production')) {
            $code = '1234';
        } else {
            try {
                // 发送信息
                $result = $easySms->send($phone, [
                    'content' => "【Lbbs社区】您的验证码是{$code}。如非本人操作，请忽略本短信",
                ]);
            } catch (ClientException $exception) {
                $response = $exception->getResponse();
                $result = json_decode($response->getBody()->getContents(), true);
                return $this->response->errorInternal($result['msg'] ?? '短信发送异常');
            }
        }

        // 保存验证码到缓存
        $key = 'verificationCode_'.str_random(15);
        $expiredAt = now()->addMinutes(10);
        // 缓存验证码 10分钟过期
        \Cache::put($key, ['phone' => $phone, 'code' => $code], $expiredAt);

        return $this->response->array([
            'key' => $key,
            'expired_at' => $expiredAt->toDateTimeString(),
        ])->setStatusCode(201);
    }
}
