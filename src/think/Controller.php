<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think;

use think\exception\ValidateException;
use think\traits\Jump;

class Controller
{
    use Jump;

    /**
     * 视图类实例
     * @var \think\View
     */
    protected $view;

    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 验证失败是否抛出异常
     * @var bool
     */
    protected $failException = false;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app['request'];
        $this->view    = $this->app['view'];

        // 控制器初始化
        $this->initialize();
        $this->registerMiddleware();
    }

    // 注册控制器中间件
    private function registerMiddleware()
    {
        foreach ($this->middleware as $key => $val) {
            if (!is_int($key)) {
                if (isset($val['only']) && !in_array($this->request->action(), $val['only'])) {
                    continue;
                } elseif (isset($val['except']) && in_array($this->request->action(), $val['except'])) {
                    continue;
                } else {
                    $val = $key;
                }
            }

            $this->app['middleware']->controller($val);
        }
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 加载模板输出
     * @access protected
     * @param  string $template 模板文件名
     * @return mixed
     */
    protected function fetch(string $template = '')
    {
        return $this->view->fetch($template);
    }

    /**
     * 渲染内容输出
     * @access protected
     * @param  string $content 模板内容
     * @return mixed
     */
    protected function display(string $content = '')
    {
        return $this->view->display($content);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param  array $vars 模板变量
     * @return $this
     */
    protected function assign(array $vars)
    {
        $this->view->assign($vars);

        return $this;
    }

    /**
     * 视图过滤
     * @access protected
     * @param  Callable $filter 过滤方法或闭包
     * @return $this
     */
    protected function filter(callable $filter)
    {
        $this->view->filter($filter);

        return $this;
    }

    /**
     * 初始化模板引擎
     * @access protected
     * @param  array|string $engine 引擎参数
     * @return $this
     */
    protected function engine($engine)
    {
        $this->view->engine($engine);

        return $this;
    }

    /**
     * 设置验证失败后是否抛出异常
     * @access protected
     * @param  bool $fail 是否抛出异常
     * @return $this
     */
    protected function validateFailException(bool $fail = true)
    {
        $this->failException = $fail;

        return $this;
    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate($validate, $message);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                list($validate, $scene) = explode('.', $validate);
            }
            $class = $this->app->parseClass('validate', $validate);
            $v     = $class::make([], $message);
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        if (!$v->check($data)) {
            if ($this->failException) {
                throw new ValidateException($v->getError());
            }
            return $v->getError();
        }

        return true;
    }
}