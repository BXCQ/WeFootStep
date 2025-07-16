<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

require_once 'Widget.php';

/**
 * WeFootStep 操作类
 *
 * @author 璇
 * @package WeFootStep
 */
class WeFootStep_Action extends Typecho_Widget implements Widget_Interface_Do
{
    /**
     * 日志记录
     * @param string $message
     */
    private function log($message)
    {
        // 在插件目录下创建日志文件，请确保目录有写权限
        $logFile = dirname(__FILE__) . '/request_log.txt';
        $formattedMessage = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
        file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    }

    /**
     * 处理同步请求
     */
    public function sync()
    {
        // Start output buffering to catch any stray output
        ob_start();

        // Set header at the very beginning
        @header('Content-Type: application/json; charset=utf-8');

        $this->log("====== New Sync Request ======");

        $options = Helper::options()->plugin('WeFootStep');
        if (empty($options->appid) || empty($options->secret)) {
            $this->log("Error: AppID or AppSecret is not configured.");
            $response = ['status' => 'error', 'message' => '插件未正确配置AppID或AppSecret'];
            $this->sendAndExit($response);
        }

        // 获取小程序POST的原始数据
        $rawPostData = file_get_contents("php://input");
        if (!$rawPostData) {
            $this->log("Error: No POST data received.");
            $response = ['status' => 'error', 'message' => '未收到请求数据'];
            $this->sendAndExit($response);
        }

        $this->log("Received raw data: " . $rawPostData);
        $postData = json_decode($rawPostData, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($postData['code']) || !isset($postData['iv']) || !isset($postData['encryptedData'])) {
            $this->log("Error: Invalid JSON or missing required parameters.");
            $response = ['status' => 'error', 'message' => '请求参数不完整或格式错误'];
            $this->sendAndExit($response);
        }

        $this->log("Parameters check passed. Code: " . substr($postData['code'], 0, 10) . '...');

        try {
            $sessionData = $this->_getSessionKeyFromWeChat($options->appid, $options->secret, $postData['code']);

            if (isset($sessionData['errcode']) && $sessionData['errcode'] != 0) {
                $this->log("Error from WeChat API (" . $sessionData['errcode'] . "): " . $sessionData['errmsg']);
                $response = ['status' => 'error', 'message' => '微信登录失败: ' . $sessionData['errmsg']];
                $this->sendAndExit($response);
            }

            $this->log("Successfully got session_key.");

            $decryptedData = $this->_decryptData($options->appid, $sessionData['session_key'], $postData['encryptedData'], $postData['iv']);

            if ($decryptedData['error']) {
                $this->log("Decryption failed: " . $decryptedData['message']);
                $response = ['status' => 'error', 'message' => '数据解密失败：' . $decryptedData['message']];
                $this->sendAndExit($response);
            }

            $this->log("Data decrypted successfully.");
            $stepInfoList = isset($decryptedData['data']['stepInfoList']) ? $decryptedData['data']['stepInfoList'] : [];
            $todayStepInfo = end($stepInfoList);

            if ($todayStepInfo && isset($todayStepInfo['step'])) {
                $this->log("Today's step count: " . $todayStepInfo['step']);
                $this->_saveStepsToDatabase($todayStepInfo['step']);
                $this->log("Steps saved successfully.");
                $response = ['status' => 'success', 'message' => '步数同步成功！', 'today_steps' => $todayStepInfo['step']];
                $this->sendAndExit($response);
            } else {
                $this->log("Error: Could not find today's step info in decrypted data.");
                $response = ['status' => 'error', 'message' => '未能获取到今日步数'];
                $this->sendAndExit($response);
            }
        } catch (Exception $e) {
            $this->log("Caught Exception: " . $e->getMessage());
            $this->log("Stack Trace: " . $e->getTraceAsString());
            $response = ['status' => 'error', 'message' => '服务器发生内部错误'];
            $this->sendAndExit($response);
        }
    }

    /**
     * 获取步数统计数据API
     */
    public function getStepData()
    {
        // 开始输出缓冲，捕获任何意外输出
        ob_start();

        // 设置响应头
        header('Content-Type: application/json; charset=utf-8');

        $this->log("====== Get Step Data Request ======");

        try {
            // 创建Widget实例来获取数据
            $widget = new WeFootStep_Widget();
            $history = $widget->getStepHistory();
            $stats = $widget->getStepStats();

            // 处理历史数据，格式化为前端图表所需格式
            $chartData = [];
            foreach ($history as $item) {
                $chartData[] = [
                    'date' => $item['date'],
                    'steps' => (int)$item['step_count']
                ];
            }

            // 按日期排序
            usort($chartData, function ($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });

            $response = [
                'status' => 'success',
                'data' => [
                    'stats' => $stats,
                    'chart_data' => $chartData
                ]
            ];

            $this->log("Step data retrieved successfully");
        } catch (Exception $e) {
            $this->log("Error in getStepData: " . $e->getMessage());
            $response = [
                'status' => 'error',
                'message' => '获取步数数据失败: ' . $e->getMessage()
            ];
        }

        $this->sendAndExit($response);
    }

