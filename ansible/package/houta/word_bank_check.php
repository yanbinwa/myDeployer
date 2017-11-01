<?php
   //1.  导入配置文件和Excel读取文件
   set_time_limit(1800);
   include_once '../../lib/MySqlConnect.php';
   //2.  获取Session
   require_once("../../lib/SessionValue.php");
   //3.  导入Excel扩展
   require_once("Robotprofile_utility.php");
   #require_once("../../PHPExcel/Classes/PHPExcel.php");

   header('Content-Type:text/html;charset=utf-8');
   date_default_timezone_set(TIME_ZONE);
   define("PROCESS_API", $knowledge_upload);

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

   //4.  检查appid和用户是否存在
   if (check_appid($link, $appid, $user_id) != 0) {
      return_message(SYMBOL_ERROR, "10008", 0);
      exit();
   }

   if (!$link) {
      mysqli_close($link);
   }
   $link = mysqli_connect(DB_HOST, ADMIN_ACCOUNT, ADMIN_PASSWORD, "multicustomer");
   mysqli_set_charset ($link,"utf8");
   if ($link) {
      $str_process_status_select = "select * from process_status where app_id = '{$appid}' and module = 'wordbank' order by id desc limit 1";
      if ($result = mysqli_query($link, $str_process_status_select)) {
         $num = mysqli_num_rows($result);
         if ($num == 1) {
            $row = mysqli_fetch_assoc($result);
            $end_at = $row["end_at"];
            if (strcmp($row["status"], "success") == 0) {
               $json_data = json_encode(array("return"=>0, "return_message"=>"导入成功！", "end_at" => $end_at), JSON_UNESCAPED_UNICODE);
            }
            elseif (strcmp($row["status"], "running") == 0) {
               $json_data = json_encode(array("return"=>0, "return_message"=>"导入执行中！", "end_at" => $end_at), JSON_UNESCAPED_UNICODE);
            }
            elseif (strcmp($row["status"], "fail") == 0 || strcmp($row["status"], "rerun") == 0) {
               $json_data = json_encode(array("return"=>-1, "return_message"=>"{$row["message"]}", "end_at" => $end_at), JSON_UNESCAPED_UNICODE);
            }
            else {
               $json_data = json_encode(array("return"=>0, "return_message"=>"", "end_at" => $end_at), JSON_UNESCAPED_UNICODE);
            }
         }
         else {
            $json_data = json_encode(array("return"=>0, "return_message"=>"", "end_at" => 0), JSON_UNESCAPED_UNICODE);
         }
      }
      else {
         $json_data = json_encode(array("return"=>-1, "return_message"=>"查询失败！", "end_at" => 0), JSON_UNESCAPED_UNICODE);
      }
   }
   else {
      $json_data = json_encode(array("return"=>-1, "return_message"=>"查询失败！", "end_at" => 0), JSON_UNESCAPED_UNICODE);
   }
   echo $json_data;
   return;
?>
