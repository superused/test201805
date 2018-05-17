<?php
// 初期URL
$url = 'https://no1s.biz/';

$crawling = new crawling($url);
$crawling->start();

/**
 * クローリングクラス
 */
class crawling {
    public $initUrl;
    public $urlDatas = []; // 配列キーにURL、値にタイトルを入れる

    /**
     * コンストラクタ
     *
     * @param $url 最初にクローリングするURL
     */
    public function __construct($url) {
        $this->initUrl = $url;
        $this->initExecDone = false;
    }

    /**
     * クローリング開始
     */
    public function start() {
        $crawlFlg = false;

        // 初回判定
        if (count($this->urlDatas) == 0 && !$this->initExecDone) {
            $this->initExecDone = true; // 無限ループ防止

            $this->exec($this->initUrl, $this->urlDatas);
            $crawlFlg = true;
        } else {
            foreach ($this->urlDatas as $crawledUrl => $title) {
                // タイトルが取得できていなければそのURLのクローリングを行う
                if ($title === false) {
                    $this->exec($crawledUrl, $this->urlDatas);
                    $crawlFlg = true;
                }
            }
        }

        // 全てのURLのタイトルを取得するまでクローリングを開始し続ける
        if ($crawlFlg) {
            $this->start($this->urlDatas);
        }
    }

    /**
     * クローリング処理
     *
     * @params $url クローリングするURL
     */
    public function exec($url) {
        $html = file_get_contents($url);
        // タイトル取得
        $title = $this->getTitle($html);

        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'sjis'));
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//a') as $node) {
            // aタグのhrefの値を取得する
            $href = $xpath->evaluate('string(@href)', $node);

            // 外部サイトを取得しないように、最初に初期URL文字列が入っていないと処理しない
            if (strpos($href, $this->initUrl) === 0) {
                // URL末尾に"/"があったりなかったり、"//"だった時に同じページが被る問題に対処
                if (substr($href, -1) != '/') $href .= '/';
                if (substr($href, -2) == '//') $href = substr($href, 0, -1);

                // urlDatasに追加されていなければ、追加
                if (!array_key_exists($href, $this->urlDatas)) {
                    $this->urlDatas[$href] =  false; // 初期状態ではタイトルはfalse
                }
            }
        }

        if (isset($this->urlDatas[$url])) {
            $this->urlDatas[$url] = $title;

            // 取得したURL、タイトルを表示
            echo $url . '	' . $title . "\n";
        }
    }

    /**
     * HTMLデータからタイトル取得
     *
     * @params $html HTMLデータ
     * @return ページタイトル
     */
    public function getTitle($html){
        $title = '';
        $match = '@<title>([^<]++)</title>@i';
        $order = 'ASCII,JIS,UTF-8,CP51932,SJIS-win';

        if (
            preg_match($match, mb_convert_encoding($html, 'UTF-8', $order), $result) &&
            isset($result[1])
        ) {
            $title = str_replace(PHP_EOL, '', $result[1]); // 改行を削除
        }

        return $title;
    }
}
