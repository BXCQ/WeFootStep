
## 1. 项目概述

WeFootStep是一个Typecho插件，它能够将用户的微信运动步数同步到Typecho博客并在前端展示。本项目由两部分组成：Typecho插件和微信小程序。通过这两部分的协同工作，实现了微信运动数据的采集、传输、存储和展示的完整流程。

### 1.1 功能特点

- **数据采集**：通过微信小程序获取用户的微信运动步数数据
- **安全传输**：使用微信官方加密机制确保数据传输安全
- **灵活展示**：支持多种展示样式（进度条、文本、卡片）
- **位置自定义**：可选择在侧边栏、页脚或自定义位置显示
- **自动同步**：小程序支持自动同步最新步数数据

### 1.2 技术架构

```
+------------------+      +------------------+      +------------------+
|                  |      |                  |      |                  |
|  微信运动服务器   +----->+   微信小程序      +----->+  Typecho博客     |
|                  |      |                  |      |                  |
+------------------+      +------------------+      +------------------+
        |                                                    |
        |                                                    |
        v                                                    v
+------------------+                              +------------------+
|                  |                              |                  |
|  加密步数数据     |                              |   解密并存储数据   |
|                  |                              |                  |
+------------------+                              +------------------+
```

## 2. 工作原理详解

### 2.1 数据流程

1. **数据获取**：微信小程序通过`wx.getWeRunData()`API获取加密的微信运动数据
2. **身份验证**：小程序通过`wx.login()`获取临时登录凭证code
3. **数据传输**：小程序将code和加密数据发送到Typecho博客
4. **服务端处理**：
   - 博客后端使用code从微信服务器获取session_key
   - 使用session_key解密微信运动数据
   - 将解密后的步数存入数据库
5. **数据展示**：博客前端根据设置的样式展示最新的步数数据

### 2.2 安全机制

- **数据加密**：微信运动数据使用AES-128-CBC算法加密
- **身份验证**：使用临时登录凭证确保请求来自合法用户
- **数据水印**：解密后的数据包含appid水印，防止数据伪造

### 2.3 数据库设计

WeFootStep使用一个简单的数据表来存储步数信息：

```sql
CREATE TABLE `typecho_we_foot_step` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date` DATE NOT NULL,
  `step_count` INT(11) NOT NULL DEFAULT 0,
  `created` INT(10) UNSIGNED NOT NULL,
  `modified` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`date`)
)
```

## 3. 安装与配置

### 3.1 插件安装

1. 下载WeFootStep文件夹
2. 将`WeFootStep`上传到Typecho的`/usr/plugins/`目录
3. 将`miniprogram`分离出并在微信开发者平台中打开
4. 在Typecho后台启用插件并填写内容
5. 编译启动`miniprogram`

### 3.2 插件配置

在Typecho后台，进入"控制台" > "插件" > "WeFootStep" > "设置"，配置以下选项：

- **显示样式**：选择步数显示的样式（进度条/文本/卡片）
- **显示位置**：选择步数显示的位置（侧边栏/页脚/自定义位置）
- **微信小程序AppID**：填写您的小程序AppID
- **微信小程序AppSecret**：填写您的小程序AppSecret
- **卸载时是否删除数据**：选择插件卸载时是否保留数据

### 3.3 微信小程序配置

1. 在[微信公众平台](https://mp.weixin.qq.com/)创建一个小程序
2. 在"开发" > "开发管理" > "开发设置"中获取AppID和AppSecret
3. 在"开发" > "开发管理" > "接口设置"中开启"微信运动步数"接口权限
4. 下载WeFootStep小程序源码
5. 使用微信开发者工具打开小程序项目
6. 修改`pages/index/index.js`中的URL为您的博客地址：

```javascript
url: 'https://您的博客地址/index.php/action/wefootstep?do=sync',
```

7. 在`app.json`中确保已添加获取微信运动数据的权限：

```json
{
  "requiredPrivateInfos": ["getRunData"]
}
```

## 4. 使用方法

### 4.1 博客端

安装并配置好插件后，步数数据会根据您的设置自动显示在指定位置。如果选择了"自定义位置"，需要在模板中添加以下代码：

```php
<?php WeFootStep_Plugin::render(); ?>
```

或者使用HTML注释作为标记：

```html
<!-- WeFootStep -->
```

#### 4.1.1 在主题中添加侧边栏显示

如果您想在主题的侧边栏中添加微信步数显示，可以在主题的sidebar.php或相应的侧边栏模板文件中添加以下代码：

```php
<!--微信步数-->
<section id="wefootstep_widget" class="widget widget_categories wrapper-md padder-v-none clear">
    <h5 class="widget-title m-t-none"><?php _me("微信步数") ?></h5>
    <div class="panel wrapper-sm padder-v-ssm">
        <?php if (class_exists('WeFootStep_Plugin') && method_exists('WeFootStep_Plugin', 'render')) echo WeFootStep_Plugin::render(); ?>
    </div>
