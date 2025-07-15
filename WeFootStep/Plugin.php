<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 微信运动步数展示与同步插件，使博客可以显示微信运动步数并每天自动同步更新
 * 
 * @package WeFootStep
 * @author 璇
 * @version 1.0.0
 * @link https://github.com/BXCQ/WeFootStep
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
        // 加载辅助函数
        // require_once 'libs/WxRunHelper.php';

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

        // 加载 Widget 类
        Typecho_Widget::widget('WeFootStep_Widget')->to($weFootStep);

        // 使用 addAction 注册一个标准的动作接口
        Helper::addAction('wefootstep', 'WeFootStep_Action');

        // 添加插件所需的钩子
        Typecho_Plugin::factory('Widget_Archive')->footer = array('WeFootStep_Plugin', 'footer');
        Typecho_Plugin::factory('Widget_Archive')->header = array('WeFootStep_Plugin', 'header');
        Typecho_Plugin::factory('admin/menu.php')->navBar = array('WeFootStep_Plugin', 'navBarWrapper');

        // 创建上传目录
        if (!is_dir(dirname(__FILE__) . '/cache/')) {
            mkdir(dirname(__FILE__) . '/cache/', 0777, true);
        }

        return _t('插件启用成功，请设置微信运动参数');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        // 移除动作接口
        Helper::removeAction('wefootstep');

        // 是否删除数据表
        $db = Typecho_Db::get();
        $config = Helper::options()->plugin('WeFootStep');

        if (isset($config->dropTable) && $config->dropTable) {
            $db->query("DROP TABLE IF EXISTS `" . $db->getPrefix() . "we_foot_step`");
            $dir = dirname(__FILE__) . '/cache/';
            if (is_dir($dir)) {
                self::deleteDir($dir);
            }
        }

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
     * 添加页脚内容
     * 
     * @access public
     * @param string $footer
     * @return string
     */
    public static function footer($footer = '')
    {
        $options = Helper::options()->plugin('WeFootStep');

        if ($options->displayPosition == 'footer') {
            $footer .= self::render();
        }

        return $footer;
    }

    /**
     * 添加头部内容
     * 
     * @access public
     * @param string $header
     * @return string
     */
    public static function header($header = '')
    {
        $options = Helper::options()->plugin('WeFootStep');

        // 此处不再需要同步逻辑，因为所有同步都由小程序端通过 Action 接口发起

        return $header;
    }

    /**
     * 导航栏包装方法，用于适配钩子
     * 
     * @access public
     * @param string $navBar 导航条HTML
     * @return string
     */
    public static function navBarWrapper($navBar)
    {
        if (Helper::options()->request->getPathInfo() == '/admin/' || Helper::options()->request->getPathInfo() == '/admin/index.php') {
            $adminUrl = Helper::options()->adminUrl;
            $url = rtrim($adminUrl, '/') . '/extending.php?panel=WeFootStep/admin/manage.php';
            $navBar .= '<a href="' . $url . '" class="parent"><span class="message right">' . _t('微信步数') . '</span></span></a>';
        }

        return $navBar;
    }

    /**
     * 原导航按钮方法（保留用于向后兼容）
     * 
     * @access public
     * @param array $navBar 导航条
     * @param mixed $request 请求对象（可选）
     * @return string
     */
    public static function navBar($navBar, $request = null)
    {
        return self::navBarWrapper($navBar);
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
