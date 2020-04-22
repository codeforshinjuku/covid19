<?php
namespace Code4Shinjuku;

class Util
{
    
    /**
     * @brief system()の代替
     * @param コマンド
     * @param 入力
     * @retval
     */
    public static function system($cmd, $stdin = "") 
    {

        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => tmpfile(),
            2 => array("pipe", "w")
            );

        $process = proc_open($cmd, $descriptorspec, $pipes);
        $result_message = "";
        $error_message = "";
        $return = null;
        if (is_resource($process)) 
        {
            fputs($pipes[0], $stdin);
            fclose($pipes[0]);

            while ($error = fgets($pipes[2])){
                $error_message .= $error;
            }
            fseek($descriptorspec[1], 0);
            while ($result = fgets($descriptorspec[1])){
                $result_message .= $result;
            }
            foreach ($pipes as $k=>$_rs){
                if (is_resource($_rs)){
                    fclose($_rs);
                }
            }
            $return = proc_close($process);
        }
        
        if ($return !== 0){
            throw new \Exception("Error in system command.\n => $cmd \n" . $error_message);
        }
        return array(
            'return' => $return,
            'stdout' => $result_message,
            'stderr' => $error_message,
            );
    }
    
    /**
     * @brief ファイルサイズを良い感じにする
     * @param ファイルサイズ(byte)
     * @retval
     */
    public static function number_format_filesize($string) 
    {
        $size = intval($string);
        $unit = "Byte";

        $units = array(
            1             => "B",
            1000          => "kB",
            1000000       => "MB",
            1000000000    => "GB",
            1000000000000 => "TB",
            );

        foreach ($units as $key=>$value){
            if ($size/$key<1000){
                $s = sprintf("%f", $size/$key);
                return trim(substr($s, 0, 4),".") . $value;
                break;
            }
        }

        return $string;
    }
    
}
