<?
/*

    BIRD Looking Glass :: http://bird-lg.subnets.ru/
    =================================================
    Copyright (c) 2013 SUBNETS.RU project (Moscow, Russia)
    Authors: Nikolaev Dmitry <virus@subnets.ru>, Panfilov Alexey <lehis@subnets.ru>

    Configuration file

*/
$config=array();

//========================================================================================
//				   	GLOBAL CONFIG SECTION
//========================================================================================
/*
    Global
    ======
    timezone: http://php.net/manual/en/function.date-default-timezone-get.php , default Europe/Moscow
    php_path: full path to the PHP CLI version, details http://php.net/manual/en/features.commandline.introduction.php
*/
$config['timezone']="Europe/Moscow";
$config['php_path']="/usr/local/bin/php";

/*
    BIRD sockets
    ==================================
    Full path to bird and bird6 sockets.
    Àttention: 
	* you must set write permissions on BIRD sockets so user/group who runs HTTP server can write to the BIRD socket
	    exmpl: chmod o=w /path/to/bird.ctl
	* if you set socket permissions they will be rewrited after BIRD daemon restarted - keep this in mind

    Default values:
	- for IPv4 BIRD daemon (birdc):  /var/run/bird.ctl
	- for IPv6 BIRD daemon (birdc6): /var/run/bird6.ctl
*/
$config['birdc']="/var/run/bird.ctl";
$config['birdc6']="/var/run/bird6.ctl";


//========================================================================================
//				    BIRD CLIENT SCRIPT CONFIG SECTION
//========================================================================================

/*
    Bird client script
    ==================================
    bird_client_dir: full path to directory where script bird.client.php is located
    bird_client_file: name of bird client script, default bird.client.php
    suppress_welcome: don`t print BIRD welcome string, where BIRD version is present
    ping_util: 
	* path - full path to ipv4 ping utility
	* flags - add flags when execute ping utility
    ping6_util:
	* path - full path to IPv6 ping utility
	* flags - add flags when execute ping utility
    trace_util: 
	* path - full path to ipv4 traceroute utility
	* flags - add flags when execute traceroute utility
    trace6_util:
	* path - full path to IPv6 traceroute utility
	* flags - add flags when execute traceroute utility
*/
$config['bird_client_dir']="/full/path/to/bird.client/dir";
$config['bird_client_file']="bird.client.php";
$config['suppress_welcome']=true;

$config['ping_util']['path']="/sbin/ping";
$config['ping_util']['flags']="-c 5";
$config['ping6_util']['path']="/sbin/ping6";
$config['ping6_util']['flags']="-c 5";
$config['trace_util']['path']="/usr/sbin/traceroute";
$config['trace_util']['flags']="-m 20 -q 1 -w 1 -I";
$config['trace6_util']['path']="/usr/sbin/traceroute6";
$config['trace6_util']['flags']="-m 20 -q 1 -I";


//========================================================================================
//				    WEB INTERFACE SECTION
//========================================================================================
define('REMOTE_ADDR',$_SERVER['REMOTE_ADDR']);			//Remote client IP-address, used for restricted commands
// comment out previous string and UNcomment this define if you use NGINX as proxy for APACHE
// don`t forget to add "proxy_set_header    X-Real-IP        $remote_addr;" to nginx.conf
//define('REMOTE_ADDR',$_SERVER['HTTP_X_REAL_IP']);

/*
    Main
    ====
    url: LG URL
    logo: path to logo image
    logo_url: href on logo image
    asn: Your Autonomous System Number (ASN)

    check_new_version: auto check if new version of LG is avail, default is true
    log_query: log LG requests to log file, default is false.
    log_query_result: log LG request result to log file, default is false
    log_query_file: full path to the log file, default is empty
*/
$config['logo']="img/logo.png";
$config['logo_url']="http://www.subnets.ru";
$config['company_name']="SUBNETS.RU";
$config['asn']="51410";
$config['contact_email']="your_email@domain.zone";
$config['disclaimer']="All commands will be logged for possible later analysis and statistics. If you don't accept this policy, please close window now!";

$config['check_new_version']=true;
$config['log_query']=false;
$config['log_query_result']=false;
$config['log_query_file']="";

/*
    IPv6
    =====
    Enable IPv6 support or not, default is false.
*/
$config['ipv6_enabled']=false;

