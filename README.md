# Code For Shinjuku Covid19感染者データの区市町村別整形スクリプト

https://github.com/tokyo-metropolitan-gov/covid19 のデータを区市町村別にしたものです。

PHPをコマンドラインから実行させることで実行します。PHP7.3で確認しています。

## インストール方法
composerを使えるようにしておいてください。  
https://getcomposer.org/

### このリポジトリをcloneする

```
$ git clone git@github.com:codeforshinjuku/covid19.git
```

```
Cloning into 'covid19'...
remote: Enumerating objects: 16, done.
remote: Counting objects: 100% (16/16), done.
remote: Compressing objects: 100% (13/13), done.
remote: Total 16 (delta 1), reused 16 (delta 1), pack-reused 0
Receiving objects: 100% (16/16), 10.36 KiB | 10.36 MiB/s, done.
Resolving deltas: 100% (1/1), done.
$ ls
covid19
```
### composer.jsonのあるディレクトリに移動して、composer installする。

```
$ cd covid19/
~/covid19$ ls
README.md  code4shinjuku  composer.json  dist  gitdata.sh  pdfdata.sh
~/covid19$ composer install
```

```
Loading composer repositories with package information
Updating dependencies (including require-dev)
Package operations: 11 installs, 0 updates, 0 removals
  - Installing ulrichsg/getopt-php (v3.3.0): Loading from cache
  - Installing symfony/process (v5.0.7): Loading from cache
  …（略）
```
### 東京都Covid19のGitHubをcloneする。
データの供給元になるので、cloneしてください。

```
~/covid19$ ./gitdata.sh update
```
を最初に実行すると、以下のようにエラーが出るので
```
[Error] gitリポジトリがありません。
以下のコマンドを実行してください。
mkdir -p /home/itoh/xxxxxxxx/covid19/data/gitrepo/covid19/
cd /home/itoh/xxxxxxxx/covid19/data/gitrepo/
git clone git@github.com:tokyo-metropolitan-gov/covid19.git
```
mkdir / cd / git cloneコマンドをコピペして実行します。

```
~/covid19$ mkdir -p /home/itoh/xxxxxxxx/covid19/data/gitrepo/covid19/
~/covid19$ cd /home/itoh/xxxxxxxx/covid19/data/gitrepo
~/xxxxxxxx/covid19/data/gitrepo/covid19$ git clone git@github.com:tokyo-metropolitan-gov/covid19.git
Cloning into 'covid19'...
remote: Enumerating objects: 256856, done.
remote: Total 256856 (delta 0), reused 0 (delta 0), pack-reused 256856
Receiving objects: 100% (256856/256856), 296.15 MiB | 10.61 MiB/s, done.
Resolving deltas: 100% (127709/127709), done.
Checking out files: 100% (363/363), done.
```

インストールは以上です。

## 使い方
当プロジェクトファイルのcloneディレクトリのgitdata.shを実行します。-hでヘルプが出ます。

```
~/covid19$ ./gitdata.sh -h

Usage: ./gitdata.sh <command> [options] [operands]

Options:
  -h, --help  Show this help and quit

Commands:
  update   tokyo-metropolitan-gov/covid19のgitリポジトリをアップデート
  patient  患者の日別データをJSON出力する
```

### 東京都Covid19のGitアップデート

先ほどと同じコマンドを実行すると、GitHubから東京都Covid19リポジトリをアップデートします。
```
~/covid19/data/gitrepo$ cd ../..
~/covid19$ ./gitdata.sh update
Already up to date.
```
先ほどcloneしたばかりなので、何もUpdateされないと思います。

### 感染者データの出力
patientコマンドを実行すると、東京都の全自治体の感染者データを出力します。  
-hでヘルプができます。
```
~/covid19$ ./gitdata.sh patient -h

Usage: ./gitdata.sh patient [options] [operands]

患者の日別データをJSON出力する

Options:
  -h, --help        Show this help and quit
  -p, --php         PHPの配列で出力
  -c, --city <arg>  区市町村を指定して表示
  -l, --citylist    区市町村のリストを表示
```

#### 例：新宿区の感染者データをJSONで出力
新宿区の自治体コード131032を指定します。
```
~/covid19$ ./gitdata.sh patient -c 131032
```
PHPのvar_dump()形式で出力すると目視しやすいデータ表示になります。
```
~/covid19$ ./gitdata.sh patient -c 131032 -p
array(21) {
  ["2020/3/31"]=>
  int(39)
  ["2020/4/1"]=>
  int(40)
  ....(略)
```

#### 例：自治体コードをみたい場合
東京都の自治体データの一覧を出力します。
```
~/covid19$ ./gitdata.sh patient -l -p
array(62) {
  [0]=>
  array(4) {
    ["code"]=>
    int(131016)
    ["area"]=>
    string(9) "特別区"
    ["label"]=>
    string(12) "千代田区"
    ["ruby"]=>
    string(12) "ちよだく"
  }
  [1]=>
  array(4) {
    ["code"]=>
    int(131024)
    ["area"]=>
    string(9) "特別区"
    ["label"]=>
    string(9) "中央区"
    ["ruby"]=>
    string(18) "ちゅうおうく"
  }
  ....(略)
```


