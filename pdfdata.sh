#!/usr/bin/php
<?php
require_once 'vendor/autoload.php';
require_once 'code4shinjuku/Data/Covid19/Pdf.php';
use GetOpt\GetOpt;
use GetOpt\Option;
use GetOpt\Command;

// set command options
$cmd = new GetOpt();
$cmd->addOptions([
    Option::create('h', 'help',    GetOpt::NO_ARGUMENT)->setDescription('Show this help and quit'),
    ])->addCommands([
        Command::create('download',  '')->setDescription('新データをダウンロード')
          ->addOptions([
              Option::create('o', 'overwrite', GetOpt::NO_ARGUMENT)->setDescription('既にダウンロードしていたファイルがあっても取得して上書きする。')
                ->setDefaultValue(false),
              ]),
        Command::create('parsepdf',  '')->setDescription('ダウンロードPDFデータを処理して表示'),
        Command::create('config','')->setDescription('データのURLとXPathを指定して保存します。')
          ->addOptions([
              Option::create('s', 'show',       GetOpt::NO_ARGUMENT)->setDescription('現在の設定値一覧を表示'),
              Option::create('u', 'url',        GetOpt::REQUIRED_ARGUMENT)->setDescription('PDFの一覧が表示されているページのURLを指定'),
              Option::create('x', 'xpath',      GetOpt::REQUIRED_ARGUMENT)->setDescription('PDFのリンクを表示するXPathを指定'),
              Option::create('d', 'dateregexp', GetOpt::REQUIRED_ARGUMENT)->setDescription('PDFのリンク文字列から日付を取得。例：令和\d+年\d+月\d+日')
                ->setDefaultValue('令和(\d+)年(\d+)月(\d+)日'),
              ]),
        ]);
try {
    $cmd->process();
    if ($cmd->getOption('help')){
        echo PHP_EOL . $cmd->getHelpText();
        exit;
    }
    if (!$command = $cmd->getCommand()){
        throw new Exception('有効なコマンドを指定してください。-hオプションでヘルプを表示します。');
    }
    $command_name = $command->getName();
    $args = [];
    switch ($command_name) {
      case 'download':
        $args['overwrite'] = $cmd->getOption('o');
        break;
      case 'parsepdf':
        break;
      case 'config':
        $args = [
            'show'       => null,
            'url'        => null,
            'xpath'      => null,
            'dateregexp' => null,
            ];
        if ($cmd->getOption('s')){
            $args['show'] = true;
        }
        else {
            if (!$url = $cmd->getOption('u')) {
                throw new Exception('URLを指定してください。');
            }
            $args['url']        = $url;
            if (!$xpath = $cmd->getOption('x')) {
                throw new Exception('XPathを指定してください。');
            }
            $args['xpath']      = $xpath;
        }
        $args['dateregexp'] = $cmd->getOption('d');
        break;
    }
    $data = 
      call_user_func_array([
          new Code4Shinjuku\Data\Covid19\Pdf(__DIR__),
          $command_name,
          ], $args);
} catch (Exception $exception) {
    file_put_contents('php://stderr',
                      sprintf('[Error] %s%s',
                      $exception->getMessage() ,PHP_EOL));
    exit(1);
}
