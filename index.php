<?
/*

    BIRD Looking Glass :: Version: 0.4.1
    Home page: http://bird-lg.subnets.ru/
    =====================================
    Copyright (c) 2013-2014 SUBNETS.RU project (Moscow, Russia)
    Authors: Nikolaev Dmitry <virus@subnets.ru>, Panfilov Alexey <lehis@subnets.ru>

*/

//////////////////////// Index file /////////////////////////////

$pathinfo = realpath( dirname(__FILE__) );
$func=$pathinfo."/func.php";
if (!@include $func){
    print "[ERROR]: Fuctions file not found\n";
    exit;
}

$param=$_POST;
if (!isset($param['query'])){
    $param['query']="";
}

if (!$param['query']){
    head();
    print "<script>";
    print "function request(act){\n";
	print "var div;\n";
	print "div=$('content');\n";
	print "var reqv='';\n";
	print "var tmp='';\n";
	print "var err='';\n";
	print "var query='';\n";
	print "if (act>0){
	    var el = $$('div#content input');
	    el.each(function(item, index){
		if (item.type == 'radio'){
		    if (item.checked==true){
			query=item.name+'='+item.value;
		    }
		}
	    });
	    if (query){
		tmp=query;
	    }else{
		err+='<li>Select Type of Query</li>';
	    }
	    if ($('router').value){
		tmp+='&router='+$('router').value;
	    }else{
		err+='<li>Select Node</li>';
	    }
	    tmp+='&additional='+$('additional').value;";
	    if ($config['ipv6_enabled']){
		print "tmp+='&protocol='+$('protocol').value;";
	    }
	    print "if (!err){
		reqv=tmp;
	    }
	}else{
	    reqv='query=main_form';
	}\n";
	print "if (reqv){
	    div.innerHTML='<center><BR><img src=\"img/indicator.gif\"><BR><b>Processing...</b></center>';
	    new Request.HTML({url: 'index.php', method: 'post', update: div}).send(reqv);
	}else{
	    $('errors').empty();
	    $('errors').innerHTML='Errors:<ul>'+err+'</ul>';
	}";
    print "}\n";
    print "function subrequest(reqv){
	div=$('content');
	if (reqv){
	    div.innerHTML='<center><BR><img src=\"img/indicator.gif\"><BR><b>Processing...</b></center>';
	    new Request.HTML({url: 'index.php', method: 'post', update: div}).send(reqv);
	}else{
	    $('errors').empty();
	    $('errors').innerHTML='Errors:<ul>Query was empty</ul>';
	}
    }\n";
    print "function additional(val){";
	if (config_val($config,"clear_additional")){
	    print "$('additional').value='';";
	}
    print "
	$('additional').placeholder=val;
    }\n";

    if (isset($config['check_new_version'])){
	if ($config['check_new_version']){
	    print "function check_new_version(){
		var reqv='query=check_version';
		new Request.HTML({url: 'index.php', method: 'post', update: 'new_version_info'}).send(reqv);
	    }\n";
	    print "check_new_version();\n";
	}
    }
    print "</script>";

    print "<div class=\"header\">";
    print "<span id=\"new_version_info\"></span>&copy; <a href=\"http://subnets.ru\" target=\"_blank\">SUBNETS.RU</a>, 2013-2014";
    print "</div>";

    printf("<TABLE BORDER=\"0\" WIDTH=\"100%%\">
    <TR>
	<TD Align=\"center\"><A HREF=\"%s\"><IMG SRC=\"%s\" BORDER=\"0\" ALT=\"LOGO\" TITLE=\"LOGO\"></A></TD>
    </TR>
    </TABLE>",$config['logo_url'],$config['logo']);
    print "<CENTER>";
    printf("<H2>%s Looking Glass (AS%s)</H2>",$config['company_name'],$config['asn']);
    print "</CENTER>";
    print "<HR SIZE=2 WIDTH=\"85%\" NOSHADE>";
    print "<span id=\"errors\" class=\"error_text\"></span>";
    print "<div id=\"content\" class=\"content\">";

    printf("%s",main_form($config));

    print "</div>";
    print "<HR SIZE=\"2\" WIDTH=\"85%%\" NOSHADE>";
    if (isset($config['log_query'])&&$config['log_query']){
	print "<div class=\"disclaimer\">";
	printf("<FONT SIZE=\"-3\">Disclaimer: %s</FONT>",$config['disclaimer']);
	$logging=logging($config);
	if ($logging['error']){
	    print $logging['error'];
	}
	print "</div>";
    }
    if (isset($config['contact_email'])&&$config['contact_email']){
	printf("<CENTER><i>Please email questions or comments to <A HREF=\"mailto:%s\">%s</A></i></CENTER>",$config['contact_email'],$config['contact_email']);
    }
    foot();
}else{
    $query=$param['query'];
    if ($query=="check_version"){
	if (isset($config['check_new_version'])){
	    if ($config['check_new_version']){
		$new_ver=check_lg_version($config['check_new_version']);
		if (!$new_ver['error'] && $new_ver['version']){
		    if (LG_VERSION != $new_ver['version'] ){
			printf("<span class=\"new_ver\">This LG version %s, latest LG <a href=\"%s\" target=\"blank\">version %s</a></span><BR>",LG_VERSION,$new_ver['url'],$new_ver['version']);
		    }
		}
	    }
	}
    }else{
	//deb($query);
	//deb($param);

	$js_param=array();
	$js_param['repeat']="";
	$js_param['back']="";
	if ( is_array($param) && count($param) > 0 ){
	    $back="";
	    if ( isset($param['back']) && $param['back'] ){
		$back=$param['back'];
		unset($param['back']);
	    }

	    foreach ($param as $k => $v){
		$js_param['repeat'] .= sprintf("&%s=%s%s",$k,$v,$back?"&back=".$back:"");
		if ($back){
		    if ($k == "query"){
			if ( $back ){
			    $v=$back;
			}else{
			    if ( preg_match("/^nei_route_/",$v) ){
				$v="bgp_summ";
			    }elseif ( preg_match("/^bgp_det/",$v) ){
				$v="bgp_summ";
			    }
			}
		    }
		    $js_param['back'] .= sprintf("&%s=%s",$k,$v);
		}
	    }
	}

	$buttons=array();
	$buttons['new_request']="<button class=\"but\" onclick=\"request('0');\">New request</button>";
	if ($js_param['repeat']){
	    $buttons['repeat']=sprintf("<button class=\"but\" onclick=\"subrequest('%s');\">Repeat request</button>",$js_param['repeat']);
	}
	if ($js_param['back']){
	    $buttons['back']=sprintf("<button class=\"but\" onclick=\"subrequest('%s');\">Return back</button>",$js_param['back']);
	}

	print "<script>$('errors').empty();</script>";

	if ($query && $query !="main_form"){
	    show_buttons($buttons);
	}

	if ($query=="main_form"){
	    printf("%s",main_form($config));
	}elseif ($query=="bgp_det"){
	    if (config_val($config['output'],"hide","bgp_peer_det_link")){
		printf("%s",error("Command execution is not permitted"));
	    }else{
		$restricted=0;
		if (isset($config['query']['bgp_summ']['restricted'])){
		    if (restricted($config['restricted'],$config['query']['bgp_summ']['restricted'])){
			$restricted=1;
		    }
		}
		if ($restricted){
		    printf("%s",error("Command execution is not permitted"));
		}else{
		    if ($param['peer']&&$param['router']){
			$result=bgp_peer_search($param,$config);
			if ($result['error']){
			    printf("%s",$result['error']);
			}else{
			    if ($result['data']){
				$param['peer']=$result['data'];
				$param['full_info']=true;
				unset($result);
				$result=bgp_neighbor_details($param,$config);
				//deb($result);
				if ($result['error']){
				    printf("%s",$result['error']);
				}else{
				    printf("<div class=\"result\">%s</div>",parse_bird_data($result['data'],"protocols",$config,$param));
				}
			    }else{
				printf("%s",error("Peer not found"));
			    }
			}
		    }else{
			printf("%s",error("Params is missing"));
		    }
		}
	    }

	    show_buttons($buttons,1);
	}elseif ($query=="nei_route_accepted"){
	    if (config_val($config['output'],"hide","bgp_accepted_routes_link")){
		printf("%s",error("Command execution is not permitted"));
	    }else{
		$restricted=0;
		if (isset($config['query']['bgp_summ']['restricted'])){
		    if (restricted($config['restricted'],$config['query']['bgp_summ']['restricted'])){
			$restricted=1;
		    }
		}
		if ($restricted){
		    printf("%s",error("Command execution is not permitted"));
		}else{
		    if ($param['peer']&&$param['router']){
			$result=bgp_peer_search($param,$config);
			if ($result['error']){
			    printf("%s",$result['error']);
			}else{
			    if ($result['data']){
				$param['peer']=$result['data'];
				unset($result);
				$result=process_query($param,$config);
				//deb($result);
				if ($result['error']){
				    printf("%s",$result['error']);
				}else{
				    printf("<div class=\"result\">%s</div>",parse_bird_data($result['data'],"route",$config,$param));
				}
			    }else{
				printf("%s",error("Peer not found"));
			    }
			}
		    }else{
			printf("%s",error("Params is missing"));
		    }
		}
	    }

	    show_buttons($buttons,1);
	}elseif ($query=="nei_route_best"){
	    if (config_val($config['output'],"hide","bgp_best_routes_link")){
		printf("%s",error("Command execution is not permitted"));
	    }else{
		$restricted=0;
		if (isset($config['query']['bgp_summ']['restricted'])){
		    if (restricted($config['restricted'],$config['query']['bgp_summ']['restricted'])){
			$restricted=1;
		    }
		}
		if ($restricted){
		    printf("%s",error("Command execution is not permitted"));
		}else{
		    if ($param['peer']&&$param['router']){
			$result=bgp_peer_search($param,$config);
			if ($result['error']){
			    printf("%s",$result['error']);
			}else{
			    if ($result['data']){
				$param['peer']=$result['data'];
				unset($result);
				$result=process_query($param,$config);
				//deb($result);
				if ($result['error']){
				    printf("%s",$result['error']);
				}else{
				    printf("<div class=\"result\">%s</div>",parse_bird_data($result['data'],"route",$config,$param));
				}
			    }else{
				printf("%s",error("Peer not found"));
			    }
			}
		    }else{
			printf("%s",error("Params is missing"));
		    }
		}
	    }

	    show_buttons($buttons,1);
	}elseif ($query=="nei_route_filtered"){
	    if (config_val($config['output'],"hide","bgp_filtered_routes_link")){
		printf("%s",error("Command execution is not permitted"));
	    }else{
		$restricted=0;
		if (isset($config['query']['bgp_summ']['restricted'])){
		    if (restricted($config['restricted'],$config['query']['bgp_summ']['restricted'])){
			$restricted=1;
		    }
		}
		if ($restricted){
		    printf("%s",error("Command execution is not permitted"));
		}else{
		    if ($param['peer']&&$param['router']){
			$result=bgp_peer_search($param,$config);
			if ($result['error']){
			    printf("%s",$result['error']);
			}else{
			    if ($result['data']){
				$param['peer']=$result['data'];
				unset($result);
				$result=process_query($param,$config);
				//deb($result);
				if ($result['error']){
				    printf("%s",$result['error']);
				}else{
				    printf("<div class=\"result\">%s</div>",parse_bird_data($result['data'],"route",$config,$param));
				}
			    }else{
				printf("%s",error("Peer not found"));
			    }
			}
		    }else{
			printf("%s",error("Params is missing"));
		    }
		}
	    }

	    show_buttons($buttons,1);
	}elseif ($query=="nei_route_export"){
	    if (config_val($config['output'],"hide","bgp_export_routes_link")){
		printf("%s",error("Command execution is not permitted"));
	    }else{
		$restricted=0;
		if (isset($config['query']['bgp_summ']['restricted'])){
		    if (restricted($config['restricted'],$config['query']['bgp_summ']['restricted'])){
			$restricted=1;
		    }
		}
		if ($restricted){
		    printf("%s",error("Command execution is not permitted"));
		}else{
		    if ($param['peer']&&$param['router']){
			$result=bgp_peer_search($param,$config);
			if ($result['error']){
			    printf("%s",$result['error']);
			}else{
			    if ($result['data']){
				$param['peer']=$result['data'];
				unset($result);
				$result=process_query($param,$config);
				//deb($result);
				if ($result['error']){
				    printf("%s",$result['error']);
				}else{
				    printf("<div class=\"result\">%s</div>",parse_bird_data($result['data'],"route",$config,$param));
				}
			    }else{
				printf("%s",error("Peer not found"));
			    }
			}
		    }else{
			printf("%s",error("Params is missing"));
		    }
		}
	    }
	    
	    show_buttons($buttons,1);
	}else{
	    //deb($query);
	    //deb($param);
	    //deb($config['query']);
	    if (is_array($config['query'])){
		if (isset($config['query'][$query])){
		    if (!isset($param['protocol'])){
			$param['protocol']="IPv4";
		    }
		    $chk=check_form_params($param,$config);
		    if ($chk['error']){
			print $chk['error'];
		    }else{
			$restricted=0;
			if (isset($config['query'][$query]['restricted'])){
			    if (restricted($config['restricted'],$config['query'][$query]['restricted'])){
				$restricted=1;
			    }
			}
			if ($restricted){
			    printf("%s",error("Command execution is not permitted"));
			}else{
			    $result=process_query($param,$config);
			    if ($result['error']){
				printf("%s",$result['error']);
			    }else{
				printf("<div class=\"result\">%s</div>",parse_bird_data($result['data'],$query,$config,$param));
			    }
			}
		    }
		    show_buttons($buttons,1);
		}else{
		    printf("%s",error("Unknown query type"));
		}
	    }else{
		printf("%s",error("Config error: no query types"));
	    }
	}
    }
}
?>