<?php
// API関連情報はこちらの""の中に設定してください
define('CONSUMER_KEY', "");
define('CONSUMER_SECRET', "");
define('ACCESS_TOKEN', "");
define('ACCESS_TOKEN_SECRET', "");

$url = "https://api.twitter.com/1.1/search/tweets.json";
$method = "GET";

// user_timelineのパラメータ
$param = [
    "q" => "JustinBieber",
    "count" => 100
];

// OAuthパラメータ
$paramBase = [
    "oauth_token" => ACCESS_TOKEN,
    "oauth_consumer_key" => CONSUMER_KEY,
    "oauth_signature_method" => "HMAC-SHA1",
    "oauth_timestamp" => time(),
    "oauth_nonce" => microtime(),
    "oauth_version" => "1.0",
];

// リクエストパラメータ作成
$resultParam = array_merge($param, $paramBase);
ksort($resultParam);
$requestParams = http_build_query($resultParam, "", "&");
$requestParams = str_replace(["+", "%7E"], ["%20", "~"], $requestParams);

// OAuthの署名を作成してパラメータに追加
$resultParam["oauth_signature"] = base64_encode(hash_hmac(
    "sha1",
    urlencode($method) . "&" . urlencode($url) . "&" . urlencode($requestParams),
    urlencode(CONSUMER_SECRET) . "&" . urlencode(ACCESS_TOKEN_SECRET),
    true
));

// URLにuser_timelineのGETパラメータを追加
if ($param) $url .= "?" . http_build_query($param);

$context = [
    "http" => [
        "method" => $method,
        "header" => [
            "Authorization: OAuth " . http_build_query($resultParam, "", ","),
        ],
    ],
];

// データを取得
$json = file_get_contents($url, false, stream_context_create($context));
if (!$json) exit('error'); // 取得失敗した場合errorを表示

$datas = json_decode($json, true);
$images = [];
$count = 0;
$error = false;
foreach ($datas['statuses'] as $key => $data) {
	if (isset($data['entities']['media'][0]['media_url'])) {
		$mediaUrl = $data['entities']['media'][0]['media_url'];
		$images[] = $mediaUrl;
		$image = file_get_contents($mediaUrl);
		$mediaArr = explode('.', $mediaUrl);
		$extension = array_pop($mediaArr);
		$count++;
		if (!file_put_contents('./images/' . $count . '.' . $extension, $image)) {
			$error = true;
			$count--;
		}
		if (count($images) >= 10) break;
	}
}
if ($error) {
	echo "failed\n";
} else if (count($images) == 10) {
	echo "succeeded\n";
} else {
	echo "succeeded(not enough)\n";
}
