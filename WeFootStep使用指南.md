## 1. 项目概述

WeFootStep 是一个 Typecho 插件，它能够将用户的微信运动步数同步到您的 Typecho 博客并在前端展示。本项目由 **Typecho 插件**（后端）和**微信小程序**（前端）两部分组成。通过这两部分的协同工作，实现了微信运动数据的采集、传输、存储和展示的完整流程。

### 1.1 功能特点

- **步数同步**：通过微信小程序安全地获取并同步微信运动步数。
- **数据统计**：在导航栏提供今日步数、周/月平均、累计步数和最佳纪录等统计功能。
- **历史图表**：以图表形式直观展示历史步数变化趋势。
- **后台管理**：支持在后台查看和管理已同步的步数记录。
- **高度自定义**：支持将步数模块显示在侧边栏、页脚或主题任意自定义位置。
- **安全可靠**：严格遵循微信官方加密和验证机制，确保数据安全。

### 1.2 技术架构

![工作流程][1]

## 2. 安装与配置

### 2.1 插件安装

1.  从 GitHub 下载 `WeFootStep` 项目的 ZIP 压缩包并解压。
2.  将解压后得到的 `WeFootStep` 文件夹上传到您 Typecho 博客的 `/usr/plugins/` 目录下。
3.  在 Typecho 后台管理界面的“控制台” > “插件”中，找到 WeFootStep 并**启用**它。

### 2.2 插件配置

启用插件后，点击“设置”进入配置页面：

-   **微信小程序 AppID / AppSecret**：**（必填）** 填写您的小程序 `AppID` 和 `AppSecret`。
-   **显示样式与位置**：选择步数在前台的显示样式（如进度条、文本）和位置（侧边栏、页脚或自定义）。
-   **卸载时是否删除数据**：建议保留数据，除非您确定不再需要历史步数记录。

![插件配置截图][2]

### 2.3 微信小程序配置

`WeFootStep` 目录中包含的 `miniprogram` 文件夹是小程序的前端项目。

