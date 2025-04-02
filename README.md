# 论语曰 - 文字加密工具

可以使用工具将指定文本转换为论语中的一些常用字，并提供了一个简单的API接口（PHP）。

## API接口说明

### 请求方式
- **POST** `/lunyu_api.php`

### 请求参数
请求体为JSON格式，包含以下字段：

| 参数名 | 类型 | 必填 | 说明 |
| ------ | ---- | ---- | ---- |
| action | string | 是 | 操作类型，可选值：`encrypt`（加密）或 `decrypt`（解密） |
| content | string | 是 | 需要加密或解密的内容，加密时需要先进行URL编码 |

### 响应格式
响应体为JSON格式，包含以下字段：

| 字段名 | 类型 | 说明 |
| ------ | ---- | ---- |
| success | boolean | 操作是否成功 |
| message | string | 操作结果说明 |
| data | string/null | 加密或解密后的结果，失败时为null |

## 使用示例

### 截图
![undefined](https://y.gtimg.cn/music/photo_new/T053M000001bb5uF1ckkCy.png)
![undefined](https://y.gtimg.cn/music/photo_new/T053M000001Zv5lb4KP8P3.png)
![undefined](https://y.gtimg.cn/music/photo_new/T053M000000NpOpK02e6ku.png)

### 加密示例（JavaScript）
```javascript
// 加密时，需要先将原文进行URL编码
const text = "这是一段需要加密的文本";
const encodedText = encodeURIComponent(text);

fetch('https://你的域名/lunyu_api.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    action: 'encrypt',
    content: encodedText
  })
})
.then(response => response.json())
.then(data => {
  console.log('加密结果:', data.data);
})
.catch(error => {
  console.error('请求错误:', error);
});
```

### 解密示例（JavaScript）
```javascript
// 解密时，直接传入论语加密文本，无需URL编码
// API会自动处理"子曰："前缀
const encryptedText = "子曰：仁礼义智信忠孝...";

fetch('https://你的域名/lunyu_api.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    action: 'decrypt',
    content: encryptedText  // 注意：解密时不需要URL编码
  })
})
.then(response => response.json())
.then(data => {
  console.log('解密结果:', data.data);
})
.catch(error => {
  console.error('请求错误:', error);
});
```

## 注意事项

1. 加密时，为确保特殊字符能正确传输，需要先对原文进行URL编码再发送
2. 解密时，直接传入论语加密文本，无需进行URL编码
3. API会根据action参数自动处理编码问题
4. 加密结果会自动添加"子曰："前缀
5. 解密时会自动检测并移除"子曰："前缀（前缀不存在也能正常解密）

## 加密原理
1. URL解码传入内容，还原原始文本
2. 将原始文本转换为UTF-8编码
3. 使用zlib压缩算法压缩文本
4. 将压缩后的数据转换为Base64编码
5. 将Base64字符替换为对应的论语汉字
6. 在加密结果前添加"子曰："前缀

## 解密原理
1. 检测并移除"子曰："前缀
2. 将论语汉字还原为Base64字符
3. 将Base64解码为压缩后的二进制数据
4. 使用zlib解压缩算法解压数据
5. 将解压后的数据转换为UTF-8文本 