    /**
     * Directly requests the session key from WeChat's API.
     * This method contains the cURL logic previously in WxRunHelper.
     *
     * @param string $appId
     * @param string $appSecret
     * @param string $code
     * @return array
     */
    private function _getSessionKeyFromWeChat($appId, $appSecret, $code)
    {
        $this->log("Attempting to get session key directly...");
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appId}&secret={$appSecret}&js_code={$code}&grant_type=authorization_code";
        $this->log("Requesting URL: " . $url);

        if (!function_exists('curl_init')) {
            $this->log("cURL is not enabled on this server.");
            return ['errcode' => -1, 'errmsg' => '服务器未启用cURL扩展，无法连接微信'];
        }

        $this->log("Using cURL for request.");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Increased timeout
        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            $this->log("cURL Execution Error: " . $error);
            return ['errcode' => -1, 'errmsg' => "cURL错误: {$error}"];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->log("cURL HTTP Status Code: " . $httpCode);
        $this->log("cURL Full Response: " . $response);

        if ($httpCode != 200) {
            return ['errcode' => $httpCode, 'errmsg' => '微信服务器响应异常'];
        }

        $data = json_decode($response, true);
        if (!$data) {
            $this->log("JSON Decode Error. Response was: " . $response);
            return ['errcode' => -1, 'errmsg' => '解析微信响应失败'];
        }

        return $data;
    }

    /**
     * Decrypts the WeChat run data.
     * This logic was previously in DataCrypt.php.
     *
     * @param string $appId
     * @param string $sessionKey
     * @param string $encryptedData
     * @param string $iv
     * @return array
     */
    private function _decryptData($appId, $sessionKey, $encryptedData, $iv)
    {
        $this->log("Attempting to decrypt data...");
        if (strlen($sessionKey) != 24) {
            return ['error' => true, 'message' => 'session_key 长度无效'];
        }
        $aesKey = base64_decode($sessionKey);

        if (strlen($iv) != 24) {
            return ['error' => true, 'message' => 'iv 长度无效'];
        }
        $aesIV = base64_decode($iv);

        $aesCipher = base64_decode($encryptedData);
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        if ($result === false) {
            return ['error' => true, 'message' => 'openssl_decrypt 失败'];
        }

        $dataObj = json_decode($result, true);
        if ($dataObj === null) {
            return ['error' => true, 'message' => '解密后的数据无法被 json_decode'];
        }

        if ($dataObj['watermark']['appid'] != $appId) {
            return ['error' => true, 'message' => '数据水印中的 appid 不匹配'];
        }

        return ['error' => false, 'data' => $dataObj];
    }


    /**
     * Saves the step count to the database.
     * This logic was previously in WxRunHelper.
     *
     * @param int $steps
     * @return void
     */
    private function _saveStepsToDatabase($steps)
    {
        $this->log("Attempting to save steps to database: " . $steps);
        $db = Typecho_Db::get();
        $time = time();
        $date = date('Y-m-d', $time);

        try {
            $exist = $db->fetchRow($db->select()->from('table.we_foot_step')->where('date = ?', $date));

            if ($exist) {
                $this->log("Updating existing record for today in database.");
                $db->query($db->update('table.we_foot_step')->rows(['step_count' => $steps, 'modified' => $time])->where('id = ?', $exist['id']));
            } else {
                $this->log("Inserting new record for today into database.");
                $db->query($db->insert('table.we_foot_step')->rows(['date' => $date, 'step_count' => $steps, 'created' => $time, 'modified' => $time]));
            }
        } catch (Exception $e) {
            $this->log("Database Save Error: " . $e->getMessage());
            // We don't exit here, as the main flow might have already sent a success response
        }
    }


    /**
     * 统一的JSON输出和退出函数
     * @param array $data The data to be encoded as JSON and sent.
     */
    private function sendAndExit($data)
    {
        $this->log("Response: " . json_encode($data));
        $this->log("====== Sync Request End ======\n");

        // Clean the buffer of any stray output
        ob_end_clean();

        // Echo the final, clean JSON
        echo json_encode($data);
        exit;
    }

    public function action()
    {
        $do = $this->request->get('do');
        switch ($do) {
            case 'sync':
            $this->sync();
                break;
            case 'getStepData':
            $this->getStepData();
                break;
            default:
                // For any other action, redirect to the homepage to prevent errors.
                $this->response->redirect($this->options->siteUrl);
                break;
        }
    }

    public function execute()
    {
        $this->action();
    }
}
