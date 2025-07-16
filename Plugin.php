<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 微信运动步数展示与同步插件，使博客可以显示微信运动步数并每天自动同步更新
 * 
 * @package WeFootStep
 * @author 璇
 * @version 1.2.0
 * @link https://blog.ybyq.wang/
 * @Github https://github.com/BXCQ/WeFootStep
 */
class WeFootStep_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return string
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        // 创建数据表
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();

        // 创建步数表
        $adapter = $db->getAdapterName();
        if (strpos($adapter, 'Mysql') !== false) {
            $db->query("CREATE TABLE IF NOT EXISTS `{$prefix}we_foot_step` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `date` DATE NOT NULL,
                `step_count` INT(11) NOT NULL DEFAULT 0,
                `created` INT(10) UNSIGNED NOT NULL,
                `modified` INT(10) UNSIGNED NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `date` (`date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        } else {
            $db->query("CREATE TABLE IF NOT EXISTS `{$prefix}we_foot_step` (
                `id` INTEGER PRIMARY KEY,
                `date` DATE NOT NULL,
                `step_count` INT NOT NULL DEFAULT 0,
                `created` INT NOT NULL,
                `modified` INT NOT NULL,
                UNIQUE(`date`)
            )");
        }

        // 注册Action
        Helper::addAction('WeFootStep', 'WeFootStep_Action');

        // 注册路由
        Helper::addRoute('wefootstep_sync_route', '/wefootstep/sync', 'WeFootStep_Action', 'sync');
        Helper::addRoute('wefootstep_data_route', '/wefootstep/data', 'WeFootStep_Action', 'getStepData');

        // 添加后台管理菜单 (已移除)
        // Helper::addPanel(3, 'panel.php', '微信步数', '管理微信运动步数', 'administrator');

        return _t('插件已启用，请进行相关设置');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     */
    public static function deactivate()
    {
        // 删除路由
        Helper::removeRoute('wefootstep_sync_route');
        Helper::removeRoute('wefootstep_data_route');

        // 删除后台管理菜单和Action (已移除)
        // Helper::removePanel(3, 'panel.php');
        Helper::removeAction('WeFootStep');
        
        return _t('插件已禁用');
    }

    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        // 显示样式
        $displayStyle = new Typecho_Widget_Helper_Form_Element_Radio(
            'displayStyle',
            array(
                'bar' => _t('进度条'),
                'text' => _t('文本'),
                'card' => _t('卡片'),
            ),
            'bar',
            _t('显示样式'),
            _t('选择微信步数的显示样式')
        );
        $form->addInput($displayStyle);

        // 显示位置
        $displayPosition = new Typecho_Widget_Helper_Form_Element_Radio(
            'displayPosition',
            array(
                'sidebar' => _t('侧边栏'),
                'footer' => _t('页脚'),
                'custom' => _t('自定义位置（使用短代码）'),
            ),
            'sidebar',
            _t('显示位置'),
            _t('选择微信步数的显示位置，选择自定义位置需要在模板中添加短代码 &lt;!-- WeFootStep --&gt;')
        );
        $form->addInput($displayPosition);

        // 小程序AppID
        $appid = new Typecho_Widget_Helper_Form_Element_Text(
            'appid',
            null,
            null,
            _t('微信小程序AppID'),
            _t('用于获取微信运动数据的小程序AppID')
        );
        $form->addInput($appid);

        // 小程序AppSecret
        $secret = new Typecho_Widget_Helper_Form_Element_Text(
            'secret',
            null,
            null,
            _t('微信小程序AppSecret'),
            _t('用于获取微信运动数据的小程序AppSecret')
        );
        $form->addInput($secret);

        // 卸载是否删除数据
        $dropTable = new Typecho_Widget_Helper_Form_Element_Radio(
            'dropTable',
            array(
                '0' => _t('否'),
                '1' => _t('是'),
            ),
            '0',
            _t('卸载时是否删除数据'),
            _t('选择是否在卸载插件时删除数据表')
        );
        $form->addInput($dropTable);

        // 添加测试区域，显示最新接收到的数据
        echo '<div class="typecho-option" id="wefootstep-debug">';
        echo '<label class="typecho-label">调试信息</label>';
        echo '<div class="typecho-option-content">';

        // 显示最新步数数据
        $db = Typecho_Db::get();
        $latestStep = $db->fetchRow(
            $db->select()
                ->from('table.we_foot_step')
                ->order('date', Typecho_Db::SORT_DESC)
                ->limit(1)
        );

        if ($latestStep) {
            echo '<p><strong>最新步数记录：</strong> ' . $latestStep['step_count'] . ' 步 (' . $latestStep['date'] . ')</p>';
        } else {
            echo '<p><strong>最新步数记录：</strong> 暂无数据</p>';
        }

        // 显示请求日志
        $logFile = dirname(__FILE__) . '/request_log.txt';
        if (file_exists($logFile)) {
            echo '<p><strong>请求日志：</strong></p>';
            echo '<pre style="max-height:300px;overflow:auto;background:#f6f6f6;padding:10px;border:1px solid #ddd;font-size:12px;">';
            echo htmlspecialchars(file_get_contents($logFile));
            echo '</pre>';
        } else {
            echo '<p><strong>请求日志：</strong> 暂无日志</p>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // 个人用户不需要配置
    }

    /**
     * 插件实现方法
     * 
     * @access public
     * @return string
     */
    public static function render()
    {
        // 直接使用Widget类进行渲染
        $widget = Typecho_Widget::widget('WeFootStep_Widget');
        ob_start();
        $widget->render();
        $html = ob_get_clean();
        return $html;
    }

    /**
     * 临时导航栏包装方法，用于安全地过渡
     * 
     * @access public
     * @param string $navBar
     * @return string
     */
    public static function navBarWrapper($navBar)
    {
        // 此方法为空，以防止因旧钩子注册而导致的致命错误。
        // 在停用并重新激活插件后，此方法可以被安全地移除。
        return $navBar;
    }
    
    /**
     * 在页面底部添加内容
     * 
     * @access public
     * @param string $footer
     * @return string
     */
    public static function footer($footer = '')
    {
        // All JavaScript logic is now being moved back into headnav.php
        // to ensure execution context and timing are correct.
        // Therefore, we no longer need to load any scripts here.
        
        // This part handles the original plugin functionality of rendering a widget
        // in the footer based on plugin settings.
        $plugin_options = Helper::options()->plugin('WeFootStep');
        
        if (isset($plugin_options->displayPosition) && $plugin_options->displayPosition == 'footer') {
            $html = self::render();
            return $footer . $html;
        }
        
        return $footer;
    }
    
    /**
     * 在页面头部添加内容
     * 
     * @access public
     * @param string $header
     * @return string
     */
    public static function header($header = '')
    {
        // 不再需要在 header 中加载 echarts，统一移动到 footer
        return $header;
    }
    
    /**
     * 删除目录及文件
     * 
     * @access private
     * @param string $dir
     * @return void
     */
    private static function deleteDir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $path = $dir . $file;
                if (is_dir($path)) {
                    self::deleteDir($path . '/');
                } else {
                    @unlink($path);
                }
            }
        }
        @rmdir($dir);
    }
}
