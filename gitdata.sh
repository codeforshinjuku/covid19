#!/usr/bin/php
<?php
require_once 'vendor/autoload.php';
require_once 'code4shinjuku/Data/Covid19/Git.php';
require_once 'code4shinjuku/Util.php';
use GetOpt\GetOpt;
use GetOpt\Option;
use GetOpt\Command;

date_default_timezone_set('Asia/Tokyo');

// set command options
$cmd = new GetOpt();
$cmd->addOptions([
    Option::create('h', 'help', GetOpt::NO_ARGUMENT)->setDescription('Show this help and quit'),
    ])->addCommands([
        Command::create('update', 'Git::update')->setDescription('東京都のcovid19のgitリポジトリをアップデート'),
        Command::create('patient','Git::patient')->setDescription('東京都のcovid19のdata/patient.jsonをもとに患者の日別データをJSON出力する')
          ->addOptions([
              Option::create('f', 'format',   GetOpt::REQUIRED_ARGUMENT)->setDescription('PHP/CSVの配列で出力')
                ->setValidation(function($value){
                    return in_array(strtolower($value), ['csv', 'php']);
                }, '--formatオプションは csv, php が有効です。'),
              Option::create('c', 'city',     GetOpt::REQUIRED_ARGUMENT)->setDescription('区市町村を指定して表示'),
              Option::create('d', 'diff',     GetOpt::NO_ARGUMENT)->setDescription('前日との差分の数を表示'),
              Option::create('l', 'citylist', GetOpt::NO_ARGUMENT)->setDescription('区市町村のリストを表示'),
          ]),
        Command::create('data','Git::data')->setDescription('東京都のcovid19のdata/data.jsonをもとに集計しJSON出力する(速報値のため必ずしも正確ではない？)')
          ->addOptions([
              Option::create('p', 'php',      GetOpt::NO_ARGUMENT)->setDescription('PHPの配列で出力'),
              Option::create('d', 'diff',     GetOpt::NO_ARGUMENT)->setDescription('前日との差分の数を表示'),
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
    switch($command_name) {
      case 'patient':
        $args = [
            'city'     => false,
            'citylist' => false,
            'diff'     => false,
            'format'   => 'json',
            ];
        if ($city = $cmd->getOption('c')){
            $args['city'] = $city;
        }
        if ($cmd->getOption('l')){
            $args['citylist'] = true;
        }
        if ($cmd->getOption('d')){
            $args['diff'] = true;
        }
        if ($format = $cmd->getOption('f')){
            $args['format'] = $format;
        }
        break;
      case 'data':
        $args = [
            'diff' => false,
            ];
        if ($cmd->getOption('d')){
            $args['diff'] = true;
        }
        break;
    }
    $ret = call_user_func_array([
        new Code4Shinjuku\Data\Covid19\Git(__DIR__),
        $command_name,
        ], $args);
    if ($cmd->getOption('f') === 'php'){
        var_export($ret); 
    }
    else if ($cmd->getOption('f') === 'csv'){
        \Code4Shinjuku\Util::VarDumpCSV($ret);
    }
    else {
        echo json_encode($ret);
    }
} catch (Exception $exception) {
    file_put_contents('php://stderr',
                      sprintf('[Error] %s%s',
                      $exception->getMessage() ,PHP_EOL));
    exit(1);
}
