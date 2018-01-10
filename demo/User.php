<?php

namespace demo;

/**
 * @name 会员接口
 * @module 会员API
 * @desc 用于XX系统的会员API
 * @author zhaozhuobin
 * @date 2017-12-21
 */
class User
{

    /**
     * @name 会员登录
     * @desc 用于会员登录
     * @route /user/login
     * @method POST
     * @param string $username 用户名
     * @param string $password 密码
     * @return array [
     *  'code'=>0,
     *  'msg'=>'success'
     * ]
     */
    public function login($username,$password) : array
    {
        //Todo: 会员登录逻辑
        return ['code'=>0,'msg'=>'注册成功'];
    }

    /**
     * @name 会员注册
     * @desc 用于会员注册
     * @route /user/register
     * @method POST
     * @param int $uid 用户名
     * @return array [
     *  'code'=>0,
     *  'msg'=>'success',
     *  'data'=>[
     *     'username'=>'hello',
     *     'gender'=>0,
     *     'create_time'=>1515552719,
     *  ]
     * ]
     */
    public function getUserById($uid) : array
    {
        //Todo: 会员注册逻辑
        return ['code'=>0,'msg'=>'注册成功'];
    }
}