/*
    Query types
    ============
    This query types is served by LG
    Format:
	$config['query']['QUERY_NAME'][PARAMS]
    PARAMS:
	* name - name of the query in web-interface
	* restricted - If "restricted" is set to "false", then anyone can see this query type in the web-iface. If "restricted" is set to "true", then only permitted IPs can see this query type in the web-iface.
	* additional_empty - permit empty additional parameters if they really don`t needed
	* addon - add this string to the end of the command before send it to bird.client
*/
$config['query']=array();

$config['query']['route']=array();
$config['query']['route']['name']="Show route";
$config['query']['route']['additional_empty']=false;
$config['query']['route']['restricted']=false;
$config['query']['route']['addon']="all";

$config['query']['ping']=array();
$config['query']['ping']['name']="Ping IP";
$config['query']['ping']['additional_empty']=false;
$config['query']['ping']['restricted']=false;
$config['query']['ping']['addon']="";

$config['query']['trace']=array();
$config['query']['trace']['name']="Trace IP";
$config['query']['trace']['additional_empty']=false;
$config['query']['trace']['restricted']=false;
$config['query']['trace']['addon']="";

$config['query']['protocols']=array();
$config['query']['protocols']['name']="Show protocols";
$config['query']['protocols']['additional_empty']=true;
$config['query']['protocols']['restricted']=true;
$config['query']['protocols']['addon']="";

$config['query']['export']=array();
$config['query']['export']['name']="Advertised routes";
$config['query']['export']['additional_empty']=false;
$config['query']['export']['restricted']=true;
$config['query']['export']['addon']="all";

/*
    Permit restricted commands for IPs
    ===================================
    If "restricted" is set to "true" in any query type - here you can set list of permitted IPs for seeing this query type in the web-iface.
    If no IPs is specified then nobody can see "restricted" query types.
    You can set as many IPs as you want:
	$config['restricted'][]="1.1.1.1";
	$config['restricted'][]="2.2.2.2";
	$config['restricted'][]=...etc.....
*/
$config['restricted']=array();

//$config['restricted'][]="1.1.1.1";
//$config['restricted'][]="2.2.2.2";

/* 
    Nodes configuration
    ====================
    If "host" is set to "socket" then connect to BIRD socket on localhost (this server).
    If "host" is set to IP-address + set "port", then connections goes to bird.client.php on remote host.
    Example
    +++++++
    First node in the list:
	$hin++;
	$config['nodes'][$hin]['host'] = 'socket';
	$config['nodes'][$hin]['port'] = '';
	$config['nodes'][$hin]['name'] = 'Localhost';
	$config['nodes'][$hin]['description'] = 'This server';

    Second node (remote node) in the list:
	$hin++;
	$config['nodes'][$hin]['host'] = '1.1.1.1';
	$config['nodes'][$hin]['port'] = '55555';
	$config['nodes'][$hin]['name'] = 'Remote';
	$config['nodes'][$hin]['description'] = 'remote host';

    Third node:
	$hin++;
	$config['nodes'][$hin]['host'] = .....etc....

*/
$hin=0;

//First node
$hin++;
$config['nodes'][$hin]['host'] = 'socket';
$config['nodes'][$hin]['port'] = '';
$config['nodes'][$hin]['name'] = 'Localhost';
$config['nodes'][$hin]['description'] = 'BIRD on localhost';

/*
    Bird output
    ===================
    modify: 
	* routes - modify bird`s standart route output to our custom format output, default is false
	* protocols  - modify bird`s standart protocols output our custom format output, default is false
	* own_community - if community is in the own communities list (difined below) than also print community meaning, default is false
    hide:
	* protocol - don`t display bird`s protocol names in route output, default is false
	* iface - don`t display interface names in route output, default is false
*/
$config['output']=array();

$config['output']['modify']=array();
$config['output']['modify']['routes']=false;
$config['output']['modify']['protocols']=false;
$config['output']['modify']['own_community']=false;

$config['output']['hide']=array();
$config['output']['hide']['protocol']=false;
$config['output']['hide']['iface']=false;

/*
    Communities list
    =================
    List of communities for explanation. Used if "explain_own_community" is set to true.
    Format:
	$config['community'][COMMUNITY]="community meaning text";
    Examples:
	$config['own_community'][666]="blackhole";
	$config['own_community'][999]="internal route";
*/
$config['own_community']=array();
$config['own_community'][666]="blackhole";

/*
    End of configuration file
*/
?>