1.  **创建小程序**：如果您还没有小程序，请先在 [微信公众平台](https://mp.weixin.qq.com/) 注册并创建一个。
2.  **获取凭证**：在“开发” > “开发管理” > “开发设置”中，找到并记录您的 `AppID` 和 `AppSecret`。
![获取AppID和AppSecret][3]
3.  **开启接口权限**：在“开发” > “开发管理” > “接口设置”中，务必手动开启“微信运动步数”接口权限。
![开启接口权限][4]
4.  **配置服务器域名**：在“开发” > “开发管理” > “开发设置” > “服务器域名”中，将您的**博客域名**添加到 `request合法域名` 列表中。**这是小程序能成功请求到您博客的必要前提。**
![添加合法域名][5]
5.  **配置小程序代码**：
    *   使用“微信开发者工具”导入 `miniprogram` 文件夹。
    *   打开 `pages/index/index.js` 文件，将第 `8` 行的 `url` 修改为您博客的同步接口地址。地址必须是公网可访问的 HTTPS 地址。
        ```javascript
        //...
        url: 'https://您的博客地址/index.php/action/wefootstep?do=sync',
        //...
        ```
    *   确保 `app.json` 中已添加获取微信运动数据的权限声明：
        ```json
        {
          "requiredPrivateInfos": ["getRunData"]
        }
        ```
![修改小程序URL][6]
6.  **上传和预览**：在微信开发者工具中，点击“上传”将代码上传到微信后台，然后设置为“体验版”供自己或朋友测试使用。

## 3. 使用方法

### 3.1 博客端数据展示

-   **自动显示**：如果您在插件设置中选择了“侧边栏”或“页脚”，插件会自动在相应位置显示。
-   **自定义位置**：如果选择“自定义位置”，您需要在主题的模板文件（如 `sidebar.php`, `footer.php` 或 `header.php`）中，在您希望展示步数的地方添加以下调用代码：
    ```php
    <?php WeFootStep_Plugin::render(); ?>
    ```
    或者使用 HTML 注释作为标记（适用于不方便修改 PHP 的场景）：
    ```html
    <!-- WeFootStep -->
    ```
-   **导航栏统计**：导航栏的步数统计功能是自动集成的，启用插件后即可在前台看到效果。

### 3.2 小程序端数据同步

小程序支持两种同步方式：

1.  **自动同步**：每次打开小程序时，它会自动尝试从微信服务器获取最新步数并同步到您的博客。
2.  **手动同步**：可以随时点击界面上的“同步步数”按钮来触发一次同步。

成功同步后，小程序界面会显示“同步成功”的提示，同时您的博客前端数据也会刷新。

![设为体验版][7]
![手机端体验][8]
![同步成功][9]

## 4. 功能与接口

### 4.1 后台管理

插件在 Typecho 后台的“独立页面”菜单旁增加了一个“微信步数”管理面板，您可以在此：
- 查看所有已同步的步数历史记录。
- 对特定日期的步数记录进行编辑或删除。

### 4.2 API 接口

WeFootStep 提供了两个核心的 API 接口：

-   `POST /index.php/action/wefootstep?do=sync`：**小程序专用接口**，用于接收小程序发送的加密步数数据并执行同步操作。
-   `GET /index.php/action/wefootstep?do=getStepData`：**前端专用接口**，用于为导航栏的统计模块提供格式化的 JSON 数据。

## 5. 主题集成：以 Handsome 主题为例

本教程将指导您如何直接修改 Handsome 主题，在顶部导航栏中添加一个交互式的微信运动步数统计面板，契合主题所有颜色且支持夜间模式，具体参考本站顶部。此方法比创建新文件更稳定。
![最终效果][10]

### 5.1 第一步：修改 `headnav.php` 文件

1.  **定位文件**：用编辑器打开 Handsome 主题的 `headnav.php` 文件。该文件位于主题目录的 `component` 文件夹下：`/usr/themes/handsome/component/headnav.php`。

2.  **添加HTML面板**：在文件中搜索 `class="dropdown pos-stc`，找到如下图所示的列表项 (`<li ...>`)。**紧跟在这整个 `</li>` 标签的后面**，添加以下代码，这是步数统计面板的 HTML 结构：
![][11]
    ```html
    <!-- 步数统计 -->
    <li class="dropdown pos-stc" id="FootStepDataPos">
        <a id="FootStepData" href="#" data-toggle="dropdown" class="dropdown-toggle feathericons dropdown-toggle">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trending-up">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                <polyline points="17 6 23 6 23 12"></polyline>
            </svg>
            <span class="caret"></span>
        </a>
        <div class="dropdown-menu wrapper w-full bg-white">
            <div class="row">
                <div class="col-sm-4 b-l b-light">
                    <div class="m-t-xs m-b-xs font-bold">步数统计</div>
                    <div class="">
                        <span class="pull-right text-success" id="today_steps">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                        <span><i class="fas fa-shoe-prints fa-fw" aria-hidden="true"></i> 今日步数</span>
                    </div>
                    <br />
                    <div class="">
                        <span class="pull-right text-success" id="week_avg_steps">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                        <span><i class="fas fa-chart-line fa-fw" aria-hidden="true"></i> 本周平均</span>
                    </div>
                    <br />
                    <div class="">
                        <span class="pull-right text-success" id="month_avg_steps">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                        <span><i class="fas fa-calendar-alt fa-fw" aria-hidden="true"></i> 本月平均</span>
                    </div>
                    <br />
                    <div class="">
                        <span class="pull-right text-success" id="total_steps">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                        <span><i class="fas fa-walking fa-fw" aria-hidden="true"></i> 累计步数</span>
                    </div>
                    <br />
                    <div class="">
                        <span class="pull-right text-success" id="best_day">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                        <span><i class="fas fa-trophy fa-fw" aria-hidden="true"></i> 最佳记录</span>
                    </div>
                    <br />
                    <div class="">
                        <span class="pull-right text-danger" id="worst_day">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                        <span><i class="fas fa-bed fa-fw" aria-hidden="true"></i> 最差记录</span>
                    </div>
                </div>
                <div class="col-sm-8 b-l b-light">
                    <div class="m-t-xs m-b-xs font-bold">步数趋势</div>
                    <div class="text-center">
                        <nav class="loading-echart text-center m-t-lg m-b-lg">
                            <p class="infinite-scroll-request"><i class="animate-spin fontello fontello-refresh"></i>加载中...</p>
                        </nav>
                        <div id="steps-chart" class="top-echart hide" style="height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </li>
    ```

3.  **添加依赖库和脚本**：滚动到 `headnav.php` 文件的**最底部**，在 `</header>` 标签的**正上方**，粘贴以下所有代码。这部分代码包含了面板运行所需的 ECharts、FontAwesome 图标库以及数据处理和图表渲染的 JavaScript 脚本。
[hide]
```php
    <!-- 步数统计面板依赖 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>

    <!-- 步数统计图表的JavaScript代码 -->
    <script>
        try { // Add a global try-catch to alert any errors
            (function($) { // Use a jQuery closure to be safe
                $(document).ready(function() {
                    var chartLoaded = false;
    
                    // Function to prepare and get data from PHP
                    function getStepData() {
                        <?php
                        // Check if the plugin class exists before using it
                        if (class_exists('WeFootStep_Plugin')) {
                            try {
                                $db = Typecho_Db::get();
                                $history = $db->fetchAll($db->select()->from('table.we_foot_step')->order('date', Typecho_Db::SORT_DESC)->limit(30));
        
                                $stats = [
                                    'today_steps' => 0, 'week_avg' => 0, 'month_avg' => 0,
                                    'total_steps' => 0, 'best_record' => null, 'worst_record' => null
                                ];
        
                                if (!empty($history)) {
                                    $week_steps = 0; $week_count = 0;
                                    $month_steps = 0; $month_count = 0;
                                    $best_step_count = 0; $best_date = '';
                                    $worst_step_count = -1; $worst_date = '';
                                    $seven_days_ago = strtotime('-7 days');
        
                                    if ($history[0]['date'] == date('Y-m-d')) {
                                        $stats['today_steps'] = (int)$history[0]['step_count'];
                                    }
        
                                    foreach ($history as $item) {
                                        $current_steps = (int)$item['step_count'];
                                        $item_timestamp = strtotime($item['date']);
        
                                        $month_steps += $current_steps; $month_count++;
                                        if ($item_timestamp > $seven_days_ago) {
                                            $week_steps += $current_steps; $week_count++;
                                        }
        
                                        if ($current_steps >= $best_step_count) {
                                            $best_step_count = $current_steps; $best_date = $item['date'];
                                        }
        
                                        if ($worst_step_count == -1 || $current_steps < $worst_step_count) {
                                            $worst_step_count = $current_steps; $worst_date = $item['date'];
                                        }
                                    }
        
                                    $stats['week_avg'] = $week_count > 0 ? (int)round($week_steps / $week_count) : 0;
                                    $stats['month_avg'] = $month_count > 0 ? (int)round($month_steps / $month_count) : 0;
                                    if ($best_step_count > 0) $stats['best_record'] = ['date' => $best_date, 'step_count' => $best_step_count];
                                    if ($worst_step_count != -1) $stats['worst_record'] = ['date' => $worst_date, 'step_count' => $worst_step_count];
                                }
        
                                $totalStepsRow = $db->fetchRow($db->select('SUM(step_count) AS total_steps')->from('table.we_foot_step'));
                                $stats['total_steps'] = $totalStepsRow ? (int)$totalStepsRow['total_steps'] : 0;
        
                                $chartData = array_map(function ($item) {
                                    return ['date' => $item['date'], 'steps' => (int)$item['step_count']];
                                }, array_reverse($history));
        
                                $weFootStepData = ['stats' => $stats, 'chart_data' => $chartData];
                                echo 'var weFootStepData = ' . json_encode($weFootStepData) . ';';
                            } catch (Exception $e) {
                                echo "console.error('PHP Error: " . addslashes($e->getMessage()) . "');";
                                echo 'var weFootStepData = null;';
                            }
                        } else {
                             echo "console.error('WeFootStep plugin is not active or not found.');";
                             echo 'var weFootStepData = null;';
                        }
                        ?>
                        return weFootStepData;
                    }
    
                    // Listen for the dropdown event
                    $('#FootStepDataPos').on('show.bs.dropdown', function() {
                        if (chartLoaded) return;
    
                        var stepData = getStepData();
                        if (!stepData) {
                             $('#FootStepDataPos .loading-echart').html('<p style="color: red;">获取数据失败, WeFootStep插件可能未启用。</p>');
                            return;
                        }
    
                        $('#FootStepDataPos .loading-echart').hide();
                        $('#steps-chart').removeClass('hide').css('height', '300px');
                        updateStepStats(stepData.stats);
    
                        setTimeout(function() {
                            renderStepChart(stepData.chart_data);
                        }, 150);
    
                        chartLoaded = true;
                    });
    
                    function updateStepStats(stats) {
                        if (!stats) {
                            stats = { today_steps: 0, week_avg: 0, month_avg: 0, total_steps: 0, best_record: null, worst_record: null };
                        }
                        $('#today_steps').text(stats.today_steps ? stats.today_steps.toLocaleString() : '0');
                        $('#week_avg_steps').text(stats.week_avg ? stats.week_avg.toLocaleString() : '0');
                        $('#month_avg_steps').text(stats.month_avg ? stats.month_avg.toLocaleString() : '0');
                        $('#total_steps').text(stats.total_steps ? stats.total_steps.toLocaleString() : '0');
                        $('#best_day').html(stats.best_record ? stats.best_record.step_count.toLocaleString() + ' <small>(' + stats.best_record.date + ')</small>' : '暂无记录');
                        $('#worst_day').html(stats.worst_record ? stats.worst_record.step_count.toLocaleString() + ' <small>(' + stats.worst_record.date + ')</small>' : '暂无记录');
                    }
    
                    function renderStepChart(chartData) {
                        if (typeof echarts === 'undefined') {
                            $('#FootStepDataPos .loading-echart').html('<p style="color: red;">ECharts 库未加载。</p>').show();
                            return;
                        }
                        var myChart = echarts.init(document.getElementById('steps-chart'));
                        var dates = chartData.map(item => item.date);
                        var steps = chartData.map(item => item.steps);
    
                        var option = {
                            tooltip: { trigger: 'axis', confine: true, formatter: p => p[0].axisValue + '<br/>' + p[0].marker + '步数: <strong>' + p[0].data.toLocaleString() + '</strong>' },
                            grid: { left: '3%', right: '4%', bottom: '15%', containLabel: true },
                            xAxis: { type: 'category', data: dates, axisLabel: { rotate: 45, formatter: val => val.substring(5) } },
                            yAxis: { type: 'value', name: '步数', axisLabel: { formatter: val => val >= 1000 ? (val / 1000).toFixed(1) + 'k' : val }, splitLine: { lineStyle: { type: 'dashed' } } },
                            dataZoom: [{ type: 'slider', start: Math.max(0, 100 - (15 / (dates.length || 1) * 100)), end: 100, height: 20, bottom: 10, handleSize: '80%' }],
                            series: [{
                                data: steps, type: 'line', smooth: true, symbol: 'emptyCircle', symbolSize: 6,
                                lineStyle: { width: 2, color: new echarts.graphic.LinearGradient(0, 0, 1, 0, [{ offset: 0, color: '#4CAF50' }, { offset: 1, color: '#81C784' }]) },
                                areaStyle: { color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{ offset: 0, color: 'rgba(76, 175, 80, 0.4)' }, { offset: 1, color: 'rgba(76, 175, 80, 0.05)' }]) },
                                markPoint: { data: [{ type: 'max', name: '最多' }, { type: 'min', name: '最少' }] },
                                markLine: { data: [{ type: 'average', name: '平均值' }], lineStyle: { type: 'dashed' } },
                                animation: false
                            }]
                        };
                        myChart.setOption(option);
                        $(window).on('resize', () => myChart.resize());
                    }
                });
            })(jQuery);
        } catch (e) {
            console.error('WeFootStep critical error: ' + e.message);
        }
    </script>
    ```
[/hide]
    
    > **代码说明**：这段代码直接在页面内查询数据库并渲染数据，不依赖外部AJAX请求，性能更好且更稳定。同时，它会自动检查 `WeFootStep` 插件是否启用，如果未启用，会在面板中给出提示，避免网站出错。


## 6. 同步问题排查指南

同步失败是初期配置时最常见的问题。请遵循以下步骤进行排查：

### 6.1 第一步：小程序端自查

打开微信开发者工具，在“控制台(Console)”中查看是否有错误信息。

| 控制台错误信息 | 可能原因 | 解决方案 |
|---|---|---|
| `request:fail url not in domain list` | 请求的博客地址未被添加到小程序的合法域名中。 | 登录微信公众平台，在“开发” > “开发设置” > “服务器域名”中，将您的博客域名（如 `https.blog.example.com`）添加到 `request合法域名`。 |
| `request:fail timeout` | 请求超时。可能是您的服务器响应慢，或网络连接不佳。 | 检查服务器性能和网络状况。确保博客地址可以从公网快速访问。 |
| `[AppID] is not a valid official account` | 插件设置中的 `AppID` 或 `AppSecret` 填写错误。 | **仔细核对**插件设置中的 `AppID` 和 `AppSecret` 是否与微信公众平台上显示的完全一致，注意不要有空格。 |
| 其他网络错误 (如 404, 500) | 小程序发出的请求未能被服务器正确处理。 | 这是服务器端的问题，请跳转到 **第二步** 进行排查。 |

### 6.2 第二步：服务器端排查

如果小程序端没有明确的配置错误，问题通常出在服务器或 Typecho 插件端。

| 问题现象 | 可能原因 | 解决方案 |
|---|---|---|
| **请求返回 404 Not Found** | 1. Typecho 未能正确处理 Action 路由。<br>2. 服务器（Nginx/Apache）的伪静态/重写规则配置不正确。 | 1. 确认插件已**正确启用**。<br>2. 检查 Typecho “设置” > “永久链接”是否启用，并确认您的服务器已配置了对应的伪静态规则。对于 Nginx，请确保有类似 `location / { ... try_files $uri $uri/ /index.php?$args; }` 的配置。 |
| **请求返回 500 Internal Server Error** | PHP 代码执行出错。 | **查看服务器的 PHP 错误日志**。这是定位问题的最有效方法。错误日志会明确指出是哪个文件的哪一行代码出了问题。 |
| **请求有响应但内容为空，或提示“解密失败”** | `session_key` 获取失败或解密过程出错。 | 1. 再次确认 `AppID` 和 `AppSecret` **绝对正确**。<br>2. 确保您的服务器可以正常访问微信的 API 服务器 (`api.weixin.qq.com`)，没有被防火墙或网络策略阻挡。 |
| **数据同步了，但前端不显示** | 1. 博客或 CDN 缓存导致页面未更新。<br>2. 插件显示位置配置不正确或主题不兼容。 | 1. 清理您博客、CDN 或浏览器的缓存。<br>2. 确认插件设置中的显示位置。如果使用“自定义位置”，请检查调用代码是否已正确添加到主题文件中。 |

如果以上步骤均无法解决问题，请到项目的 GitHub Issues 页面提交您的问题，并附上您在排查过程中收集到的错误信息。

## 7. 工作原理详解

### 7.1 数据同步流程

1.  **用户授权**：用户在微信小程序中授权允许获取微信运动数据。
2.  **获取凭证**：小程序通过 `wx.login()` API 获取微信用户的临时登录凭证 `code`。
3.  **获取步数**：小程序通过 `wx.getWeRunData()` API 获取已加密的微信运动数据 `encryptedData` 和初始向量 `iv`。
4.  **发起请求**：小程序将 `code`、`encryptedData` 和 `iv` 一同发送到您在插件中配置的 Typecho 博客后端 API 接口。
5.  **服务端验证**：
    *   Typecho 插件后端接收到请求后，将 `code`、`AppID` 和 `AppSecret` 发送给微信认证服务器，换取用户的 `session_key`。
    *   使用 `session_key` 和 `iv` 对 `encryptedData` 进行 AES-128-CBC 解密。
6.  **数据入库**：解密成功后，从中提取步数和日期，并验证数据水印。验证通过后，将步数数据存入 Typecho 数据库的 `typecho_we_foot_step` 表中。
7.  **前端展示**：博客前端通过插件提供的函数或 API 接口，从数据库读取步数数据，并根据您的配置进行展示和统计。

### 7.2 安全机制

- **数据加密**：微信运动数据全程使用 AES-128-CBC 算法加密传输。
- **身份验证**：使用有时效性的临时登录凭证 `code` 和 `session_key` 机制，确保请求来自合法用户和小程序。
- **数据水印**：解密后的数据包含小程序 `appid` 水印，用于校验数据的完整性和来源，有效防止数据被篡改或伪造。

## 8. 技术实现概览

| 组件 | 文件及功能 |
|---|---|
| **插件核心** | <ul><li>`Plugin.php`: 插件主文件，负责安装/卸载、路由注册、钩子绑定、后台菜单和前端资源加载。</li><li>`Action.php`: API 请求处理器，负责数据同步和数据查询接口的逻辑。</li><li>`Widget.php`: 前端渲染器，负责生成步数模块的 HTML 和提供给主题调用的公共方法。</li><li>`manage.php`: 后台管理页面，用于展示和管理步数列表。</li><li>`edit.php`: 后台编辑页面，用于修改单条步数记录。</li></ul> |
| **小程序** | `miniprogram/`: 包含小程序的所有前端代码 (`app.js`, `pages/`, `app.json` 等)。 |

## 9. 其他

### 9.1 参考资料

1.  [微信小程序文档 - wx.getWeRunData](https://developers.weixin.qq.com/miniprogram/dev/api/open-api/werun/wx.getWeRunData.html)
2.  [微信小程序文档 - 用户隐私保护指引](https://developers.weixin.qq.com/miniprogram/dev/framework/user-privacy.html)
3.  [Typecho 插件开发文档](http://docs.typecho.org/plugins)

### 9.2 联系与支持

如果有什么使用问题或者改进建议，可在此评论区留言或加入QQ群825038573
Github下载地址：
[secret]
https://github.com/BXCQ/WeFootStep
[/secret]

---

*本文档由璇编写，最后更新于2025年7月*

---


  [1]: https://static.blog.ybyq.wang/usr/uploads/2025/07/16/2025-07-16T01:53:30.png?x-oss-process=style/shuiyin
  [2]: https://static.blog.ybyq.wang/usr/uploads/2025/07/16/2025-07-16T01:23:56.png?x-oss-process=style/shuiyin
  [3]: https://static.blog.ybyq.wang/usr/uploads/2025/07/16/2025-07-16T01:11:39.png?x-oss-process=style/shuiyin
  [4]: https://static.blog.ybyq.wang/usr/uploads/2025/07/16/2025-07-16T01:13:44.png?x-oss-process=style/shuiyin
  [5]: https://static.blog.ybyq.wang/usr/uploads/2025/07/16/2025-07-16T01:45:02.png?x-oss-process=style/shuiyin
  [6]: https://static.blog.ybyq.wang/usr/uploads/2025/07/16/2025-07-16T01:16:02.png?x-oss-process=style/shuiyin
  [7]: https://static.blog.ybyq.wang/usr/uploads/2025/07/16/2025-07-16T01:18:16.png?x-oss-process=style/shuiyin
  [8]: https://static.blog.ybyq.wang/usr/uploads/2025/07/16/2025-07-16T01:26:15.png?x-oss-process=style/shuiyin
  [9]: https://static.blog.ybyq.wang/usr/uploads/2025/07/16/2025-07-16T01:29:06.png?x-oss-process=style/shuiyin
  [10]: https://static.blog.ybyq.wang/usr/uploads/2025/07/16/2025-07-16T08:51:59.png?x-oss-process=style/shuiyin
  [11]: https://static.blog.ybyq.wang/usr/uploads/2025/07/16/2025-07-16T08:40:49.png?x-oss-process=style/shuiyin