<?php


namespace app\admin\controller;


use app\BaseController;
use app\model\SystemUsers;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\facade\View;

class Login extends BaseController
{
    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    public function login()
    {
        if (request()->isPost()) {
            $captcha = input('captcha');
            if (!captcha_check($captcha)) {
                return json(['err' => 1, 'msg' => '验证码错误']);
            }
            $username = trim(input('username'));
            $password = trim(input('password'));
            $map = array();
            $map[] = ['uname', '=', $username];
            $map[] = ['groupid', '=', 2];
            try {
                $admin = SystemUsers::where($map)->findOrFail();
                $jieqi_ver = floatval(config('site.jieqi_ver'));
                if ($jieqi_ver >= 2.4) {
                    $passsalt = md5(md5($password).$admin['salt']);
                } else {
                    $passsalt = md5($password.$admin['salt']);
                }

                if ($passsalt != $admin['pass']) {
                    return json(['err' => 1, 'msg' => '密码错误']);
                } else {
                    $admin->lastlogin = time();
                    $admin->save();
                    session('xwx_admin', $admin->uname);
                    session('xhx_admin_id', $admin->uid);
                    return json(['err' => 0, 'msg' => '登录成功']);
                }
            } catch (ModelNotFoundException $e) {
                return json(['err' => 1, 'msg' => '不存在该管理员']);
            }
        } else {
            return view();
        }
    }

    public function logout()
    {
        session('xwx_admin', null);
        session('xhx_admin_id', null);
        return redirect(url('login/login'));
    }

    public function captcha()
    {
        ob_clean();
        return captcha();
    }
}