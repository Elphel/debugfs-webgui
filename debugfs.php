<?php
/*
FILE NAME  : debugfs.php
DESCRIPTION: dynamic debug backend
REVISION: 1.00
AUTHOR: Oleg Dzhimiev <oleg@elphel.com>
LICENSE: AGPL, see http://www.gnu.org/licenses/agpl.txt
Copyright (C) 2016 Elphel, Inc.
*/

//globals
$config = "debugfs.json";
$tmp_config = "/tmp/$config";

$DEBUGFSFILE = "/sys/kernel/debug/dynamic_debug/control";

if (isset($_GET['cmd']))
    $cmd = $_GET['cmd'];
else
    $cmd = "do_nothing";

function control_records_sort($a,$b){

    $ad1 = strpos($a,":");
    $ad2 = strpos($a,"[");
    
    $afile = substr($a,0,$ad1);
    $aline = (int)substr($a,$ad1+1,($ad2-1)-$ad1-1);
    
    $bd1 = strpos($b,":");
    $bd2 = strpos($b,"[");
    
    $bfile = substr($b,0,$bd1);
    $bline = (int)substr($b,$bd1+1,($bd2-1)-$bd1-1);
    
    if ($afile==$bfile){
        if ($aline==$bline){
            return 0;
        }
        return($aline<$bline)?-1:1;
    }
    
    return ($afile<$bfile)?-1:1;
}
    
function get_control($f){
    $res = Array();
    $results = trim(file_get_contents($f));
    //print("<pre>");
    $ress = explode("\n",$results);
    //filename - find first ":"
    //lineno - between ":" and " "
    //then [module] inside brackets
    //function from "]" to " "

    usort($ress,"control_records_sort");
    
    $oldfile = "";
    
    foreach($ress as $line){
    
        if ($line[0]=="#") continue;
    
        $d0 = 0;
        $d1 = strpos($line,":");
        $d2 = strpos($line,"[");
        $d3 = strpos($line,"]");
        preg_match("/=[flmpt_]+/",$line,$matches,PREG_OFFSET_CAPTURE);
        $d4 = $matches[0][1];
        $d5 = strpos($line,"\"");
    
        $subarr = Array();
        $subarr['file']     = substr($line,0,$d1);
        $subarr['lineno']   = substr($line,$d1+1,($d2-1)-$d1-1);
        $subarr['module']   = substr($line,$d2+1,($d3-1)-$d2);
        $subarr['function'] = substr($line,$d3+1,($d4-1)-$d3-1);
        $subarr['flags']    = substr($line,$d4+1,1);
        $subarr['format']   = substr($line,$d5+1,-1);
    
        if ($subarr['file']!=$oldfile){
            //echo "processing ".$subarr['file']."\n";
            if ($oldfile!="") array_push($res,$sub);
            $oldfile = $subarr['file'];
            $sub = Array(
                "file" => $subarr['file'],
                "state" => 0,
                "configs" => Array(
                    Array(
                        "name" => "default",
                        "state" => 1,
                        "lines" => Array()
                    )
                )
            );
        }
        array_push($sub['configs'][0]['lines'],$subarr);
    }
    //last
    array_push($res,$sub);
        
    return $res;
}

function update_config($data){
    global $tmp_config;
    // debugfs.json
    file_put_contents($tmp_config,$data);
}

function apply_config_to_control(){
    global $tmp_config, $DEBUGFSFILE;
    $longstring = "";
    $arr_config = json_decode(file_get_contents($tmp_config),true);
    foreach($arr_config as $v0){
        if ($v0['state']==1){
            foreach($v0['configs'] as $v1){
                if ($v1['state']==1){
                    foreach($v1['lines'] as $v2){
                        $file = $v2['file'];
                        $lineno = $v2['lineno'];
                        $flag = $v2['flags'];
                        if ($flag=="p") $sign = "+";
                        else            $sign = "-";
                        $newstring = "file $file line $lineno ${sign}p;";
                        //there's a limit
                        if (strlen($longstring.$newstring)>4095){
                            exec("echo -n '$longstring' > $DEBUGFSFILE");
                            $longstring = $newstring;
                        }else{
                            $longstring .= $newstring;
                        }
                        //echo "echo -n 'file $file line $lineno ${sign}p'\n";
                    }
                }
            }
        }
    }
    exec("echo -n '$longstring' > $DEBUGFSFILE");
    echo "Done";
}

