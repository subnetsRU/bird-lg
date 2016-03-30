<?
/*

    BIRD Looking Glass :: Version: 0.4.2
    Home page: http://bird-lg.subnets.ru/
    =====================================
    Copyright (c) 2013-2014 SUBNETS.RU project (Moscow, Russia)
    Authors: Nikolaev Dmitry <virus@subnets.ru>, Panfilov Alexey <lehis@subnets.ru>

*/

////////////////////// Functions file ////////////////////////////
define('LG_VERSION',"0.4.2");
session_start();
date_default_timezone_set($config['timezone']);
error_reporting(E_ALL);
//set_error_handler("exception_error_handler");

$pathinfo = realpath( dirname(__FILE__) );
$config_file=$pathinfo."/bird.lg.config.php";
if (is_file($config_file)){
    if (is_readable($config_file)){
	if (!@include $config_file){
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

$config['protocols']=array("Direct","Kernel","Device","Static","Pipe","BGP","OSPF","RIP","RAdv","bfd");

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
		    $enabled=1;
		    if (isset($val['disabled'])){
			if( config_val($config['query'],$name,"disabled") ){
			    $enabled=0;
			}
		    }
		    if ($enabled){
			if (!restricted($config['restricted'],isset($val['restricted'])?$val['restricted']:"")){
			    $placeholder="";
			    if (isset($val['placeholder'])){
				if ($val['placeholder']){
				    $placeholder=$val['placeholder'];
				}
			    }
			    $ret['data'][]=sprintf("<input type=\"radio\" id=\"query\" name=\"query\" onclick=\"additional('%s');\" value=\"%s\"%s>%s",$placeholder,$name,isset($sp['query'])&&$sp['query']==$name?" checked":"",$val['name']);
			}
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

function error($txt){
    $ret=sprintf("<span class=\"error_text\">[ERROR]: %s</span>",$txt);;
 return $ret;
}

function main_form($config){
    $sp=array();
    if (isset($_SESSION['param'])&&is_array($_SESSION['param'])){
	$sp=$_SESSION['param'];
    }

    print "<div id=\"main_form\" align=\"center\">
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
	printf("<span class=\"errorMsg\">ERROR: %s</span>",$query_list['error']);
    }else{
	if (is_array($query_list['data'])){
	    foreach ($query_list['data'] as $query){
		printf("<tr><td>%s</td></tr>",$query);
	    }
	}else{
	    $query_list['data']=array();
	}
    }

    if ($config['ipv6_enabled'] && !$query_list['error'] && count($query_list['data']) > 0 ){
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
    
    if (!$query_list['error'] && count($query_list['data']) == 0 ){
	print "<tr>
		<td><font color=\"red\"><i>empty, check LG config file</i></font></td>
	</tr>";
    }
    print "</TABLE>";
    print "</TD>";
    print "<TD ALIGN=\"CENTER\">&nbsp;<BR>";
    if (!$query_list['error'] && count($query_list['data']) > 0 ){
	printf("<INPUT NAME=\"additional\" ID=\"additional\" SIZE=\"40\" VALUE=\"%s\"><BR>
	    <FONT SIZE=\"-1\">&nbsp;<SUP>&nbsp;</SUP>&nbsp;</FONT></TD>",isset($sp['additional'])?$sp['additional']:"");
    }
    print "<TD ALIGN=\"RIGHT\">&nbsp;<BR>";

    $routers_list=routers_list($config,$sp);
    if ($routers_list['error']){
	printf("<span class=\"errorMsg\">ERROR: %s</span>",$routers_list['error']);
    }else{
	if (!$query_list['error'] && count($query_list['data']) > 0 ){
	    print "<SELECT ID=\"router\" NAME=\"router\">";
	    print $routers_list['data'];
	    print "</SELECT>";
	}else{
	    print "&nbsp;";
	}
    }
    print "<BR>";
    print "<FONT SIZE=\"-1\">&nbsp;&nbsp;<SUP>&nbsp;</SUP>&nbsp;</FONT></TD>";
    print "</TR>";
    print "<TR>";
    if ( !$query_list['error'] && !$routers_list['error'] && count($query_list['data']) > 0 ){
	print "<TD ALIGN=\"CENTER\" COLSPAN=3><button class=\"but\" onclick=\"request('1');\">Send request</button></TD>";
    }
    print "</TR>";
    print "</TABLE>";
    print "</TD></TR></TABLE>";
    print "</div>";
}

function check_form_params($p=array(),$config=array()){
    $ret=array();
    $ret['error']="";
    $_SESSION['param']=$p;
    if (is_array($p)){
	if (!array_key_exists($p['query'],$config['query'])){
	    $ret['error']=error("Query type unknown");
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

	if (!config_val($config['query'],$p['query'],"additional_empty")){
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

function process_query($p=array(),$config=array()){
    //deb($p);
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
	$p['cmd']="";
	$addon="";
	
	if (isset($config['query'][$p['query']]['restricted'])){
	    if (restricted($config['restricted'],$config['query'][$p['query']]['restricted']?1:0)){
		$ret['error']=sprintf("%s",error("Command execution is not permitted"));
		return $ret;
	    }
	}
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
		    $p['cmd']=sprintf("show route %s%s",$p['additional'],$addon);
	    }else{
		    $p['cmd']=sprintf("show route for %s%s",$p['additional'],$addon);
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
	        $p['cmd']=sprintf("ping%s %s",$p['protocol']=="IPv6"?6:"",$p['additional']);
	    }elseif($p['query']=="trace"){
	        $p['cmd']=sprintf("trace%s %s",$p['protocol']=="IPv6"?6:"",$p['additional']);
	    }
	}elseif ($p['query']=="protocols"){
	    if ($p['additional']){
		if (!preg_match("/^[a-zA-Z0-9\_\s]+$/",$p['additional'])){
		    $ret['error']=error(sprintf("Check protocol name in additional parameters: %s",$p['additional']));
		    return $ret;
		}
	    }
	    $p['cmd']=sprintf("show protocols %s%s",$p['additional'],$addon);
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
	    $p['cmd']=sprintf("show route export %s%s",$p['additional'],$addon);
	}elseif ($p['query']=="bgp_summ"){
	    $p['additional']="";
	    $p['cmd']="show protocols";
	    $p['fake_cmd']="bgp summary";
	}elseif ($p['query']=="nei_route_accepted"){
	    $p['additional']="";
	    $p['cmd']=sprintf("show route protocol %s all",$p['peer']);
	    $p['fake_cmd']=sprintf("show %s routes",isset($p['nei'])?$p['nei']:"");
	}elseif ($p['query']=="nei_route_best"){
	    $p['additional']="";
	    $p['cmd']=sprintf("show route protocol %s primary all",$p['peer']);
	    $p['fake_cmd']=sprintf("show %s routes best",$p['nei']);
	}elseif ($p['query']=="nei_route_filtered"){
	    $p['additional']="";
	    $p['cmd']=sprintf("show route protocol %s filtered all",$p['peer']);
	    $p['fake_cmd']=sprintf("show %s routes filtered",$p['nei']);
	}elseif ($p['query']=="nei_route_export"){
	    $p['additional']="";
	    $p['cmd']=sprintf("show route export %s all",$p['peer']);
	    $p['fake_cmd']=sprintf("show %s export routes",$p['nei']);
	}elseif ($p['query']=="bfd_sessions"){
	    $p['cmd']="show bfd sessions";
	}elseif ($p['query']=="ospf_summ"){
	    $p['cmd']="show ospf neighbors";
	}
	
	$p['router_name']=sprintf("%s %s",$config['nodes'][$p['router']]['name'],$config['nodes'][$p['router']]['description']?sprintf(" (%s)",$config['nodes'][$p['router']]['description']):"");
	printf("<b>Router: %s</b><BR>",$p['router_name']);
	printf("<b>Command: %s</b><BR>",isset($p['fake_cmd'])&&$p['fake_cmd']?$p['fake_cmd']:$p['cmd']);
	print "<br>";
	if ($p['query']=="bgp_summ"){
	    $ret=bgp_summ($p,$config);
	}else{
	    $ret=bird_send_cmd($p,$config);
	}
    }
 return $ret;
}

function bgp_summ($p=array(),$config=array()){
    $ret=array();
    $ret['error']="";
    $data=array();

    $ret=bird_send_cmd($p,$config);
    //deb($ret);
    if ($ret['error']){
	return $ret;
    }else{
	$tmp=explode("\n",$ret['data']);
	$ret['data']="";
	if (count($tmp)>0){
	    $bgp_peers=array();
	    $nn=0;
	    foreach($tmp as $v){
		if ($v){
		    if (preg_match("/^([a-zA-Z0-9\_]+)\s+BGP\s+\S+\s+\S+\s+(\S+)/i",$v,$m)){
			//deb($m);
			$bgp_peers[$nn]['name']=$m[1];
			$bgp_peers[$nn]['date']=$m[2];
			$nn++;
		    }
		}
	    }
	    //deb($bgp_peers);
	    $total_peers=count($bgp_peers);
	    if ($total_peers>0){
		$hide_protocol=config_val($config['output'],"hide","protocol");
		$data=sprintf("<b>Total number of BGP neighbors: %d</b>\n",$total_peers);
		$data.="BGP neighbors Up: %TOTAL_UP% <font color=\"red\">Down:  %TOTAL_DOWN%</font>\n";
		$data.="<b>Number of unique ASN: %TOTAL_UNIQ%</b>\n";
		$data.="\n";
		//$data.="<style>td{padding-left: 5px; paddinf-right:5px;}</style>\n";
		$data.="<table width=\"100%\" cellspacing=\"2\" cellpadding=\"5\" border=\"0\">";
		$data.="<tr>";
		$data.="<th class=\"head\">#</th>";
		if (!$hide_protocol){
		    $data.="<th class=\"head\">Peer name</th>\n";
		}
		$data.="<th class=\"head\">Neighbor</th>";
		$data.="<th class=\"head\">ASN</th>";
		$data.="<th class=\"head\">Date</th>";
		$data.="<th class=\"head\">State</th>";
		$data.="<th class=\"head\">Accepted / Best / Filtered</th>";
		$data.="<th class=\"head\">Exported</th>";
		//$data.="<th class=\"head\">ErrCode</th>\n";
		$data.="</tr>\n";
		$data.="<tr>\n";

		$asn_uniq=0;
		$asn=array();
		$total_up=0;
		foreach ($bgp_peers as $k => $peer){
		    $p['peer']=$peer['name'];
		    $neighbor=bgp_neighbor_details($p,$config);
		    $nei=$neighbor['data'];
		    //deb($nei);
		    $state_up=0;
		    if (strtolower($nei['state'])=="established"){
			$state_up=1;
		    }

		    $data.=sprintf("<tr%s>\n",$state_up?"":" style=\"color:red;\"");
		    $data.=sprintf("<td>%d</td>",$k+1);
		    if (!$hide_protocol){
			$data.=sprintf("<td><b>%s</b></td>",$peer['name']);
		    }
		    if ($neighbor['error']){
			$data.=sprintf("<td colspan=6><font color=\"darkred\"><i>can`t get neighbor%s info</i></font></td>\n",$hide_protocol?"":sprintf(" <b>%s</b>",$peer['name']));
		    }else{
			if (config_val($config['output'],"hide","bgp_peer_det_link")){
			    $data.=sprintf("<td>%s</td>",$nei['addr']);
			}else{
			    $data.=sprintf("<td><a style=\"cursor: pointer;\" onclick=\"subrequest('query=bgp_det&router=%s&protocol=%s&peer=%s&back=bgp_summ');\"><u>%s</u></a></td>",$p['router'],$p['protocol'],md5($peer['name']),$nei['addr']);
			}
			if (isset($config['asn_url'])&&$config['asn_url']){
			    $data.=sprintf("<td align=\"center\"><a href=\"%s\" target=\"_blank\">%s</td>",preg_replace("/%ASNUMBER%/",$nei['asn'],$config['asn_url']),$nei['asn']);
			}else{
			    $data.=sprintf("<td align=\"center\">%s</td>",$nei['asn']);
			}
			if (preg_match("/^(\d{4})\-(\d{2})\-(\d{2})$/",$peer['date'],$m)){
			    $peer['date']=sprintf("%02d.%02d.%04d",$m[3],$m[2],$m[1]);
			}
			$data.=sprintf("<td align=\"center\">%s</td>",$peer['date']);
			$data.=sprintf("<td align=\"center\">%s</td>",$nei['state']);
			if ($state_up){
			    $data.="<td align=\"center\">";
			    if (config_val($config['output'],"hide","bgp_accepted_routes_link")){
				$data.=sprintf("%s",$nei['accepted']);
			    }else{
				$data.=sprintf("<a style=\"cursor: pointer;\" onclick=\"subrequest('query=nei_route_accepted&router=%s&protocol=%s&peer=%s&nei=%s&back=bgp_summ');\"><u>%d</u></a>",$p['router'],$p['protocol'],md5($peer['name']),$nei['addr'],$nei['accepted']);
			    }
			    $data.="&nbsp;/&nbsp;";
			    if (config_val($config['output'],"hide","bgp_best_routes_link")){
				$data.=sprintf("%s",$nei['best']);
			    }else{
				$data.=sprintf("<a style=\"cursor: pointer;\" onclick=\"subrequest('query=nei_route_best&router=%s&protocol=%s&peer=%s&nei=%s&back=bgp_summ');\"><u>%d</u></a>",$p['router'],$p['protocol'],md5($peer['name']),$nei['addr'],$nei['best']);
			    }
			    $data.="&nbsp;/&nbsp;";
			    if (config_val($config['output'],"hide","bgp_filtered_routes_link")){
				$data.=sprintf("%s",$nei['filtered']);
			    }else{
				$data.=sprintf("<a style=\"cursor: pointer;\" onclick=\"subrequest('query=nei_route_filtered&router=%s&protocol=%s&peer=%s&nei=%s&back=bgp_summ');\"><u>%d</u></a>",$p['router'],$p['protocol'],md5($peer['name']),$nei['addr'],$nei['filtered']);
			    }
			    $data.="</td>";
			    if (config_val($config['output'],"hide","bgp_export_routes_link")){
				$data.=sprintf("<td align=\"center\">%s</td>",$nei['exported']);
			    }else{
				$data.=sprintf("<td align=\"center\"><a style=\"cursor: pointer;\" onclick=\"subrequest('query=nei_route_export&router=%s&protocol=%s&peer=%s&nei=%s&back=bgp_summ');\"><u>%d</u></a></td>",$p['router'],$p['protocol'],md5($peer['name']),$nei['addr'],$nei['exported']);
			    }
			}else{
			    $data.="<td>&nbsp;</td>";
			}
			
			if(!in_array($nei['asn'],$asn)){
			    $asn[]=$nei['asn'];
			    $asn_uniq++;
			}
		    }
		    $data.="</tr>";
		    if ($state_up){
			$total_up++;
		    }
		}
		$data.="</table>\n";
		$ret['data']=preg_replace("/%TOTAL_UP%/",$total_up,$data);
		$ret['data']=preg_replace("/%TOTAL_DOWN%/",sprintf("%d",$total_peers-$total_up),$ret['data']);
		$ret['data']=preg_replace("/%TOTAL_UNIQ%/",$asn_uniq,$ret['data']);
	    }else{
		$ret['error']=sprintf("%s",error("BGP peers not found"));
	    }
	}else{
	    $ret['error']=sprintf("%s",error("Get summary failed"));
	}
    }
 return $ret;
}

function bgp_neighbor_details($p=array(),$config=array()){
    $ret=array();
    $ret['error']="";
    $ret['data']=array();

    unset($p['cmd']);
    $p['cmd']=sprintf("show protocols all %s",$p['peer']);
    $show=bird_send_cmd($p,$config);
    if ($show['error']){
	$ret['error']=$show['error'];
    }else{
	$tmp=explode("\n",$show['data']);
	if (count($tmp)>0){
		//deb($tmp);
		foreach ($tmp as $k => $v){
		    if ($v){
			if (preg_match("/^Neighbor\saddress:(.*)$/",$v,$m)){
			    $ret['data']['addr']=trim($m[1]);
			}elseif (preg_match("/^Neighbor\sAS:\s+(\d+)$/",$v,$m)){
			    $ret['data']['asn']=trim($m[1]);
			}elseif (preg_match("/^BGP\sstate:\s+(\S+)$/",$v,$m)){
			    $ret['data']['state']=trim($m[1]);
			}elseif (preg_match("/^Routes:\s+(\d+)/",$v)){
			    $routes=routes_count($v);
			    //deb($routes);
			    foreach ($routes as $key => $val){
				$ret['data'][$key]=$val;
			    }
			}
		    }
		}
	}

	if (isset($p['full_info']) && $p['full_info']){
	    if (preg_match("/Routes:\s+\d+/",$show['data'])){
		if (!config_val($config['output'],"hide","bgp_accepted_routes_link")){
		    $lnk=sprintf("<a style=\"cursor: pointer;\" onclick=\"subrequest('query=nei_route_accepted&router=%s&protocol=%s&peer=%s&nei=%s&back=bgp_det');\"><u><b>%d</b></u></a>",$p['router'],$p['protocol'],md5($p['peer']),$ret['data']['addr'],$ret['data']['accepted']);
		    $show['data']=preg_replace("/\d+\simported/",sprintf("%s imported",$lnk),$show['data']);
		}
		if (!config_val($config['output'],"hide","bgp_best_routes_link")){
		    $lnk=sprintf("<a style=\"cursor: pointer;\" onclick=\"subrequest('query=nei_route_best&router=%s&protocol=%s&peer=%s&nei=%s&back=bgp_det');\"><u><b>%d</b></u></a>",$p['router'],$p['protocol'],md5($p['peer']),$ret['data']['addr'],$ret['data']['best']);
		    $show['data']=preg_replace("/\d+\spreferred/",sprintf("%s preferred",$lnk),$show['data']);
		}
		if (!config_val($config['output'],"hide","bgp_export_routes_link")){
		    $lnk=sprintf("<a style=\"cursor: pointer;\" onclick=\"subrequest('query=nei_route_export&router=%s&protocol=%s&peer=%s&nei=%s&back=bgp_det');\"><u><b>%d</b></u></a>",$p['router'],$p['protocol'],md5($p['peer']),$ret['data']['addr'],$ret['data']['exported']);
		    $show['data']=preg_replace("/\d+\sexported/",sprintf("%s exported",$lnk),$show['data']);
		}
		if (!config_val($config['output'],"hide","bgp_filtered_routes_link")){
		    $lnk=sprintf("<a style=\"cursor: pointer;\" onclick=\"subrequest('query=nei_route_filtered&router=%s&protocol=%s&peer=%s&nei=%s&back=bgp_det');\"><u><b>%d</b></u></a>",$p['router'],$p['protocol'],md5($p['peer']),$ret['data']['addr'],$ret['data']['filtered']);
		    $show['data']=preg_replace("/\d+\sfiltered/",sprintf("%s filtered",$lnk),$show['data']);
		}
	    }
	    return $show;
	}
    }
 return $ret;
}

function bird_send_cmd($p=array(),$config=array()){
    $ret=array();
    $ret['error']="";
    $put_2_log=array();
    if ($p['cmd']){
	    $p['cmd']=sprintf("%s: %s",strtolower($p['protocol']),$p['cmd']);
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
	    $put_2_log[]=sprintf("Router: %s",$p['router_name']);
	    $put_2_log[]=sprintf("Command: %s",$p['cmd']);
	    if (isset($config['log_query_result'])&&$config['log_query_result']){
		$put_2_log[]=sprintf("Result:\n%s",$ret['error']?$ret['error']:$ret['data']);
	    }
	    logging($config,$put_2_log);
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

function parse_bird_data($data,$query,$config,$p){
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
	    $tot_paths=intval(substr_count($data,"via "));
	    $tot_paths+=intval(substr_count($data,"dev "));
	    $tot_paths+=intval(substr_count($data,"blackhole "));
	    $ret=sprintf("Total routes: %d\n",$tot_paths);
	    foreach ($data_str as $k => $str){
		if (config_val($config['output'],"hide","protocol")){
		    if (preg_match("/\s\[[a-zA-Z0-9\_]+\s\S+\]\s/",$str)){
			$str=preg_replace("/\s\[[a-zA-Z0-9\_]+\s(\S+)\]\s/"," [\$1] ",$str);
		    }elseif (preg_match("/\s\[[a-zA-Z0-9\_]+\s\S+\s+from\s+\S+\]\s/",$str)){
			$str=preg_replace("/\s\[[a-zA-Z0-9\_]+\s(\S+\s+from\s+\S+)\]\s/"," [\$1\$2] ",$str);
		    }
		}
		if (config_val($config['output'],"hide","iface")){
		    if (preg_match("/\son\s\S+\s\[/",$str)){
			$str=preg_replace("/\son\s\S+\s/"," ",$str);
		    }
		    if (preg_match("/dev\s[a-zA-Z]+\d{0,5}\s+\[/",$str)){
			$str=preg_replace("/dev\s[a-zA-Z]+\d{0,5}\s+\[/","via <i>hidden</i> [",$str);
		    }
		}
		if (config_val($config['output'],"modify","routes")){
		    $str=trim($str);
		    $str=preg_replace("/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(\/\d{1,3}){0,1}\s+/","\nRouting table entry for <b>\$1\$2</b>\n",$str);
		    $str=preg_replace("/^([0-9a-fA-F:]+)(\/\d{1,3}){0,1}\s+/","\nRouting table entry for <b>\$1\$2</b>\n",$str);
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

		if ( config_val($config['output'],"modify","own_community") || config_val($config['output'],"modify","routes") ){
		    if (preg_match("/community:/i",$str)){
			$str=preg_replace("/\((\d{1,5}),(\d{1,5})\)/","\$1:\$2",$str);
			if (config_val($config['output'],"modify","own_community")){
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
	$ret="";
	$proto=$config['protocols'];
	if (is_array($proto)){
		if (count($proto)==1){
		    $protos=$proto[0];
		}else{
		    $protos=sprintf("(%s)",implode("|",$proto));
		}
	}
	$tmp=explode("\n",$data);
	foreach ($tmp as $str){
		if (config_val($config['output'],"modify","protocols") || config_val($config['output'],"hide","protocol")){
		    if (preg_match(sprintf("/\s+%s\s+/i",$protos),$str)){
			if (preg_match("/^\S+\s+\S+\s+\S+\s+(down)/",$str)){
			    if (config_val($config['output'],"hide","protocol")){
				$str=preg_replace("/^[a-zA-Z0-9\_]+\s/","<i>hidden</i>",$str);
			    }
			    $ret.=preg_replace("/^(.*)$/","\n<b><font size=\"+1\" color=\"red\">\$1</font></b>",$str);
			}else{
			    if (config_val($config['output'],"hide","protocol")){
				$str=preg_replace("/^[a-zA-Z0-9\_]+\s/","<i>hidden</i>",$str);
			    }
			    $ret.=preg_replace("/^(.*)/","\n<font size=\"+1\"><b>\$1</b></font>",$str);
			}
			$ret.="\n";
		    }else{
			$ret.=sprintf("%s\n",trim($str));
		    }
		}else{
		    $ret.=sprintf("%s\n",trim($str));
		}
	}
    }elseif ($query=="bfd_sessions"){
	$ret="<pre>$data</pre>";
	$hide_iface=config_val($config['output'],"hide","iface");
	$hide_proto=config_val($config['output'],"hide","protocol");
	$additional="";
	if (isset($p['additional'])){
	    if ($p['additional']){
		$additional=trim($p['additional']);
	    }
	}

	if ($hide_iface || $hide_proto || $additional){
	    $ret="<pre>";
	    $tmp=explode("\n",$data);
	    foreach ($tmp as $str){
		if ($hide_proto){
		    if (preg_match("/^\S+:$/",$str)){
			$str="";
		    }
		}
		if ($hide_iface){
		    $str=preg_replace("/Interface/","",$str);
		    if (preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\s+\S+\s/",$str)){
			$str=preg_replace("/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\s+)(\S+)\s/","\$1",$str);
		    }elseif(preg_match("/([0-9a-fA-F:]+)(\/\d{1,3}){0,1}\s+\S+\s/",$str)){
			$str=preg_replace("/(([0-9a-fA-F:]+)(\/\d{1,3}){0,1}\s+)(\S+)\s/","\$1",$str);
		    }
		}
		
		if ($additional){
		    if (preg_match("/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\s+\S+\s/",$str,$m)){
			if ($m[1] != $additional){
			    $str="";
			}
		    }elseif(preg_match("/([0-9a-fA-F:]+)(\/\d{1,3}){0,1}\s+\S+\s/",$str,$m)){
			if ($m[1] != $additional){
			    $str="";
			}
		    }
		}

		if ($str){
		    $ret.=sprintf("%s\n",trim($str));
		}
	    }
	    $ret.="<pre>";
	}
    }elseif($query=="ospf_summ"){
	$ret="<pre>$data</pre>";
	$additional="";
	if (isset($p['additional'])){
	    if ($p['additional']){
		$additional=trim($p['additional']);
	    }
	}

	if ($additional){
	    $ret="<pre>";
	    $tmp=explode("\n",$data);
	    foreach ($tmp as $str){
		if ($additional){
		    if (preg_match("/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\s+/",$str,$m)){
			if ($m[1] != $additional){
			    $str="";
			}
		    }elseif(preg_match("/([0-9a-fA-F:]+)(\/\d{1,3}){0,1}\s+/",$str,$m)){
			if ($m[1] != $additional){
			    $str="";
			}
		    }
		}

		if ($str){
		    $ret.=sprintf("%s\n",trim($str));
		}
	    }
	    $ret .= "<pre>";
	}
    }
 return $ret;
}

function bgp_peer_search($p=array(),$config=array()){
    $ret=array();
    $ret['error']="";
    $ret['data']="";
    $peer="";
    if (!$p['protocol']){$p['protocol']="IPv4";}

    if ($p['peer']&&$p['router']){
	$p['cmd']="show protocols";
	$result=bird_send_cmd($p,$config);
	//deb($result);
	if ($result['error']){
		$ret['error']=sprintf("%s",$result['error']);
	}else{
		$tmp=explode("\n",$result['data']);
		foreach ($tmp as $k => $v){
			if (preg_match(sprintf("/^([a-zA-Z0-9\_]+)\s+(%s)\s+/",implode("|",$config['protocols'])),$v,$m)){
			    if (md5($m[1])==$p['peer']){
				$peer=$m[1];
			    }
			}
		}
	}
    }else{
	$ret['error']=sprintf("%s",error("Can`t find protocol, params is missing"));
    }

    if ($peer){
	$ret['data']=$peer;
    }else{
	$ret['error']=sprintf("%s",error("Peer not found"));
    }
 return $ret;
}

function config_val($config,$key,$val=""){
    //default
    if ($key=="hide"){
	$ret=1;
    }else{
	$ret=0;
    }
    //
    
    if ($val){
	if (isset($config[$key][$val])){
	    if (is_bool($config[$key][$val])){
		if ($key=="hide"){
		    if ($config[$key][$val]===false){
			$ret=0;
		    }
		}else{
		    if ($config[$key][$val]===true){
			$ret=1;
		    }
		}
	    }else{
		if ($config[$key][$val]=="restricted"){
		    $restricted=restricted("",1);
		    if ($restricted==0){
			if ($key=="hide"){
			    $ret=0;
			}else{
			    $ret=1;
			}
		    }
		}
	    }
	}
    }else{
	if (isset($config[$key])){
	    if (is_bool($config[$key])){
		if ($config[$key]===true){
		    $ret=1;
		}elseif ($config[$key]===false){
		    $ret=0;
		}
	    }
	}
    }
 return $ret;
}

function restricted($ips,$restricted){
    $ret=1;
    if ($restricted){
	if (!is_array($ips)){
	    global $config;
	    $ips=$config['restricted'];
	}
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

function check_lg_version($check_version="0"){
    $lg_host="bird-lg.subnets.ru";
    $lg_port="80";
    $get=sprintf("/check_version.php?version=%s",LG_VERSION);
    if (!defined('LG_VERSION')){
	define('LG_VERSION','N/A');
    }

    $ret=array();
    $ret['error']="";
    $ret['url']=sprintf("http://%s/",$lg_host);
    $ret['version']=LG_VERSION;

    if ($check_version){
	if (isset($_SESSION['check_version']) ){
	    $ret['version']=$_SESSION['check_version'];
	}else{
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
			$ret['version']=$_SESSION['check_version']=$tmp[1];
		    }
		}
	    }
	}
    }
 return $ret;
}

function deb($text){
    if (is_restricted()){
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
}

function is_restricted(){
    global $config;
    $ret=0;
    if (PHP_SAPI === 'cli'){
	$ret=1;
    }else{
	if (isset($config['restricted'])){
	    if (is_array($config['restricted'])){
		foreach ($config['restricted'] as $ip){
		    if (isset($_SERVER['REMOTE_ADDR'])){
			if ($_SERVER['REMOTE_ADDR'] == $ip){
			    $ret=1;
			    break;
			}
		    }
		}
	    }
	}
    }
 return $ret;
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

function show_buttons($buttons,$pos="0"){
    if (is_array($buttons)){
	if ($pos){
	    print "<BR><BR>";
	}
	foreach ($buttons as $but){
	    print $but."&nbsp;";
	}
	if (!$pos){
	    print "<BR><BR>";
	}
    }
}

function routes_count($str){
    $ret=array();
    if (preg_match("/(\d+)\s+imported/",$str,$m)){
	$ret['accepted']=trim($m[1]);
    }
    if (preg_match("/(\d+)\s+exported/",$str,$m)){
	$ret['exported']=trim($m[1]);
    }
    if (preg_match("/(\d+)\s+preferred/",$str,$m)){
	$ret['best']=trim($m[1]);
    }
    if (preg_match("/(\d+)\s+filtered/",$str,$m)){
	$ret['filtered']=trim($m[1]);
    }
 return $ret;
}

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    if (is_restricted()){
	print "<pre>";
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
	print "</pre>";
    }
}

?>
