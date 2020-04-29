<?php
namespace Code4Shinjuku\Data\Covid19;
require_once dirname(dirname(__DIR__)).'/Util.php';
use Code4Shinjuku\Util;

class Git
{
    private $git_dir = 'data/gitrepo/covid19/';
    
    /**
     * @brief 
     * @param コマンド実行時のPath
     * @retval
     */
    public function __construct($cmd_dir)
    {
        $this->git_dir = $cmd_dir . '/' . $this->git_dir;

        if (!file_exists($this->git_dir . '/.git')){
            throw new \Exception(
                sprintf("gitリポジトリがありません。 \n以下のコマンドを実行してください。\nmkdir -p %s\ncd %s\ngit clone git@github.com:tokyo-metropolitan-gov/covid19.git",
                        $this->git_dir, dirname($this->git_dir)));
        }
        chdir($this->git_dir);
    }
    
    
    
    /**
     * @brief Gitリポジトリをアップデート
     * @param 
     * @retval
     */
    public function update()
    {
        $r = Util::system('git pull');
        echo $r['stdout'];
        // echo "Git repository updated. データを集積します。\n";
    }
    

    
    
    
    /**
     * @brief 患者の区市町村別JSONデータを表示する
     * @param 
     * @retval
     */
    public function patient($city, $citylist, $diff, $format)
    {
        $data_name = 'patients';
        if ($diff){
            $data_name = 'diff';
        }
        
        $data = $this->getPatientData();
        if ($citylist){
            return $this->getPatientCityList($data);
        }
        if ($city) {
            if (!isset($data[$city])){
                throw new \Exception($city . 'という区市町村コードのデータはありません。');
            }
        }

        
        $ret = [];
        foreach ($data as $citycode => $d) {
            if (!$city || ($city == $citycode)){
                if ($format === 'csv'){
                    if (!isset($ret['header'])){
                        $ret['header'] = ['発表日', '区市町村', '人数'];
                        $ret['data']   = [];
                    }
                    foreach ($d[$data_name] as $date => $_num){
                        $ret['data'][]   = [
                            date('n/j/Y', strtotime($date)),
                            $d['city']['label'],
                            $_num
                            ];
                    }
                }
                else {
                    $ret[$citycode] = $d[$data_name];
                }
            }
        }

        return $ret;
    }
    
    
    /**
     * @brief 退院者の区市町村別JSONデータを表示する
     * @param 
     * @retval
     */
    public function data($diff)
    {
        $ret = $this->getDataData();
        if ($diff){
            foreach ($ret as &$_r){
                $yesterday = 0;
                foreach ($_r as $k=>$day_count){
                    $_r[$k] -= $yesterday;
                    $yesterday = $day_count;
                }
            }
        }
        return $ret;
    }
    
    
    
    /**
     * @brief 区市町村の一覧を取得
     * @param 
     * @retval
     */
    private function getPatientCityList($data)
    {
        $citylist = [];
        foreach ($data as $citycode => $d){
            $citylist[] = $d['city'];
        }
        return $citylist;
    }
    
    
    /**
     * @brief patientデータをgitリポジトリから取得
     * @param 
     * @retval array
     */
    private function getPatientData()
    {
        $target_file = 'data/patient.json';
        $r = Util::system('git log --oneline '. $target_file);
        $data = []; 
        foreach (array_reverse(explode("\n", trim($r['stdout']))) as $log){
            $com_id = current(explode(" ", $log));
            $cmd = sprintf('git show %s:%s > /dev/stdout', $com_id, $target_file);
            $r = Util::system($cmd);
            $d = json_decode($r['stdout'], true);
            // jsonのデータ形式がcommit時期により2パターンある
            $datasets_array = [];
            if (isset($d['datasets'])){
                // 1日ずつのデータ
                $datasets_array[] = [
                    'date'     => $d['datasets']['date'],
                    'datasets' => $d['datasets']['data']
                    ];
            }
            else {
                // 何日分かをまとめたもの
                $datasets_array = $d;
            }
            foreach ($datasets_array as $_d){
                $_date = $_d['date'];
                foreach ($_d['datasets'] as $_data){
                    $_city = $_data['code']; // 'label'に表記ゆれ(区市町村の有無)があるのでcode
                    if (!isset($data[$_city])) {
                        $data[$_city] = [
                            'city'     => $_data,
                            'patients' => [],
                            'diff'   => [],
                            ];
                        unset($data[$_city]['city']['count']);
                    }
                    $data[$_city]['patients'][$_date] = $_data['count'];
                    $yesterday = date('Y/n/j', strtotime('-1 day', strtotime($_date)));
                    $data[$_city]['diff'][$_date] = 
                      isset($data[$_city]['patients'][$yesterday])
                        ? $_data['count'] - $data[$_city]['patients'][$yesterday] : 0;
                }
            }
        }
        $data = array_filter($data, function($d){
            return $d['city']['code'] > 100000;
        });
        return $data;
    }


    /**
     * @brief tokyo-metropolitan-gov/covid19のdata/data.jsonから日別のデータを取得
     * @param 
     * @retval
     */
    private function getDataData()
    {
        // git周りの処理はgetPatientData()と同じだけど、共通化するとコミットが増えた場合にメモリが大量に必要になるのであえてコピー
        $target_file = 'data/data.json';
        $r = Util::system('git log --oneline '. $target_file);
        $ret = [
            'patient'   => [],
            'discharge' => [],
//            'age'       => [],
            ]; 
        foreach (array_reverse(explode("\n", trim($r['stdout']))) as $log){
            $com_id = current(explode(" ", $log));
            $cmd = sprintf('git show %s:%s > /dev/stdout', $com_id, $target_file);
            $r = Util::system($cmd);
            $d = json_decode($r['stdout'], true);
            
            $data = $d['patients'];
            if (!isset($data['date']) || !$data_date = strtotime($data['date'])) {
                continue;
            }
            $date = date('Y/n/j', $data_date);
            $ret['patient'][$date]   = 0;
            $ret['discharge'][$date] = 0;
            foreach ($data['data'] as $_d){
                $ret['patient'][$date]++;
                if (isset($_d['退院']) && $_d['退院']){
                    $ret['discharge'][$date]++;
                }
            }
            // printf("%s %s\t=> %s\t%s\n", $com_id, $date, $ret['patient'][$date], $ret['discharge'][$date]);
        }
        
        // 3月9日までは毎日取れてないので削除
        foreach ($ret as &$_r){
            $_r = array_filter($_r, function($date){
                return !in_array($date, ['2020/3/1','2020/3/3', '2020/3/4', '2020/3/6']);
            }, ARRAY_FILTER_USE_KEY );
        }
        
        return $ret;
    }

    /**
     * @brief 東京都のgitリポジトリからcovid19/data/data.jsonを取得
     * @param 
     * @retval array
     */
    private function getDataDataJson()
    {
        return json_decode(
            file_get_contents($this->git_dir . '/data/data.json'), true);
    }
}
