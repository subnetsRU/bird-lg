<?
/*
    BIRD Looking Glass :: Version: 0.2.0
    Home page: http://bird-lg.subnets.ru/
    =====================================
    Copyright (c) 2013 SUBNETS.RU project (Moscow, Russia)
    Authors: Nikolaev Dmitry <virus@subnets.ru>, Panfilov Alexey <lehis@subnets.ru>

    Functions file
*/

$config="bird.lg.config.php";
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

required_for_run();

session_start();
date_default_timezone_set($config['timezone']);
define('LG_VERSION',"0.2.0");

function head($title="LG"){
        printf ("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
        <html>
	<!--[if lt IE 9]><script src=\"http://html5shiv.googlecode.com/svn/trunk/html5.js\"></script><![endif]-->
        <title>%s</title>
        <head>
        <META HTTP-EQUIV=\"Content-Type\" content=\"text/html;charset=UTF-8\">
	<META HTTP-EQUIV=\"PRAGMA\" CONTENT=\"NO-CACHE\">
	<META HTTP-EQUIV=\"CACHE-CONTROL\" CONTENT=\"NO-CACHE\">
	<META HTTP-EQUIV=\"expires\" content=\"Mon, 01 Jan 1990 00:00:00 GMT\">
	<META HTTP-EQUIV=\"Content-language\" CONTENT=\"en\">
	<META NAME=\"author\" CONTENT=\"Subnets.ru :: www.subnets.ru\">
	<link rel=\"stylesheet\" type=\"text/css\" href=\"css/style.css\">
	<script type=\"text/javascript\" src=\"js/mt145.js\"></script>
        </head>\n",$title);
	print "<body>\n";
}

function foot(){
	print "\n</body>\n</html>\n";
}

function logging($config,$txt=""){
    $ret=array();
    $ret['error']="";
    if (isset($config['log_query'])&&$config['log_query']){
	if (isset($config['log_query_file'])){
	    if (@is_file($config['log_query_file'])){
		if (@is_writable($config['log_query_file'])){
		    $logfile = @fopen($config['log_query_file'], 'a');
		    if (@is_resource($logfile)){
		        @fputs($logfile,sprintf("[%s]: %s\n",date("Y-m-d H:i:s"),REMOTE_ADDR));
		        if (is_array($txt)){
			    if (count($txt)>0){
				foreach ($txt as $k => $v){
					@fputs($logfile,sprintf("\t%s\n",$v));
				}
			    }
		        }else{
			    @fputs($logfile,sprintf("\t%s\n",$txt));
			}
		    }else{
			$ret['error']=sprintf("<BR>%s",error("Log writing error"));
		    }
		    @fclose($logfile);
		}else{
		    $ret['error']=sprintf("<BR>%s",error("Log file not writable, check permissions"));
		}
	    }else{
		$ret['error']=sprintf("<BR>%s",error("Log file does not exist"));
	    }
	}else{
	    $ret['error']=sprintf("<BR>%s",error("Config error: Log queries is enabled but no log file specified"));
	}
    }
 return $ret;
}

function routers_list($config,$sp=array()){
    $nodes=$config['nodes'];
    $ret=array();
    $ret['error']="";
    $ret['data']="";
    if (is_array($nodes)){
	if (count($nodes)>0){
	    foreach ($nodes as $i => $val ){
		$ret['data'].=sprintf("<option value=\"%d\"%s>%s (%s)</option>",$i,isset($sp['router'])&&$sp['router']==$i?" selected":"",$val['name'],$val['description']);
	    }
	}else{
	    $ret['error']=sprintf("%s",error("No nodes"));
	}
    }else{
	$ret['error']=sprintf("%s",error("Config error: no nodes"));
    }
 return $ret;
}

function query_list($config,$sp=array()){
    $query=$config['query'];
    $ret=array();
    $ret['error']="";
    $ret['data']=array();
    if (is_array($query)){
	if (count($query)>0){
	    foreach ($query as $name => $val ){
		if (count($val)>0){
		    if (!restricted($config['restricted'],isset($val['restricted'])?$val['restricted']:"")){
			$ret['data'][]=sprintf("<input type=\"radio\" id=\"query\" name=\"query\" value=\"%s\"%s>%s",$name,isset($sp['query'])&&$sp['query']==$name?" checked":"",$val['name']);
		    }
		}
	    }
	}else{
	    $ret['error']=sprintf("%s",error("No query types"));
	}
    }else{
	$ret['error']=sprintf("%s",error("Config error: no query types"));
    }
 return $ret;
}

function restricted($ips,$restricted){
    $ret=1;
    if ($restricted){
	if (is_array($ips)){
	    foreach ($ips as $ip){
		if ($ip==REMOTE_ADDR){
		    $ret=0;
		    break;
		}
	    }
	}
    }else{
	$ret=0;
    }
 return $ret;
}

function error($txt){
    $ret=sprintf("<span class=\"error_text\">[ERROR]: %s</span>",$txt);;
 return $ret;
}

function main_form($config){
    $sp=array();
    if (isset($_SESSION['param'])&&is_array($_SESSION['param'])){
	$sp=$_SESSION['param'];
    }

    print "<CENTER>
	<TABLE BORDER=0 class=\"tbl\"><TR><TD>
	<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2>
	<TR>
	<TH class=\"head\">Type of Query</TH>
	<TH class=\"head\">Additional parameters</th>
	<TH class=\"head\">Node</TH>
	</TR>
	<TR><TD>
	<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2>";
    $query_list=query_list($config,$sp);
    if ($query_list['error']){
	print $query_list['error'];
    }else{
	if (is_array($query_list['data'])){
	    foreach ($query_list['data'] as $query){
		printf("<tr><td>%s</td></tr>",$query);
	    }
	}
    }
    if ($config['ipv6_enabled']){
	if (!isset($sp['protocol'])){$sp['protocol']="IPv4";}
	printf("<TR>
		    <TD>
			<SELECT ID=\"protocol\" NAME=\"protocol\">
			    <OPTION VALUE=\"IPv4\"%s> IPv4
			    <OPTION VALUE=\"IPv6\"%s> IPv6
			</SELECT>
		    </TD>
	    </TR>",$sp['protocol']=="IPv4"?" selected":"",$sp['protocol']=="IPv6"?" selected":"");
    }
    print "</TABLE>";
    print "</TD>";
    printf("<TD ALIGN=\"CENTER\">&nbsp;<BR><INPUT NAME=\"additional\" ID=\"additional\" SIZE=\"30\" VALUE=\"%s\"><BR><FONT SIZE=\"-1\">&nbsp;<SUP>&nbsp;</SUP>&nbsp;</FONT></TD>",isset($sp['additional'])?$sp['additional']:"");
    print "<TD ALIGN=\"RIGHT\">&nbsp;<BR>";

    $routers_list=routers_list($config,$sp);
    if ($routers_list['error']){
	print $routers_list['error'];
    }else{
	print "<SELECT ID=\"router\" NAME=\"router\">";
	print $routers_list['data'];
	print "</SELECT>";
    }
    print "<BR>";
    print "<FONT SIZE=\"-1\">&nbsp;&nbsp;<SUP>&nbsp;</SUP>&nbsp;</FONT></TD>";
    print "</TR>";
    print "<TR>";
    print "<TD ALIGN=\"CENTER\" COLSPAN=3><button class=\"but\" onclick=\"request('1');\">Send request</button></TD>";
    print "</TR>";
    print "</TABLE>";
    print "</TD></TR></TABLE>";
    print "</CENTER>";
}

function check_form_params($p=array(),$config=array()){
    $ret=array();
    $ret['error']="";
    $_SESSION['param']=$p;
    if (is_array($p)){
	if (!array_key_exists($p['query'],$config['query'])){
	    $ret['error']=error("Unknown query type");
	    return $ret;
	}
	if (!isset($config['nodes'][$p['router']])){
	    $ret['error']=error("Unknown node");
	    return $ret;
	}
	if ($config['ipv6_enabled']){
	    if (!isset($p['protocol'])){
		$ret['error']=error("You must choose protocol: IPv4 or IPv6");
		return $ret;
	    }
	}else{
	    $p['protocol']="IPv4";
	}

	if (!isset($p['protocol'])){
	    $ret['error']=error("Unknown protocol");
	    return $ret;
	}

	if (!config_val($config['query'][$p['query']],"additional_empty")){
	    if (!isset($p['additional'])){
		$ret['error']=error("You must enter Additional parameters");
		return $ret;
	    }
	    if (!$p['additional']){
		$ret['error']=error("Additional parameters are empty");
		return $ret;
	    }

	}
    }else{
	$ret['error']=sprintf("%s",error("No params in request"));
    }
 return $ret;
}

function check_ip_input($p){
    $ret=array();
    $ret['error']="";
    if (is_array($p)){
	if ($p['protocol']=="IPv4"){
		if (!preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d{1,3}){0,1}$/",$p['additional'])){
		    $ret['error']=error(sprintf("Additional %s parameters are wrong: %s",$p['protocol'],$p['additional']));
		}
	}elseif($p['protocol']=="IPv6"){
		//$regexp="/^((([0-9a-fA-F]{1,4}\:){1,7}\:)|((([0-9a-fA-F]{1,4}\:){1,7})|(([0-9a-fA-F]{1,4}\:){1,6}\:)[0-9a-fA-F]{1,4})(\/\d{1,3}){0,1})$/";
		$regexp="/^[0-9a-fA-F:]+(\/\d{1,3}){0,1}$/";
		if (!preg_match($regexp,$p['additional'],$tmp)){
		    $ret['error']=error(sprintf("Additional %s parameters are wrong: %s",$p['protocol'],$p['additional']));
		}
	}
    }else{
	$ret['error']=error("Can`t check IP or NET");
    }
 return $ret;
}

function bird_send_cmd($p=array(),$config=array()){
    $ret=array();
    $ret['error']="";
    $put_2_log=array();

    if (!is_array($p)){
	$ret['error']=sprintf("%s",error("Params missing"));
	return $ret;
    }
    if (!is_array($config)){
	$ret['error']=sprintf("%s",error("Config is missing"));
	return $ret;
    }
    if (!isset($config['nodes'][$p['router']])){
	$ret['error']=sprintf("%s",error("Node not found"));
	return $ret;
    }

    if ($p['query']){
	$cmd="";
	$addon="";

	if ( $p['query']=="route" || $p['query']=="ping" || $p['query']=="trace" ){
	    if ($p['protocol']=="IPv4"){
		$check_ip=check_ip_input($p);
		if ($check_ip['error']){
		    $ret['error']=$check_ip['error'];
		    return $ret;
		}
	    }elseif($p['protocol']=="IPv6"){
		$check_ip=check_ip_input($p);
		if ($check_ip['error']){
		    $ret['error']=$check_ip['error'];
		    return $ret;
		}
	    }
	}

	if (isset($config['query'][$p['query']]['addon'])){
	    if ($config['query'][$p['query']]['addon']){
		$addon=sprintf(" %s",$config['query'][$p['query']]['addon']);
	    }
	}
	if ($p['query']=="route"){
	    if (preg_match("/\/\d{1,3}$/",$p['additional'])){
		    $cmd=sprintf("show route %s%s",$p['additional'],$addon);
	    }else{
		    $cmd=sprintf("show route for %s%s",$p['additional'],$addon);
	    }
	}elseif ( $p['query']=="ping" || $p['query']=="trace" ){
	    if (($p['protocol']=="IPv4"&&$p['additional']=="0.0.0.0") || ($p['protocol']=="IPv6"&&$p['additional']=="::/0")){
		$ret['error']=error(sprintf("You must enter host, not default gateway: %s",$p['additional']));
		return $ret;
	    }
	    if (preg_match("/\/\d{1,3}$/",$p['additional'])){
		$ret['error']=error(sprintf("You must enter single host in additional parameters, network is given: %s",$p['additional']));
		return $ret;
	    }
	    if ($p['query']=="ping"){
	        $cmd=sprintf("ping%s %s",$p['protocol']=="IPv6"?6:"",$p['additional']);
	    }elseif($p['query']=="trace"){
	        $cmd=sprintf("trace%s %s",$p['protocol']=="IPv6"?6:"",$p['additional']);
	    }
	}elseif ($p['query']=="protocols"){
	    if ($p['additional']){
		if (!preg_match("/^[a-zA-Z0-9\_\s]+$/",$p['additional'])){
		    $ret['error']=error(sprintf("Check protocol name in additional parameters: %s",$p['additional']));
		    return $ret;
		}
	    }
	    $cmd=sprintf("show protocols %s%s",$p['additional'],$addon);
	}elseif ($p['query']=="export"){
	    if ($p['additional']){
		if (!preg_match("/^[a-zA-Z0-9\_\s]+$/",$p['additional'])){
		    $ret['error']=error(sprintf("Check protocol name in additional parameters: %s",$p['additional']));
		    return $ret;
		}
	    }else{
		$ret['error']=error("You must enter protocol name in additional parameters");
		return $ret;
	    }
	    $cmd=sprintf("show route export %s%s",$p['additional'],$addon);
	}

	$router_name=sprintf("%s %s",$config['nodes'][$p['router']]['name'],$config['nodes'][$p['router']]['description']?sprintf(" (%s)",$config['nodes'][$p['router']]['description']):"");
	printf("<b>Router: %s</b><BR>",$router_name);
	printf("<b>Command: %s</b><BR>",$cmd);
	print "<br>";

	if ($cmd){
	    $p['cmd']=sprintf("%s: %s",strtolower($p['protocol']),$cmd);
	    $conn=connect_2_bird($p,$config);
	    if ($conn['error']){
		$ret['error']=$conn['error'];
	    }else{
		if (preg_match("/^BIRD\sclient\serrors/",$conn['data'])){
		    $ret['error']=error($conn['data']);
		}else{
		    $ret['data']=$conn['data'];
		}
	    }
	}else{
	    $ret['error']=sprintf("%s",error("Can`t get command"));
	}
	
	if (isset($config['log_query'])&&$config['log_query']){
	    $put_2_log[]=sprintf("Router: %s",$router_name);
	    $put_2_log[]=sprintf("Command: %s",$cmd);
	    if (isset($config['log_query_result'])&&$config['log_query_result']){
		$put_2_log[]=sprintf("Result:\n%s",$ret['error']?$ret['error']:$ret['data']);
	    }
	    logging($config,$put_2_log);
	}
    }
 return $ret;
}

function connect_2_bird($p,$config){
    $ret=array();
    $ret['error']="";
    $ret['data']="";

    if (!is_array($config)){
	$ret['error']=sprintf("%s",error("Config is missing"));
	return $ret;
    }
    $router=$config['nodes'][$p['router']];

    if ($router['host']=="socket"){
	if (!preg_match("/^(ipv4|ipv6):\s(ping|ping6)/",$p['cmd']) && !preg_match("/^(ipv4|ipv6):\s(trace|trace6)/",$p['cmd'])){
	    $socket=$config['birdc'];
	    if ($p['protocol']=="IPv6"){
		$socket=$config['birdc6'];
	    }
	    if ($socket){
		if (@file_exists($socket)){
		    if (!is_writable($socket)){
			$ret['error']=sprintf("%s",error("Check socket permissions, see README for details"));
		    }
		}else{
		    $ret['error']=sprintf("%s",error("Socket not found"));
		}
	    }else{
		$ret['error']=sprintf("%s",error("Config error: check socket path"));
	    }
	}
	if (isset($config['bird_client_dir'])&&$config['bird_client_dir']){
	    if (isset($config['bird_client_file'])&&$config['bird_client_file']){
		$bird_client=sprintf("%s/%s",$config['bird_client_dir'],$config['bird_client_file']);
		if (@file_exists($bird_client)){
		    if (!@is_readable($bird_client)){
			$ret['error']=sprintf("%s",error("BIRD client script is not readable"));
		    }
		}else{
		    $ret['error']=sprintf("%s",error("BIRD client script not found"));
		}
	    }else{
		$ret['error']=sprintf("%s",error("Config error: check bird client script name"));
	    }
	}else{
		$ret['error']=sprintf("%s",error("Config error: check bird client dir"));
	}
    }

    if ($ret['error']){
	return $ret;
    }

    if ($router['host']=="socket"){
	    @exec(sprintf("%s %s/%s -c %s",$config['php_path'],$config['bird_client_dir'],$config['bird_client_file'],$p['cmd']),$out,$res);
	    if (is_array($out)){
		//deb($out);
		foreach ($out as $k => $v){
		    $ret['data'].=sprintf("%s\n",$v);
		}
	    }else{
		$ret['data']=$out;
	    }
    }else{
	if (!preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/",$router['host'])){
	    $ret['error']=error(sprintf("Host address %s is invalid",$router['host']));
	    return $ret;
	}
	if (!preg_match("/^\d{1,5}$/",$router['port'])){
	    $ret['error']=error(sprintf("Port %s is invalid",$router['port']));
	    return $ret;
	}
	
	$fp = @stream_socket_client(sprintf("tcp://%s:%d",$router['host'],$router['port']), $errno, $errstr, 10, STREAM_CLIENT_CONNECT);
	if ($fp){
	    $data="";
	    @fwrite($fp, sprintf("%s;\r\n\r\n",$p['cmd']));
	    while (!@feof($fp)) {
		$tmp=sprintf("%s",@fgets($fp, 1024));
		if ($tmp){
		    $data.=$tmp;
		}
	    }
	    @fclose($fp);
	    if (strlen($data)>0){
		//deb($data);
		$ret['data']=$data;
	    }else{
		$ret['error']=error("No data recieved");
	    }
	}else{
	    $ret['error']=error(sprintf("%s:%d connection error %s (%s)",$router['host'],$router['port'],$errno,$errstr));
	}
    }
 return $ret;
}

function parse_bird_data($data,$query,$config){
    $ret=$data;
    if ($query=="route" || $query=="export"){
	$ret="";
	$replaces=array(
	    "BGP.origin"=>"Origin",
	    "BGP.as_path"=>"AS-PATH",
	    "BGP.next_hop"=>"Next-hop",
	    "BGP.local_pref"=>"Local preference",
	    "BGP.atomic_aggr"=>"Atomic aggregate",
	    "BGP.aggregator:"=>"Aggregated by",
	    "BGP.community"=>"Community",
	    "BGP.med"=>"MED",
	    "OSPF.metric1"=>"Metric1",
	    "OSPF.metric2"=>"Metric2",
	    "OSPF.tag"=>"Tag",
	    "OSPF.router_id"=>"Router ID",
	);

	if (is_array($config)){
	    $data_str=explode("\n",$data);
	    //deb($data_str);
	    if ($query=="export"){
		$tot_paths=1;
	    }else{
		$tot_paths=intval(substr_count($data,"via "));
		$tot_paths+=intval(substr_count($data,"dev "));
		$tot_paths+=intval(substr_count($data,"blackhole "));
	    }
	    foreach ($data_str as $k => $str){
		if (config_val($config['output']['hide'],"protocol")){
		    if (preg_match("/\s\[[a-zA-Z0-9\_]+\s\S+\]\s/",$str)){
			$str=preg_replace("/\s\[[a-zA-Z0-9\_]+\s(\S+)\]\s/"," [\$1] ",$str);
		    }
		}
		if (config_val($config['output']['hide'],"iface")){
		    if (preg_match("/\son\s\S+\s\[/",$str)){
			$str=preg_replace("/\son\s\S+\s/"," ",$str);
		    }
		}
		if (config_val($config['output']['modify'],"routes")){
		    $str=trim($str);
		    $str=preg_replace("/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(\/\d{1,3}){0,1}\s+/",sprintf("\nRouting table entry for <b>\$1\$2</b>\nPaths available: %d\n",$tot_paths),$str);
		    $str=preg_replace("/^([0-9a-fA-F:]+)(\/\d{1,3}){0,1}\s+/",sprintf("\nRouting table entry for <b>\$1\$2</b>\nPaths available: %d\n",$tot_paths),$str);
		    $str=preg_replace("/(via|dev|blackhole)\s(.*)\s\*\s(.*)$/","<font color=red>\$1 \$2 \$3 <b>best</b></font>",$str);
		    $str=preg_replace("/via\s/","\nvia ",$str);
		    $str=preg_replace("/dev\s/","\nvia ",$str);
		    $str=preg_replace("/blackhole\s/","\nblackhole ",$str);
		    $str=preg_replace("/Type:\s+/","\tType: ",$str);
		    $str=preg_replace("/^(BGP.as_path:)$/","\$1 <i>empty</i>",$str);
		    $str=preg_replace("/^(BGP.atomic_aggr:)$/","\$1 <i>empty</i>",$str);
		    if(is_array($replaces)){
			foreach ($replaces as $what=>$to){
			    $str=preg_replace(sprintf("/%s/",$what),sprintf("\t%s",$to),$str);
			}
		    }
		}

		if ( config_val($config['output']['modify'],"own_community") || config_val($config['output']['modify'],"routes") ){
		    if (preg_match("/community:/i",$str)){
			$str=preg_replace("/\((\d{1,5}),(\d{1,5})\)/","\$1:\$2",$str);
			if (config_val($config['output']['modify'],"own_community")){
			    if (is_array($config['own_community'])){
				$comm_str=explode(" ",$str);
				array_shift($comm_str);
				foreach ($comm_str as $comm){
				    $comm_tmp=explode(":",$comm);
				    if ($comm_tmp[0]==$config['asn']){
					if (isset($config['own_community'][$comm_tmp[1]])){
					    $str=preg_replace(sprintf("/(%s)/",$comm),sprintf("\$1 <font size=\"-2\"><i>(%s)</i></font>",$config['own_community'][$comm_tmp[1]]),$str);
					}
				    }
				}
			    }
			}
		    }
		}
		$ret.=sprintf("%s\n",$str);
	    }
	}
    }elseif ($query=="protocols"){
	if (config_val($config['output']['modify'],"protocols")){
	    $ret="";
	    $proto=array("Direct","Kernel","Device","Static","Pipe","BGP","OSPF","RIP","RAdv","bfd");

	    if (is_array($proto)){
		if (count($proto)==1){
		    $protos=$proto[0];
		}else{
		    $protos=sprintf("(%s)",implode("|",$proto));
		}
	    }
	    $tmp=explode("\n",$data);
	    foreach ($tmp as $str){
		if (preg_match(sprintf("/\s+%s\s+/i",$protos),$str)){
		    if (preg_match("/^\S+\s+\S+\s+\S+\s+(down)/",$str)){
			$ret.=preg_replace("/^(.*)$/","\n<b><font size=\"+1\" color=\"red\">\$1</font></b>",$str);
		    }else{
			$ret.=preg_replace("/^(.*)/","\n<font size=\"+1\"><b>\$1</b></font>",$str);
		    }
		    $ret.="\n";
		}else{
		    $ret.=sprintf("%s\n",trim($str));
		}
	    }
	}
    }
 return $ret;
}

function config_val($config,$val){
    $ret=0;
    if (isset($config[$val])){
	if ($config[$val]){
	    $ret=1;
	}
    }
 return $ret;
}

function check_lg_version($check_version="0"){
    $ret=array();
    $ret['error']="";
    if ($check_version&&defined('LG_VERSION')){
	$ret['version']="";
	$lg_host="bird-lg.subnets.ru";
	$lg_port="80";
	$ret['url']=sprintf("http://%s/",$lg_host);
	$get=sprintf("/check_version.php?version=%s",LG_VERSION);
	$sock = @stream_socket_client(sprintf("tcp://%s:%d",$lg_host,$lg_port), $errno, $errstr, 5, STREAM_CLIENT_CONNECT);
	if ($sock&&$errno==0){
	    $data='';
	    @fwrite($sock, sprintf("GET %s HTTP/1.0\r\nHost: %s\r\nUser-Agent: LG CHECKER v0.1\r\nAccept: */*\r\n\r\n",$get,$lg_host));
	    while (!@feof($sock)) {
		$data.=@fgets($sock, 1024);
	    }
	    @fclose($sock);
	}
	if ($data){
	    $tmp=explode("\r\n\r\n",$data);
	    if ($tmp[1]){
		if (preg_match("/^(\d{1,3})(\.\d{1,2}){0,2}$/", $tmp[1])){
		    $ret['version']=$tmp[1];
		}
	    }
	}
    }
 return $ret;
}

function deb($text){
	$notcli=1;
	if (PHP_SAPI === 'cli'){
	    $notcli=0;
	}
	if ($notcli){print "<pre>";}
	print "[DEBUG] ";
	if (is_array($text)){
	    foreach ($text as $k=>$v){
		if (is_array($v)){
		    printf("<b>[%s] => array</b>\n",$k);
		    print_r($v);
		}else{
		    printf("[%s] => %s\n",$k,$v);
		}
	    }
	}else{
	    printf ("%s",$text);
	}
	if ($notcli){print "</pre>";}
}

function required_for_run(){
    $func_list=array("stream_socket_client","exec","preg_match","session_start");
    foreach ($func_list as $func){
	if (!function_exists($func)) {
	    printf ("[ERROR]: PHP function <b>%s</b> is not available.",$func);
	    exit(0);
	}
    }

    if (!REMOTE_ADDR){
	print "[ERROR]: REMOTE_ADDR addrees is unknown, check config";
	exit(0);
    }
}

?>