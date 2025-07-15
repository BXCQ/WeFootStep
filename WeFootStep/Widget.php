<?php

/**
 * 微信运动步数Widget类
 * 用于在侧边栏显示微信运动步数
 * 
 * @package WeFootStep
 * @author 璇
 * @link https://blog.ybyq.wang
 */
class WeFootStep_Widget extends Widget_Abstract implements Widget_Interface_Do
{
    /**
     * 步数数据
     */
    private $_step;

    /**
     * 获取最新的步数数据
     */
    public function execute()
    {
        $db = Typecho_Db::get();
        $this->_step = $db->fetchRow(
            $db->select()
                ->from('table.we_foot_step')
                ->order('date', Typecho_Db::SORT_DESC)
                ->limit(1)
        );
    }

    /**
     * 输出微信运动步数
     */
    public function stepCount()
    {
        if (empty($this->_step)) {
            return 0;
        }
        return number_format($this->_step['step_count']);
    }

    /**
     * 输出微信运动步数日期
     */
    public function stepDate()
    {
        if (empty($this->_step)) {
            return date('Y-m-d');
        }
        return date('Y年m月d日', strtotime($this->_step['date']));
    }

    /**
     * 输出步数进度百分比
     */
    public function stepPercentage()
    {
        if (empty($this->_step)) {
            return 0;
        }
        return min(round($this->_step['step_count'] / 10000 * 100), 100);
    }

    /**
     * 重载方法，输出步数显示HTML
     */
    public function render()
    {
        // 首先执行查询，获取数据
        $this->execute();

        $options = Helper::options()->plugin('WeFootStep');
        // 如果插件设置不存在，则给一个默认值
        $displayStyle = isset($options->displayStyle) ? $options->displayStyle : 'card';

        if (empty($this->_step)) {
            echo '<div>暂无步数数据</div>';
            return;
        }

        // 容器和标题已由 sidebar.php 提供，此处不再需要
        // echo '<div class="we-foot-step">';

        switch ($displayStyle) {
            case 'bar':
                $percentage = $this->stepPercentage();
                $target_steps = 10000; // 目标步数
                echo '<div class="wefootstep-bar" style="margin-top: 5px;">';
                echo '<div class="wefootstep-bar-info" style="display: flex; justify-content: space-between; font-size: 12px; color: #999; margin-bottom: 5px;"><span>今日(' . $this->_step['date'] . '): ' . $this->stepCount() . '</span><span>' . $percentage . '%</span></div>';
                echo '<div class="wefootstep-progress-track" title="目标: ' . $target_steps . ' 步" style="width: 100%; background-color: #f1f1f1; border-radius: 5px; overflow: hidden;">';
                echo '<div class="wefootstep-progress-fill" style="height: 10px; background-color: #4CAF50; border-radius: 5px; transition: width 1s; width: ' . $percentage . '%;"></div>';
                echo '</div>';
                echo '</div>';
                break;

            case 'text':
            default:
                echo '<div class="wefootstep-text" style="font-size: 14px; color: #555; margin-top: 5px;">';
                echo '🏃 ' . $this->_step['date'] . ' <br/>今日步数：<strong>' . $this->stepCount() . '</strong> 步';
                echo '</div>';
                break;

            case 'card':
                echo '<div class="wefootstep-card" style="text-align:center;  border-radius: 5px; padding: 10px; margin-top: 5px;">';
                echo '<div class="wefootstep-card-steps" style="font-size: 24px; font-weight: bold; color: #4CAF50;">' . $this->stepCount() . '</div>';
                echo '<div class="wefootstep-card-date" style="font-size: 12px; color: #999;">' . $this->_step['date'] . '</div>';
                echo '</div>';
                break;
        }

        // 不再需要容器的闭合标签和内联style
        // echo '</div>';
        // echo '<style>...</style>';
    }

    /**
     * 执行操作（Widget_Interface_Do接口要求实现的方法）
     */
    public function action()
    {
        // 不需要任何操作
    }
}
