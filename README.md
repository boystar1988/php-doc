# 自动生成文档脚本

#### 作者：[zhaozhuobin](#)
#### 日期：[2017-12-22](#)

------------------------------------------------------------------------------------

## 一.使用方式：

### [ 命令行方式 ]
#### 1.说明：通过console组件执行
#### 2.调用方法
##### （1）进入console所在目录：
`cd bin/`
##### （2）执行命令
```
 php console app:create-doc <class> [template]
```
>说明: `class` 指定类(如:demo\User),`template`是文档主题默认值是default，用于切换自定义模板。例子：`php console app:create-doc "demo\User"`

## 二.注释写法：

### [ 类的注释写法]
#### 1.说明：由于组件是通过获取注释内容解析的，所以注释的格式有要求
#### 2.参数说明
##### （1）name：标题，将作为文件名和一级标题生成
##### （2）module：功能模块(用于创建自定义输出文件夹)
##### （3）desc：功能描述
##### （4）author：作者
##### （5）date：日期
```
/**
 * @name 会员接口
 * @module 会员API
 * @desc 用于XX系统的会员API
 * @author zhaozhuobin
 * @date 2017-12-21
 */
class User
{
}
```

### [方法的注释写法]
#### 1.说明：由于组件是通过获取注释内容解析的，所以注释的格式有要求
#### 2.参数说明
##### （1）name：方法标题
##### （2）desc：方法描述
##### （3）route：请求路由
##### （4）method：请求方法
##### （5）param：参数（可填写多个param）
##### （6）return：返回值
```
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
```