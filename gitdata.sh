#!/usr/bin/php
<?php
require_once 'vendor/autoload.php';
require_once 'code4shinjuku/Data/Covid19/Git.php';
use GetOpt\GetOpt;
use GetOpt\Option;
use GetOpt\Command;

// set command options
$cmd = new GetOpt();
$cmd->addOptions([
    Option::create('h', 'help', GetOpt::NO_ARGUMENT)->setDescription('Show this help and quit'),
    ])->addCommands([
        Command::create('update', 'Git::update')->setDescription('tokyo-metropolitan-gov/covid19のgitリポジトリをアップデート'),
        Command::create('patient','Git::patient')->setDescription('患者の日別データをJSON出力する')
          ->addOptions([
              Option::create('p', 'php',      GetOpt::NO_ARGUMENT)->setDescription('PHPの配列で出力'),
              Option::create('c', 'city',     GetOpt::REQUIRED_ARGUMENT)->setDescription('区市町村を指定して表示'),
              Option::create('d', 'diff',     GetOpt::NO_ARGUMENT)->setDescription('前日との差分の数を表示'),
              Option::create('l', 'citylist', GetOpt::NO_ARGUMENT)->setDescription('区市町村のリストを表示'),
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
            'city' => false,
            'citylist' => false,
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
        break;
    }
    $ret = call_user_func_array([
        new Code4Shinjuku\Data\Covid19\Git(__DIR__),
        $command_name,
        ], $args);
    if ($cmd->getOption('php')){
        var_export($ret); 
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
