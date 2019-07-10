<?php
header('Content-Type: text/html; charset=utf-8');

$APIKey = 'XXXXXXXX';//百度AI开放平台API Key
$SecretKey = 'XXXXXXXX';//百度AI开放平台API Key
$cjhaoma=array(//抽奖号码
    "13000000000"
    ,"15500000000"
    ,"18500000000"
    );
    
$bd_ocr_at = bd_ocr_at($APIKey,$SecretKey);
foreach ($cjhaoma as $value){
   echo "------${value}------\r\n";
   qdpjcj($value,$bd_ocr_at);
  }
  echo "任务完成！\r\n";

function qdpjcj($hm,$bd_ocr_at){//输入号码自动抽奖
$html = qhtml_userid();
$xh=1;
do{
  $yzres=yz_yzm($hm,$html,$bd_ocr_at);
  $xh++;
  if($xh==6){echo "验证码验证错误过多！\r\n"; return false;}
}while($yzres[0]==false);
for ($x=1; $x<=4; $x++) {
$cjres = choujiang($html,$yzres[1],$yzres[2]);
echo "第${x}次抽奖结果:".$cjres." \r\n";
if(strpos($cjres,'没有抽奖次数') !== false){ return true; }
}
return true;
}
function choujiang($html_arr,$mobile,$yzm){//抽奖获取结果
$JSESSIONID=$html_arr[0];
$SL=$html_arr[1];
$cookies="name=value; SL=$SL; JSESSIONID=$JSESSIONID";
$postdata="mobile=$mobile&image=$yzm&userid=$JSESSIONID";
$cjres=curl('https://m.client.10010.com/sma-lottery/qpactivity/qpLuckdraw.htm',0,$postdata,1,$cookies);
$cj_arr = json_decode($cjres, true);
if($cj_arr["isunicom"]==false){return $cj_arr["msg"];}
if($cj_arr["status"]==200 or $cj_arr["status"]==0){
$giftnum=$cj_arr["data"]["level"];
switch ($giftnum){
case "1":
    $gift="50MB流量";
    break;
case "2":
    $gift="100MB流量";
    break;
case "3":
    $gift="200MB流量";
    break;
case "4":
    $gift="1000MB流量";
    break;
case "5":
    $gift="20砖石";
    break;
case "6":
    $gift="15元开卡红包";
    break;
case "7":
    $gift="50元开卡红包";
    break;
default:
    $gift="未知礼物";
    }
 return $gift;
}else if($cj_arr["status"]==500){return "没有抽奖次数了！";}
else if($cj_arr["status"]==400 or $cj_arr["status"]==700){return "抽奖人数过多";}
else{return "未知错误".$cj_arr["status"];}
}
function yz_yzm($hm,$html_arr,$bd_ocr_at){//验证验证码是否正确
$xh = 1;
do{
  $JSESSIONID=$html_arr[0];
  $SL=$html_arr[1];
  $cookies="SL=$SL; JSESSIONID=$JSESSIONID";
  $pic = get_yzmpic($html_arr);
  $yzm=ocr_yzm($hm,$bd_ocr_at,$pic);
  echo "验证码是:".$yzm."\r\n";
  $xh++;
  if($xh==8){die("验证码识别失败,退出！");}//避免死循环
}while($yzm>9999 or $yzm<1000);
$postdata="mobile=$hm&image=$yzm&userid=$JSESSIONID";
$yzmres=curl('https://m.client.10010.com/sma-lottery/validation/qpImgValidation.htm',0,$postdata,1,$cookies);
$res_arr = json_decode($yzmres, true);
if($res_arr["code"] != "YES"){$res[0]=false;$res[1]='2db565dc0843d5cdcef92e2db17a81ee';$res[2]='1111';}else{$res[0]=true;$res[1]=$res_arr["mobile"];$res[2]=$yzm;}
return $res;
}

function ocr_yzm($hm,$bdat,$pic){//AI识别验证码
$Postdata = "access_token=$bdat&image=".urlencode($pic);
$bdyzm = curl('https://aip.baidubce.com/rest/2.0/ocr/v1/numbers',0,$Postdata,1);
$ocr_arr = json_decode($bdyzm, true);
if(empty($ocr_arr["words_result"][0]["words"])){$yzm=999;}else{$yzm=$ocr_arr["words_result"][0]["words"];}
return $yzm;
}
function get_yzmpic($cookies){//获取验证码
$JSESSIONID=$cookies[0];
$SL=$cookies[1];
$cookies="SL=$SL; JSESSIONID=$JSESSIONID";
$time=time().rand(100,999);
$url="https://m.client.10010.com/sma-lottery/qpactivity/getSysManageLoginCode.htm?userid=$JSESSIONID&code=$time";
$getyzm=curl($url,0,0,0,$cookies);
return base64_encode($getyzm);
}
function qhtml_userid(){//HTML页面获取参数
$qdhtml=curl('https://m.client.10010.com/sma-lottery/qpactivity/qingpiindex',1);
$ret = preg_match('/userid\" type=\"hidden\" value=\"(.*)\"/',$qdhtml,$matchs);
if($ret >= 1){ $arr[0]=$matchs[1];}else{return false;}
$ret = preg_match('/SL=(.*)\; E/',$qdhtml,$matchs);
if($ret >= 1){ $arr[1]=$matchs[1];}else{return false;}
return $arr;
}
function bd_ocr_at($APIKey,$SecretKey){//百度AI-Token
    $url = 'https://aip.baidubce.com/oauth/2.0/token';
    $post_data = "grant_type=client_credentials&client_id=${APIKey}&client_secret=${SecretKey}";
    $res = curl($url,0,$post_data,1);
    //var_dump($res);
    $arr_bd = json_decode($res, true);
    if (empty($arr_bd["access_token"])){return false;}else{return $arr_bd["access_token"];}
}
function curl($url = '',$tou=0,$param = '',$post=0,$cookies='JSESSIONID=D146A2338E29418E6F31ACEC30409D89'){//CURL模块
        if (empty($url)) { return false; }
        $curl = curl_init();//初始化curl
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; U; Android 8.1.0; zh-cn; BLA-AL00 Build/HUAWEIBLA-AL00) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/57.0.2987.132 MQQBrowser/8.9 Mobile Safari/537.36');
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_URL,$url);
        //curl_setopt($curl, CURLOPT_PROXY, "127.0.0.1");
        //curl_setopt($curl, CURLOPT_PROXYPORT, 8888);
        curl_setopt($curl,CURLOPT_HTTPHEADER,array(
        'Cookie: '.$cookies
        ,'Upgrade-Insecure-Requests: 1'
        ));
        if($tou){curl_setopt($curl, CURLOPT_HEADER, 1);}//设置header
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if($post){//post提交方式
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
        }
        $data = curl_exec($curl);//运行curl
        if (curl_errno($curl)) {  return false;  }
        curl_close($curl);
        return $data;
    }
?>