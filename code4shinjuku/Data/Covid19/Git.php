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
     * @brief 患者の区別JSONデータを表示する
     * @param 
     * @retval
     */
    public function patient($city, $citylist)
    {
        $data = $this->getPatientData();
        if ($citylist){
            return $this->getPatientCityList($data);
        }
        if ($city) {
            if (!isset($data[$city])){
                throw new \Exception($city . 'という市区町村コードのデータはありません。');
            }
            return $data[$city]['patients'];
        }
        $patients = [];
        foreach ($data as $citycode => $d){
            $patients[$citycode] = $d['patients'];
        }
        
        return $patients;
    }
    
    
    
    /**
     * @brief 市区町村の一覧を取得
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
                    $_city = $_data['code']; // 'label'に表記ゆれ(市区町村の有無)があるのでcode
                    if (!isset($data[$_city])) {
                        $data[$_city] = [
                            'city'     => $_data,
                            'patients' => [],
                            ];
                        unset($data[$_city]['city']['count']);
                    }
                    $data[$_city]['patients'][$_date] = $_data['count'];
                }
            }
        }
        $data = array_filter($data, function($d){
            return $d['city']['code'] > 100000;
        });
        return $data;
    }
}
