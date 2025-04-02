<?php
// 设置错误处理
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // 记录错误但不输出
    return true;
});

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 论语前缀
define('LUNYU_PREFIX', '子曰：');

// 处理非POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    outputJson(false, '只接受POST请求', null);
    exit;
}

// 获取请求体
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// 验证请求数据
if (!$data || !isset($data['action']) || !isset($data['content'])) {
    outputJson(false, '无效的请求参数，需要提供action和content字段', null);
    exit;
}

// 提取并验证参数
$action = $data['action'];
$content = $data['content']; // 不进行URL解码，因为后面会针对场景处理

if ($action !== 'encrypt' && $action !== 'decrypt') {
    outputJson(false, 'action参数必须为encrypt或decrypt', null);
    exit;
}

// 论语映射表 - 将base64字符映射到论语中的单个汉字
$lunyuMap = [
    'A' => '仁',  // 论语核心"仁"置于首位
    'B' => '礼',  // 礼为儒家重要规范
    'C' => '义',
    'D' => '智',
    'E' => '信',
    'F' => '忠',
    'G' => '孝',
    'H' => '悌',
    'I' => '恕',  // "己所不欲勿施于人"的恕道
    'J' => '勇',  // "见义不为无勇也"
    'K' => '温',  // 君子五德：温良恭俭让
    'L' => '良',
    'M' => '恭',
    'N' => '俭',
    'O' => '让',
    'P' => '中',  // 中庸之道
    'Q' => '和',  // 和为贵
    'R' => '德',
    'S' => '道',
    'T' => '敬',  // 君子敬而无失
    'U' => '诚',
    'V' => '敏',  // 君子欲讷于言而敏于行
    'W' => '惠',  // 君子惠而不费
    'X' => '毅',  // 士不可不弘毅
    'Y' => '讷',  // 君子欲讷于言
    'Z' => '学',  // 学而时习之
    'a' => '之',
    'b' => '思',  // 学而不思则罔
    'c' => '省',  // 吾日三省吾身
    'd' => '问',  // 不耻下问
    'e' => '知',  // 知之为知之
    'f' => '行',  // 听其言而观其行
    'g' => '贤',  // 见贤思齐
    'h' => '圣',
    'i' => '善',
    'j' => '美',
    'k' => '正',  // 政者正也
    'l' => '直',  // 以直报怨
    'm' => '宽',  // 宽则得众
    'n' => '友',  // 友于兄弟
    'o' => '文',  // 文质彬彬
    'p' => '质',
    'q' => '君',  // 君子
    'r' => '士',  // 士志于道
    's' => '师',  // 三人行必有我师
    't' => '教',  // 有教无类
    'u' => '政',
    'v' => '治',
    'w' => '天',  // 知天命
    'x' => '命',
    'y' => '性',  // 性相近也
    'z' => '习',  // 习相远也
    '0' => '一',  // 吾道一以贯之
    '1' => '贯',
    '2' => '九',  // 君子有九思
    '3' => '五',  // 五常/五德
    '4' => '十',  // 十室之邑
    '5' => '百',  // 百姓
    '6' => '千',
    '7' => '万',
    '8' => '始',  // 始可与言诗已矣
    '9' => '终',  // 慎终追远
    '+' => '乐',  // 知之者不如好之者，好之者不如乐之者
    '/' => '诗',  // 诗三百
    '=' => '书'   // 书云孝乎
];

// 反向映射表 - 从论语句子映射回base64字符
$reverseLunyuMap = [];
foreach ($lunyuMap as $key => $value) {
    $reverseLunyuMap[$value] = $key;
}

try {
    $result = null;
    
    if ($action === 'encrypt') {
        // 加密流程
        // 首先对内容进行URL解码，因为传入的是URL编码后的内容
        $decodedContent = urldecode($content);
        
        // 1. 将文本转换为UTF-8编码的字节
        $textBytes = mb_convert_encoding($decodedContent, 'UTF-8');
        
        // 2. 使用zlib压缩
        $compressed = gzdeflate($textBytes, 9, ZLIB_ENCODING_RAW);
        if ($compressed === false) {
            throw new Exception('压缩数据失败');
        }
        
        // 3. 转换为Base64
        $base64 = base64_encode($compressed);
        
        // 4. 将Base64字符映射为论语文字
        $lunyuText = '';
        for ($i = 0; $i < strlen($base64); $i++) {
            $char = $base64[$i];
            if (isset($lunyuMap[$char])) {
                $lunyuText .= $lunyuMap[$char];
            } else {
                $lunyuText .= $char;
            }
        }
        
        // 5. 添加"子曰："前缀
        $result = LUNYU_PREFIX . $lunyuText;
        outputJson(true, '加密成功', $result);
    } else {
        // 解密流程
        
        // 检查并去除"子曰："前缀
        if (mb_substr($content, 0, mb_strlen(LUNYU_PREFIX, 'UTF-8'), 'UTF-8') === LUNYU_PREFIX) {
            $content = mb_substr($content, mb_strlen(LUNYU_PREFIX, 'UTF-8'), null, 'UTF-8');
        }
        
        // 1. 将论语文字映射回Base64
        $base64 = '';
        
        for ($i = 0; $i < mb_strlen($content, 'UTF-8'); $i++) {
            $char = mb_substr($content, $i, 1, 'UTF-8');
            if (isset($reverseLunyuMap[$char])) {
                $base64 .= $reverseLunyuMap[$char];
            } else {
                $base64 .= $char;
            }
        }
        
        // 2. 从Base64解码
        $compressed = base64_decode($base64);
        if ($compressed === false) {
            throw new Exception('解码失败，可能不是有效的论语加密文本');
        }
        
        // 3. 使用zlib解压缩
        $decompressed = @gzinflate($compressed);
        if ($decompressed === false) {
            throw new Exception('解压缩失败，可能不是有效的论语加密文本');
        }
        
        // 4. 将字节转换回文本
        $text = mb_convert_encoding($decompressed, 'UTF-8');
        
        $result = $text;
        outputJson(true, '解密成功', $result);
    }
} catch (Exception $e) {
    outputJson(false, ($action === 'encrypt' ? '加密' : '解密') . '失败: ' . $e->getMessage(), null);
}

/**
 * 输出JSON响应，确保中文不转为Unicode
 */
function outputJson($success, $message, $data) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
} 