</section>
```

这段代码会：
1. 创建一个标题为"微信步数"的侧边栏小部件
2. 检查WeFootStep插件是否已安装并启用
3. 调用插件的render方法显示步数数据
4. 使用适合大多数主题的CSS类进行样式设置

注意：`_me()`函数是某些主题使用的国际化函数，如果您的主题不支持，可以直接使用：
```php
<h5 class="widget-title m-t-none">微信步数</h5>
```

### 4.2 小程序端

1. 使用微信开发者工具上传小程序代码
2. 在"版本管理"中创建体验版
3. 添加自己为体验者
4. 在手机上打开体验版小程序
5. 点击"同步步数"按钮，授权获取微信运动数据
6. 同步成功后，步数数据会显示在小程序界面和博客上

### 4.3 定期同步

小程序支持两种同步方式：

1. **手动同步**：打开小程序，点击"同步步数"按钮
2. **自动同步**：每次打开小程序时会自动尝试同步一次

## 5. 自定义与扩展

### 5.1 样式自定义

您可以通过修改CSS来自定义步数显示的样式。插件提供了三种基本样式，您可以在插件目录的Widget.php文件中找到并修改。

### 5.2 功能扩展

可以扩展的功能方向：

1. **历史数据展示**：添加步数历史记录图表
2. **目标设定**：设置每日步数目标
3. **社交分享**：添加分享功能
4. **多用户支持**：支持多个用户的步数数据

### 5.3 API接口

WeFootStep提供了以下API接口：

- `/index.php/action/wefootstep?do=sync`：同步步数数据

## 6. 常见问题与解决方案

| 问题 | 可能原因 | 解决方案 |
|------|---------|---------|
| **同步失败** | • 小程序AppID或AppSecret配置错误<br>• 微信运动接口未开启<br>• 服务器网络问题 | • 检查AppID和AppSecret是否正确<br>• 确认微信运动接口已开启<br>• 查看服务器日志排查网络问题 |
| **数据不显示** | • 未成功同步数据<br>• 显示位置配置错误<br>• 模板文件未正确引用插件 | • 确认小程序同步成功<br>• 检查插件设置中的显示位置<br>• 检查模板文件是否正确引用插件 |
| **使用体验版** | • 无需通过审核<br>• 适合个人长期使用 | • 在微信开发者工具中上传代码<br>• 选择"添加为体验版"<br>• 将自己微信添加为体验者（最多可添加100人）<br>• 在手机上访问体验版小程序<br>• 体验版可长期使用，无时间限制 |

## 7. 技术实现概览

| 组件 | 文件 | 功能描述 |
|------|------|---------|
| **插件核心** | Plugin.php | 插件主文件，负责插件的安装、卸载和配置 |
|  | Action.php | 处理API请求，实现数据解密和存储 |
|  | Widget.php | 负责前端数据展示 |
| **小程序** | app.js | 小程序入口文件 |
|  | index.js | 主页面逻辑，实现步数获取和同步 |
|  | index.wxml | 主页面布局 |
|  | index.wxss | 主页面样式 |


## 8. 参考资料

1. [微信小程序文档 - wx.getWeRunData](https://developers.weixin.qq.com/miniprogram/dev/api/open-api/werun/wx.getWeRunData.html)
2. [微信小程序文档 - 用户隐私保护指引](https://developers.weixin.qq.com/miniprogram/dev/framework/user-privacy.html)
3. [Typecho插件开发文档](http://docs.typecho.org/plugins)
4. [AES加密算法详解](https://en.wikipedia.org/wiki/Advanced_Encryption_Standard)

## 9. 最后

如果有什么使用问题或者改进建议，可在此评论区留言或加入QQ群825038573

---

*本文档由璇编写，最后更新于2025年7月15日* 