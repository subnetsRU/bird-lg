<?
/*

    BIRD Looking Glass :: Version: 0.3.2
    Home page: http://bird-lg.subnets.ru/
    =====================================
    Copyright (c) 2013 SUBNETS.RU project (Moscow, Russia)
    Authors: Nikolaev Dmitry <virus@subnets.ru>, Panfilov Alexey <lehis@subnets.ru>

*/

$pathinfo = pathinfo($argv[0]);
$config=$pathinfo['dirname']."/bird.lg.config.php";
if (is_file($config)){
    if (is_readable($config)){
	if (!@include $config){
	    print "[ERROR]: CONFIG not found\n";
	    exit(0);
	}
    }else{
	print "[ERROR]: CONFIG file not readable\n";
	exit(0);
    }
}else{
    print "[ERROR]: CONFIG file don`t exists\n";
    exit(0);
}

if (!isset($config['bird_client_dir'])&&$config['bird_client_dir']){
    print "Config error: check bird client dir";
    exit(0);
}

date_default_timezone_set($config['timezone']);

$debug=0;	//0 - off, 1 - print to log (don`t forget to create & chown log file), 2 - print to log + console
$script_dir=$config['bird_client_dir'];
$log_dir=sprintf("%s",$script_dir);
$script_name=sprintf("%s",isset($config['bird_client_file'])&&$config['bird_client_file']?$config['bird_client_file']:"bird.client");
$params_in=$argv;
array_shift($params_in);

$permitted_commands=array("show","ping","ping6","trace","trace6");

$bird_success_codes=array(
    '0000' => "OK",
    '0002' => "Reading configuration",
    '0003' => "Reconfigured",
    '0004' => "Reconfiguration in progress",
    '0005' => "Reconfiguration already in progress, queueing",
    '0006' => "Reconfiguration ignored, shutting down",
    '0007' => "Shutdown ordered",
    '0008' => "Already disabled",
    '0009' => "Disabled",
    '0010' => "Already enabled",
    '0011' => "Enabled",
    '0012' => "Restarted",
    '0013' => "Status report",
    '0014' => "Route count",
    '0015' => "Reloading",
    '0016' => "Access restricted"
);

$bird_error_codes = array(
    '8000' => "Reply too long",
    '8001' => "Route not found",
    '8002' => "Configuration file error",
    '8003' => "No protocols match",
    '8004' => "Stopped due to reconfiguration",
    '8005' => "Protocol is down => cannot dump",
    '8006' => "Reload failed",
    '8007' => "Access denied",

    '9000' => "Command too long",
    '9001' => "Parse error",
    '9002' => "Invalid symbol type",
);

$bird_end_codes=$bird_error_codes + $bird_success_codes;

if ($debug){
    $mkdir=0;
    if (@is_dir($log_dir)){
        $flogfile=sprintf('%s/%s_%s.log',$log_dir,date("Y-m-d",time()),$script_name);
        if (!@file_exists($flogfile)){
            @exec("/usr/bin/touch $flogfile");
        }
        if (@is_writable($flogfile)){
            $logfile = @fopen($flogfile, 'a');
        }
    }
}

if (is_array($params_in)){
    if (count($params_in)==0){
	usage($argv);
    }else{
        logg("++++++++++++ got params +++++++++++++++");
        logg(print_r($params_in, true));

	$command="";
	if (preg_match("/^-c$/",$params_in[0])){
	    unset($params_in[0]);
	    $command=implode(" ",$params_in);
	}
        if ($command){
    	    logg($command);
        }else{
	    usage($argv);
        }
    }
}else{
    usage($argv);
}


$error=array();
$bird_data="";

$protocol="ipv4";
if (preg_match("/^(ipv4|ipv6):\s/",$command,$tmp)){
    $protocol=$tmp[1];
    $command=preg_replace("/^(ipv4|ipv6):\s/","",$command);
}

$query_type="";
if (is_array($permitted_commands)){
    unset($tmp);
    preg_match("/^(\S+)\s/",$command,$tmp);
    $query_type=$tmp[1];
    if (!in_array($tmp[1],$permitted_commands)){
	$error[]=sprintf("%s is prohibited",$query_type);
    }
}else{
    $error[]="All commands are prohibited";
}

if ($query_type){
    if (preg_match("/^(ping|ping6)$/",$query_type) || preg_match("/^(trace|trace6)$/",$query_type)){
	//
    }else{
	if (is_array($config)){
	    if (isset($config['birdc'])||isset($config['birdc6'])){
		$socket_path=$config['birdc'];
		if ($protocol=="ipv6"){
		    $socket_path=$config['birdc6'];
		}
		if (!@file_exists($socket_path)){
		    $error[]="Socket not found";
		}
	    }else{
		$error[]="Config error: check socket path";
	    }
	}else{
	    $error[]="Config error: check config file";
	}
    }
}else{
    $error[]="Can`t get query type";
}
if (count($error)==0){
    if (preg_match("/^(ping|ping6)$/",$query_type) || preg_match("/^(trace|trace6)$/",$query_type)){
	if (preg_match("/^(ping|ping6)$/",$query_type)){
	    $ping_res=ping($config,$command,$protocol);
	    if ($ping_res['error']){
		$error[]=$ping_res['error'];
	    }else{
		$bird_data=$ping_res['data'];
	    }
	}elseif(preg_match("/^(trace|trace6)$/",$query_type)){
	    $trace_res=trace($config,$command,$protocol);
	    if ($trace_res['error']){
		$error[]=$trace_res['error'];
	    }else{
		$bird_data=$trace_res['data'];
	    }
	}
    }else{
	$sock = @stream_socket_client(sprintf('unix:///%s',$socket_path), $errno, $errstr, 30,STREAM_CLIENT_CONNECT);
	if ($sock&&$errno==0){
		@fwrite($sock, $command."\r\n");
		$nn=0;
		while (($buf = @fgets($sock, 4096)) !== false) {
		    $buffer=trim($buf, "\t\r\0\x0B" );
		    if (($nn==0&&!preg_match("/^0001\sBIRD\s(\S+)\sready\./",$buffer))){
			$error[]="Welcome string unknown";
			break;
		    }
		    if ($config['suppress_welcome']){
			if (preg_match("/^0001\sBIRD\s(\S+)\sready\./",$buffer)){
			    $buffer="";
			}
		    }
		    $code=substr($buffer,0,4);
		    if ($buffer){
			if (preg_match("/^[0-9]{4}(-){0,1}/",$buffer,$tmp)){
			    $buffer=substr($buffer,sprintf("%d",isset($tmp[1])?5:4));
			}
			if ($buffer){
			    $bird_data.=sprintf("%s",$buffer);
			}
			if (array_key_exists($code,$bird_end_codes)){
			    break;
			}
		    }
		    $nn++;
		}
		$bird_data = preg_replace( "/ *\n */m", "\n", $bird_data );
		$bird_data = preg_replace( "/\n+$/", "", $bird_data );
		@fclose($sock);
	}else{
	    $error[]=sprintf("Socket connection error %s (%s)",$errno,$errstr);
	}
    }
}

