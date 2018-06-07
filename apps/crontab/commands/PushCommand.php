<?php

namespace apps\crontab\commands;

use mix\console\ExitCode;
use mix\facades\Input;
use mix\task\CenterProcess;
use mix\task\LeftProcess;
use mix\task\TaskExecutor;

/**
 * 推送模式范例
 * @author 刘健 <coder.liu@qq.com>
 */
class PushCommand extends BaseCommand
{

    // 初始化事件
    public function onInitialize()
    {
        parent::onInitialize(); // TODO: Change the autogenerated stub
        // 获取程序名称
        $this->programName = Input::getCommandName();
    }

    /**
     * 获取服务
     * @return TaskExecutor
     */
    public function getTaskService()
    {
        return create_object(
            [
                // 类路径
                'class'         => 'mix\task\TaskExecutor',
                // 服务名称
                'name'          => "mix-crontab: {$this->programName}",
                // 执行模式
                'mode'          => \mix\task\TaskExecutor::MODE_PUSH,
                // 左进程数
                'leftProcess'   => 1, // 一次性执行，只能为1
                // 中进程数
                'centerProcess' => 5,
                // POP退出等待时间 (秒)
                'popExitWait'   => 3,
            ]
        );
    }

    // 执行任务
    public function actionExec()
    {
        // 预处理
        parent::actionExec();
        // 启动服务
        $service = $this->getTaskService();
        $service->on('LeftStart', [$this, 'onLeftStart']);
        $service->on('CenterStart', [$this, 'onCenterStart']);
        $service->start();
        // 返回退出码
        return ExitCode::OK;
    }

    // 左进程启动事件回调函数
    public function onLeftStart(LeftProcess $worker)
    {
        // 模型内使用长连接版本的数据库组件，这样组件会自动帮你维护连接不断线
        $tableModel = new \apps\common\models\TableModel();
        // 取出数据一行一行推送给中进程
        foreach ($tableModel->getAll() as $item) {
            // 将消息推送给中进程去处理，push有长度限制 (https://wiki.swoole.com/wiki/page/290.html)
            $worker->push($item);
        }
        // 结束任务
        $worker->finish();
    }

    // 中进程启动事件回调函数
    public function onCenterStart(CenterProcess $worker)
    {
        // 保持任务执行状态，循环结束后当前进程会退出，主进程会重启一个新进程继续执行任务，这样做是为了避免长时间执行内存溢出
        for ($j = 0; $j < 16000; $j++) {
            // 从进程消息队列中抢占一条消息
            $data = $worker->pop();
            if (empty($data)) {
                continue;
            }
            // 处理消息
            try {
                // 处理消息，比如：发送短信、发送邮件、微信推送
                // ...
            } catch (\Exception $e) {
                // 回退数据到消息队列
                $worker->fallback($data);
                // 休息一会，避免 CPU 出现 100%
                sleep(1);
                // 抛出错误
                throw $e;
            }
        }
    }

}
