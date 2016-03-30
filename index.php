<?
/*
    BIRD Looking Glass :: Version: 0.3.0
    Home page: http://bird-lg.subnets.ru/
    =====================================
    Copyright (c) 2013 SUBNETS.RU project (Moscow, Russia)
    Authors: Nikolaev Dmitry <virus@subnets.ru>, Panfilov Alexey <lehis@subnets.ru>

*/

$func="func.php";
if (!@include $func){
    print "[ERROR]: Fuctions file not found\n";
    exit;
}

$param=$_POST;

if (!isset($param['query'])&&!$param['query']){
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
    print "function det(reqv){
	div=$('content');
	if (reqv){
	    div.innerHTML='<center><BR><img src=\"img/indicator.gif\"><BR><b>Processing...</b></center>';
	    new Request.HTML({url: 'index.php', method: 'post', update: div}).send(reqv);
	}else{
	    $('errors').empty();
	    $('errors').innerHTML='Errors:<ul>Query was empty</ul>';
	}
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
    print "<span id=\"new_version_info\"></span>&copy; <a href=\"http://subnets.ru\" target=\"_blank\">SUBNETS.RU</a>, 2013";
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
		if (!$new_ver['error']&&$new_ver['version']){
		    printf("<span class=\"new_ver\">This LG version %s, latest LG <a href=\"%s\" target=\"blank\">version %s</a></span><BR>",LG_VERSION,$new_ver['url'],$new_ver['version']);
		}
	    }
	}
    }else{
	print "<script>$('errors').empty();</script>";
	$back_but="<button class=\"but\" onclick=\"request('0');\">New request</button><BR><BR>";
	if ($query=="main_form"){
	    printf("%s",main_form($config));
	}elseif ($query=="bgp_det"){
	    print $back_but;
	    if (!$param['protocol']){$param['protocol']="IPv4";}
	    if ($param['peer']&&$param['router']){
		$param['cmd']="show protocols";
		$result=bird_send_cmd($param,$config);
		//deb($result);
		if ($result['error']){
		    printf("%s",$result['error']);
		}else{
		    $tmp=explode("\n",$result['data']);
		    foreach ($tmp as $k => $v){
			if (preg_match("/^([a-zA-Z0-9\_]+)\s+BGP\s+/",$v,$m)){
			    if (md5($m[1])==$param['peer']){
				$peer=$m[1];
			    }
			}
		    }
		    if ($peer){
			$param['peer']=$peer;
			$param['full_info']=true;
			$result=bgp_neighbor_details($param,$config);
			//deb($result);
			if ($result['error']){
			    printf("%s",$result['error']);
			}else{
			    printf("<div class=\"result\">%s</div>",parse_bird_data($result['data'],"protocols",$config));
			}
		    }else{
			printf("%s",error("Peer not found"));
		    }
		}
	    }else{
		printf("%s",error("Params is missing"));
	    }
	}else{
	    print $back_but;
	    if (is_array($config['query'])){
		if (isset($config['query'][$query])){
		    if (!isset($param['protocol'])){
			$param['protocol']="IPv4";
		    }
		    $chk=check_form_params($param,$config);
		    if ($chk['error']){
			print $chk['error'];
		    }else{
			$result=process_query($param,$config);
			if ($result['error']){
			    printf("%s",$result['error']);
			}else{
			    printf("<div class=\"result\">%s</div>",parse_bird_data($result['data'],$query,$config));
			}
		    }
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