function apply_flag($flag){
    global $tmp_config, $DEBUGFSFILE;
    $longstring = "";
    $arr_config = json_decode(file_get_contents($tmp_config),true);
    foreach($arr_config as $v0){
        if ($v0['state']==1){
            foreach($v0['configs'] as $v1){
                if ($v1['state']==1){
                    $newstring = "file ".$v0['file']." $flag;";
                    if (strlen($longstring.$newstring)>4095){
                        exec("echo -n '$longstring' > $DEBUGFSFILE");
                        $longstring = $newstring;
                    }else{
                        $longstring .= $newstring;
                    }
                }
            }
        }
    }
    exec("echo -n '$longstring' > $DEBUGFSFILE");
    echo "Done";
    
}

function sync_from_debugfs_to_config($config_index,$file,$line,$flags,$sign){
    global $tmp_config;

    //$arr_debugfs = get_control("/sys/kernel/debug/dynamic_debug/control");
    $arr_config = json_decode(file_get_contents($tmp_config),true);
    
    $err = 0;
    $dc = 0; $dcc = 0; $dccc = 0;
    
    foreach($arr_config as $k => $v){
        if ($v['file']==$file) {
            $dc = $k;
            $err = $err + 1;
            break;
        }
    }
    
    $tmp_arr1 = $arr_config[$dc]['configs'];
    
    foreach($tmp_arr1 as $k => $v){
        if ($v['state']==1) {
            $dcc = $k;
            $err = $err + 2;
            break;
        }
    }
    
    $tmp_arr2 = $arr_config[$dc]['configs'][$dcc]['lines'];
    
    foreach($tmp_arr2 as $k => $v){
        if ($v['lineno']==$line) {
            $dccc = $k;
            $err = $err + 4;
            break;
        }
    }
    
    if ($sign=="+") $flag = "p";
    else            $flag = "_";
    
    echo "file index: $dc, config index: $dcc, line index: $dccc \n";
    
    if ($err==7){
        $arr_config[$dc]['configs'][$dcc]['lines'][$dccc]['flags'] = $flag;   
        update_config(json_encode($arr_config));
    }else{
        echo "error code: $err";
    }
}

function filter_record_by_file($a,$f){
    $res = Array();
    foreach($a as $k=>$v){
        if ($v['file']==$f){
            $res = $v;
            break;
        }
    }
    return $res;
}

if ($cmd=="do_nothing"){
    if (isset($_GET['file'])) $file = $_GET['file'];
    else                      $file = $DEBUGFSFILE;
    
    //echo json_encode(get_control($file));
    //echo "<pre>";
    
    if (!is_file($tmp_config)) {
        if (is_file($config)) {
            copy($config,$tmp_config);
            $json_data = file_get_contents($config);
            echo $json_data;
        }else{
            $arr = get_control($file);
            //print_r($arr);
            update_config(json_encode($arr));
            echo json_encode($arr);
        }
        //echo "debugfs.json was missing, refresh page\n";
    }else{
        $json_data = file_get_contents($tmp_config);
        echo $json_data;
        //print_r(json_decode($json_data));
    }
}

if ($cmd=="echo") {
    $file = $_GET['file'];
    $line = $_GET['line'];
    $flags = $_GET['flags'];
    $config_index = intval($_GET['conf']);
    //$config name
    
    if (strpos($flags,"p")===FALSE){
        $sign = "-p";
    }else{
        $sign = "+";
    }
    exec("echo -n 'file $file line $line ${sign}${flags}' > $DEBUGFSFILE");
    sync_from_debugfs_to_config($config_index,$file,$line,$flags,$sign);
}

$debugfs_configs = "debugfs_configs";

if ($cmd=="save"){
    $file = $_GET['file'];
    if (!is_dir($debugfs_configs)) mkdir($debugfs_configs);
    file_put_contents("$debugfs_configs/$file", file_get_contents($DEBUGFSFILE));
}

if ($cmd=="sync"){
    //list saved configs here
    $file = $_GET['file'];
    $data = file_get_contents("php://input");
    update_config($data);
    apply_config_to_control();
}

if ($cmd=="savetofs"){
    copy($tmp_config,$config);
}

if ($cmd=="restore"){
    apply_config_to_control();
}

if ($cmd=="reread"){
    $file = $_GET['file'];
    $arr = get_control($DEBUGFSFILE);
    $filtered = filter_record_by_file($arr,$file);
    echo json_encode($filtered);
    //echo "<pre>";print_r($filtered);
}

if ($cmd=="setflag"){
    $flag = $_GET['flag'];
    apply_flag($flag);
}

//single line: echo -n 'file gamma_tables.c +p' > /sys/kernel/debug/dynamic_debug/control

?>
