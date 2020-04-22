<?php
namespace Code4Shinjuku\Data\Covid19;
use \GuzzleHttp\Client;
require_once dirname(dirname(__DIR__)).'/Util.php';

class Pdf
{
    private $config_file = '';

    private $config = [];

    private $pdf_dir = '';

    /**
     * @brief 
     * @param コマンド実行時のPath
     * @retval
     */
    public function __construct($cmd_dir)
    {
        $this->cmd_dir     = $cmd_dir;
        $this->config_file = sprintf('%s/data/pdf/config.ini', $cmd_dir);
        $this->pdf_dir     = sprintf('%s/data/pdf', $cmd_dir);

        if (!file_exists($this->pdf_dir)){
            mkdir($this->pdf_dir, 0755, true);
        }
        if (file_exists($this->config_file)){
            $this->config = @unserialize(file_get_contents($this->config_file));
        }
    }
    
    
    
    /**
     * @brief 
     * @param 上書きダウンロードして保存する場合はtrue
     * @retval
     */
    public function download($overwrite = false)
    {
        if (!$this->config){
            throw new \Exception('有効な設定がありません。');
        }
        $url = parse_url($this->config['url']);
        $client = new Client([
            'base_uri' => sprintf('%s://%s', $url['scheme'], $url['host'])
            ]);
        
        $response = $client->request('GET', $url['path']);
        $dom = new \DOMDocument('1.0');
        if (@$dom->loadHTML($response->getBody()->getContents())){
            $xpath = new \DOMXPath($dom);
            foreach ($xpath->query($this->config['xpath']) as $k=>$link) {
                $date = '';
                if (preg_match('/'.$this->config['dateregexp'].'/',  $link->textContent, $m)){
                    if ($datetime = strtotime(sprintf('%04d/%02d/%02d', 2018 + $m[1], $m[2], $m[3]))) {
                        $date = date('Y-m-d', $datetime);
                        $pdf_path = sprintf('%s/%s.pdf', $this->pdf_dir, $date);
                        if (file_exists($pdf_path) && $overwrite === false){
                            printf("Skip\n  PDF file exists => %s\n", $pdf_path);
                        }
                        else {
                            $href = $link->getAttribute('href');
                            if (!preg_match('@^https?://@', $href)){
                                if (strpos($href, '/') === 0){
                                    $href = sprintf('%s://%s%s',
                                                    $url['scheme'], $url['host'], $href);
                                }
                                else {
                                    $href = sprintf('%s://%s%s/%s',
                                                    $url['scheme'], $url['host'], dirname($url['path']), $href);
                                }
                            }
                            printf("Downloading... %s\n", $href);
                            $response = $client->request('GET', $href);
                            $byte = file_put_contents($pdf_path, $response->getBody()->getContents());
                            printf("  Save %s (%s)\n", $pdf_path, \Code4Shinjuku\Util::number_format_filesize($byte));
                        }
                    }
                }
            }
        }
    }
    
    /**
     * @brief 
     * @param 表示するだけの場合はtrue
     * @param データの一覧HTMLがあるURL
     * @param データを示すXpath
     * @param データの日付を取得する正規表現（令和縛り）
     * @retval
     */
    public function config($show, $url, $xpath, $dateregexp)
    {
        if ($show !== true){
            if ($url){
                $this->config['url'] = $url;
            }
            if ($xpath){
                $this->config['xpath'] = $xpath;
            }
            if ($dateregexp){
                $this->config['dateregexp'] = $dateregexp;
            }
            if (!file_put_contents($this->config_file, serialize($this->config))){
                throw new \Exception(sprintf('File %s に書き込めません。', $this->config_file));
            }
        }
        
        if ($this->config) {
            foreach ($this->config as $k=>$v){
                printf("%s\t=> %s\n", $k, $v);
            }
        }
        else {
            echo '設定ファイルは存在していません。';
        }
        echo PHP_EOL;
    }
    
    
    /**
     * @brief PDFディレクトリを解析する
     * @param 既にパース済みデータがあっても解析する。
     * @retval
     */
    public function parsepdf($overwrite = false)
    {
        $files = glob($this->pdf_dir.'/*.pdf');
        foreach ($files as $file){
            $text = Spatie\PdfToText\Pdf::getText($file);
            $regexp = [
                '港\n新宿\n文京\n台東\n墨田\n江東\n\n\d+\n\n\d+\n\n\d+\n\n(\d+)\n\n\d+\n\n\d+\n\n\d+\n\n\d+\n\n\d+\n\n\d+\n\n\d+\n\n世田谷\n\n渋谷\n\n中野\n\n杉並\n\n豊島\n', // 2020-04-07
                '世田谷\n\n渋谷\n\n江戸川\n\n(\d+)\n\n\d+\n\n\d+\n\n\d+\n\n\d+\n\n中野\n\n杉並\n\n豊島\n', // 2020-04-10
                '千代田\n中央\n港\n新宿\n文京\n台東\n墨田\n江東\n\n\d+\n\n\d+\n\n\d+\n\n(\d+)\n\n',
            ];
            foreach ($regexp as $_r){
                if (preg_match('@'.$_r.'@', $text, $m)){
                    $pathinfo = pathinfo($file);
                    printf("%s => %s\n", $pathinfo['filename'], $m[1]);
                    break;
                }
            }
        }
    }
}
