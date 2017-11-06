<?php
   //1.  导入配置文件和Excel读取文件
   set_time_limit(1800);
   include_once '../../lib/MySqlConnect.php';
   include_once '../../lib/LogPhp.php';
   require_once("Robotprofile_utility.php");
   //require_once("../../PHPExcel/Classes/PHPExcel.php");
   require_once("../../lib/SessionValue.php");
   header('Content-Type:text/html;charset=utf-8');
   date_default_timezone_set(TIME_ZONE);
   define("CHECK_DOWN", $knowledge_check);

   ini_set('memory_limit', '-1');
   if (isset($_POST["appid"])){
      $appid = $_POST["appid"];
   }
   else {
      sleep(DELAY_SEC);
      echo FILE_ERROR;
      return;
   }

   if (mb_strlen(trim($appid)) == 0) {

      return_message(SYMBOL_ERROR, "数据库异常" . __LINE__, DELAY_SEC);
      exit();
   }

   if (check_appid($link, $appid, $user_id) != 0) {
      LogPhp::warn("AppId, UserId", "[{$appid}] ----- [{$user_id}]");
      return_message(SYMBOL_ERROR, "10008", 0);
      exit();
   }

   $str_url = CHECK_DOWN . "cknowledge/{$appid}";
   //$str_url = CHECK_DOWN . "cknowledge/c4e5608e5217a631fe341059231122d3";
   $str_curl = "curl ". $str_url;
   $json_str = shell_exec($str_curl);
   if (trim($json_str) == "") {
      return_message(-13,  "文件不存在，请先上传文件！", 0);
      return;
   }
   $json_data = json_decode($json_str);
   $str_value = $json_data[0]->Value;
   $str_value = base64_decode($str_value);
   $json_value = json_decode($str_value);
   $str_status = $json_value->status;
   echo "{";
   echo "\"return\":0,";
   echo "\"return_message\":" . "\"\",";
   echo "\"data\":" . $str_value;
   echo "}";

   return;
?>
