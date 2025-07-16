# WeFootStep - 微信步数同步到 Typecho 博客

WeFootStep 是一款专为 Typecho 博客设计的插件，能将您的微信运动步数以小部件的形式优雅地展示在博客侧边栏或任何您希望的位置。

## 功能特点

- **自动同步**: 通过微信小程序获取您的每日步数数据
- **优雅展示**: 在博客侧边栏或任意位置显示您的步数数据
- **简单配置**: 仅需填写小程序的 AppID 和 AppSecret
- **安全可靠**: 使用微信官方 API，数据加密传输
- **高度定制**: 支持自定义样式和展示位置

## 项目结构

```
WeFootStep/
├── Plugin.php             # 插件主文件
├── Action.php             # API请求处理器
├── Widget.php             # 前端渲染器
├── README.md              # 项目说明
├── WeFootStep使用指南.md   # 详细使用文档
└── miniprogram/           # 微信小程序部分
    ├── app.js
    ├── app.json
    └── pages/
        └── index/
            ├── index.js
            ├── index.json
            ├── index.wxml
            └── index.wxss
```

## 快速上手指南

### 第一步：安装与启用插件

1. 下载本仓库中的`typecho-plugin`文件夹
2. 将其重命名为`WeFootStep`并上传到您 Typecho 博客的`/usr/plugins/`目录
3. 登录博客后台，进入"控制台" -> "插件"，找到"WeFootStep"并**启用**它

### 第二步：配置插件

1. 启用插件后，点击"设置"
2. 填入您的微信小程序的`AppID`和`AppSecret`
   - 这些信息可以在[微信公众平台](https://mp.weixin.qq.com/)的"开发"->"开发设置"中找到

### 第三步：部署配套小程序

1. 在微信开发者工具中创建一个新项目
2. 将本仓库中的`miniprogram`文件夹内容复制到项目中
3. 修改`pages/index/index.js`中的 URL 为您的博客地址：
   ```javascript
   url: "https://您的博客域名/index.php/action/wefootstep?do=sync",
   ```
4. 在微信公众平台的"开发"->"开发设置"中，将您的博客域名添加到 request 合法域名

### 第四步：在博客上展示

**方法一：使用标准小部件**

1. 进入"控制台" -> "外观" -> "设置外观"
2. 在可用部件列表中，找到"微信运动步数"并拖动到侧边栏

**方法二：使用代码嵌入**
在您主题的模板文件（如`sidebar.php`）中添加：

```php
<section id="wefootstep_widget" class="widget">
    <h5 class="widget-title">微信步数</h5>
    <div class="widget-content">
        <?php if (class_exists('WeFootStep_Plugin')) echo WeFootStep_Plugin::render(); ?>
    </div>
</section>
```

### 最后
详细使用教程在https://blog.ybyq.wang/archives/730.html
