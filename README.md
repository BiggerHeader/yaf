# framework_yaf

## 项目安装说明

1):安装yaf拓展，并配置php.ini extension=yaf.so yaf.environ=.localhost  【作用：环境区分配置文件】

2):克隆项目即可

## 框架整体目录结构说明

/application 目录为项目的业务逻辑代码

  /application/controllers yaf框架默认控制器层，这里可以做一些系统捕获异常输出、首页分配、性能测试等

  /application/models  项目model层，按数据库来区分目录，目录下文件为这个数据库下的table

  /application/modules 项目模块 模块下为对应的控制器层、业务逻辑层、视图层

  /application/views   yaf框架默认的views层

  /application/Bootstrap.php 最关键的文件，框架初始化文件
  
  /config  项目配置文件
  
../Frame/library 外部类存放目录

## 框架使用说明

#### 对于路由支持

http://www.yaf.com/mobile/user/login 
mobile为项目模块，user为对应的控制器，login为控制器下的方法

#### 对于模块间调用说明

TZ_Loader::service('Ad', 'Ad'); 第二个参数为模块，第一个为模块下的services下对应的文件
TZ_Loader::service('Ad'); 缺省第二个参数，默认是当前模块下services下对应的文件

#### 对于service调用models说明

TZ_Loader::model('User', 'EshopUser')->getUserList()  第一个参数为文件名（表名），第二个为model下对应的目录名（库名）

#### 对于配置说明

Yaf_Registry::get('config')  载入application配置文件对象，以对象形式依次调用
[.locahost : common]  其中.locahost为yaf.environ配置 common表示每个环境一样的系统配置

#### 对于异常类说明

TZ_Response::error(90001, '新增白名单失败.');
其中返回的code【第一个参数】必须放在config的error.ini 文件做项目整体的异常说明

#### 对于公告函数说明

项目所有的公共函数都放在common/funciton.php 文件内

#### 对于运行日志记录说明

TZ_Log::writeRunLog('测试运行日志', $param);
第一个参数为日志内容，第二个参数可以自定义日志参数和内容，数组形式


#### 对于PDO说明

此框架引入的是Medoo非常轻量级的一个PDO框架，具体使用方法可以参考文档

http://medoo.lvtao.net/
# yaf