if (count($error)==0){
    logg($bird_data);
    if ($debug<2){print $bird_data."\r\n";}
}else{
    $err_txt="BIRD client errors:\n";
    foreach ($error as $key => $val){
	$err_txt.=sprintf("\t%d: %s\n",$key+1,$val);
    }
    logg($err_txt);
    if ($debug<2){print $err_txt."\r\n";}
}
exit(0);

function usage($argv){
    printf("\nUsage: php %s -c [ipv4|ipv6]: <command>\n",$argv[0]);
    exit(0);
}

function ping($config,$command,$protocol="ipv4"){
    $ret=array();
    $ret['error']="";
    $host="";
    $ping_util=isset($config['ping_util']['path'])?$config['ping_util']['path']:"";
    $ping_util_flags=isset($config['ping_util']['flags'])?$config['ping_util']['flags']:"";
    if (preg_match("/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/",$command,$tmp)){
	$host=$tmp[1];
    }
    if ($protocol=="ipv6"){
	$ping_util=isset($config['ping6_util']['path'])?$config['ping6_util']['path']:"";
	$ping_util_flags=isset($config['ping6_util']['flags'])?$config['ping6_util']['flags']:"";
	if (preg_match("/\s((([0-9a-fA-F]{1,4}\:){1,7}\:)|((([0-9a-fA-F]{1,4}\:){1,7})|(([0-9a-fA-F]{1,4}\:){1,6}\:)[0-9a-fA-F]{1,4}))[\S\s]{0,}$/",$command,$tmp)){
	    $host=$tmp[0];
	}
    }
    if ($ping_util&&is_file($ping_util)){
	if ($host){
	    @exec(sprintf("%s %s %s",$ping_util,$ping_util_flags,$host),$out,$res);
	    if ($res==0||$res==2){
		if (is_array($out)){
		    $ret['data']="";
		    foreach ($out as $k => $v){
			$ret['data'].=sprintf("%s\n",$v);
		    }
		}else{
		    $ret['error']="Ping util: no data";
		}
	    }else{
		$ret['error']=sprintf("Ping util: return error %s",$res);
	    }
	}else{
	    $ret['error']="Ping util: IP-address is invalid";
	}
    }else{
	$ret['error']="Config error: check path to ping util";
    }
 return $ret;
}

function trace($config,$command,$protocol="ipv4"){
    $ret=array();
    $ret['error']="";
    $host="";
    $trace_util=isset($config['trace_util']['path'])?$config['trace_util']['path']:"";
    $trace_util_flags=isset($config['trace_util']['flags'])?$config['trace_util']['flags']:"";
    if (preg_match("/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/",$command,$tmp)){
	$host=$tmp[1];
    }
    if ($protocol=="ipv6"){
	$trace_util=isset($config['trace6_util']['path'])?$config['trace6_util']['path']:"";
	$trace_util_flags=isset($config['trace6_util']['flags'])?$config['trace6_util']['flags']:"";
	if (preg_match("/\s((([0-9a-fA-F]{1,4}\:){1,7}\:)|((([0-9a-fA-F]{1,4}\:){1,7})|(([0-9a-fA-F]{1,4}\:){1,6}\:)[0-9a-fA-F]{1,4}))[\S\s]{0,}$/",$command,$tmp)){
	    $host=$tmp[0];
	}
    }
    if ($trace_util&&is_file($trace_util)){
	if ($host){
	    @exec(sprintf("%s %s %s",$trace_util,$trace_util_flags,$host),$out,$res);
	    if ($res==0){
		if (is_array($out)){
		    $ret['data']="";
		    foreach ($out as $k => $v){
			$ret['data'].=sprintf("%s\n",$v);
		    }
		}else{
		    $ret['error']="Trace util: no data";
		}
	    }else{
		$ret['error']=sprintf("Trace util: return error %s",$res);
	    }
	}else{
	    $ret['error']="Trace util: IP-address is invalid";
	}
    }else{
	$ret['error']="Config error: check path to Trace util";
    }
 return $ret;
}

function logg($text){
    global $debug,$logfile;
    if ($debug>0){
        if (@is_resource($logfile)){
            @fputs($logfile,sprintf("[%s]: %s\n",date("Y-m-d H:i:s"),$text));
        }
    }
    if ($debug>1){
        print "$text\n";
    }
}

?>