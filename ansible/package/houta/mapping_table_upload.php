<?php
   require_once("Robotprofile_utility.php");
   #require_once("../../PHPExcel/Classes/PHPExcel.php");
   require_once("../../lib/SessionValue.php");

   define("SUCCESS", 0);
   define("DELAY_SEC", 3);
   define("FILE_ERROR", -2);
   define("FILE_NAME", "../../DB.conf");
   //检查环境变数配置档
   if (file_exists(FILE_NAME))
   {
      include(FILE_NAME);
   }
   else
   {
      sleep(DELAY_SEC);
      $file_status->status = -1;
      array_push($file_status->errors, array("sheet"=>0, "lines"=>0, "message"=>"无法取得环境变数配置档"));
      goto err_exit;
   }
   define("MONGODB_URL", $mongodb_url);
   define("TASK_ENGINE_URL", $task_engine);

   header('Content-Type:text/html;charset=utf-8');

   //timezone
   date_default_timezone_set(TIME_ZONE);

   // since phpexcel maybe execute very long time, so currently set time limit to 0
   set_time_limit(0);
   ini_set('memory_limit', '-1');
   // 取得appid
   if (isset($_POST["appid"])){
      $appid = $_POST["appid"];
   }
   else {
      sleep(DELAY_SEC);
      $file_status->status = -1;
      array_push($file_status->errors, array("sheet"=>0, "lines"=>0, "message"=>"无法取得appid"));
      goto err_exit;
   }

   //上传文件路径
   $target_dir = "../../Files/settings/" . $appid . "/";
   $old = umask(0);
   if(!is_dir($target_dir)){
      mkdir($target_dir, 0777, true);
   }
   umask($old);
   $file_status = new UploadFileStatus();
   $file_status->status = UPLOAD_SUCCESS;
   $file_type = pathinfo($_FILES["mapping_table"]["name"],PATHINFO_EXTENSION);
   if (!in_array($file_type, array("csv"))) {
      sleep(DELAY_SEC);
      $file_status->status = -1;
      array_push($file_status->errors, array("sheet"=>0, "lines"=>0, "message"=>"上传文件格式不正确, 请传入csv格式谢谢!"));
      goto err_exit;
   }
   $str_date = date("YmdHis", time());

   //mapping_table_name
   $mapping_table_name = str_replace(".csv","",$_FILES["mapping_table"]["name"]);
   $mapping_table_name = "{$mapping_table_name}_{$str_date}";

   //上传后新文件名称
   $robot_file = $target_dir . "mapping_table_{$str_date}." . $file_type;

   //判断是否是空文件
   if ($_FILES['mapping_table']['size'] == 0) {
      $file_status->status = -1;
      array_push($file_status->errors, array("sheet"=>0, "lines"=>0, "message"=>MSG_ERR_EMPTY_FILE));
      goto err_exit;
   }

   //产生新文件
   if (!move_uploaded_file($_FILES["mapping_table"]["tmp_name"], $robot_file))
   {
      $file_status->status = -1;
      array_push($file_status->errors, array("sheet"=>0, "lines"=>0, "message"=>MSG_ERR_MOVE_FILE));
      goto err_exit;
   }

   //读档存入db
   if (!read_csv($user_id, $mapping_table_name, $robot_file)){
      $file_status->status = -1;
      array_push($file_status->errors, array("sheet"=>0, "lines"=>0, "message"=>"档案读取格式错误"));
      goto err_exit;
   }

   //判断Excel格式
   function is_valid_excel_type($file_path)
   {
      //only accept excel2007, and 2003 format
      $valid_types = array('Excel2007', 'Excel5');

      foreach($valid_types as $type)
      {
         $reader = PHPExcel_IOFactory::createReader($type);
         if ($reader->canRead($file_path))
         {
            return true;
         }
      }

      return false;
   }

   //读取csv档案
   function read_csv($user_id, $mapping_table_name, $file_path)
   {
      try{
         //打开文件
         $f = fopen($file_path, 'r');
         if ($f) {
            //获取文件的一行内容，注意：需要php5才支持该函数；
            while ($line = stream_get_line($f, 8192, "\n")) {
               $terms = explode(',',$line);
               $arr[] = array('key' => trim($terms[0]), 'value' => trim($terms[1]));
            }
            fclose($f);//关闭文件
            unlink($file_path);//删除文件
         }

         $post = [
            'userid' => $user_id,
            'mapping_table_name' => $mapping_table_name,
            'mapping_table' => json_encode($arr)
         ];

         $ch = curl_init();
         $url = TASK_ENGINE_URL.'task_engine_mapping_table';
         curl_setopt($ch, CURLOPT_URL, $url);
         curl_setopt($ch, CURLOPT_POST, 1);
         curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         $response = curl_exec($ch);
         curl_close($ch);
         $rtn_obj = json_decode($response);
         if ($rtn_obj->{'msg'} == 'DB connection error!!')
            return false;
         else
            return true;
      }
      catch (Exception $e)
      {
         return false;
      }
   }

   //读取Excel档案
   function read_excel_and_insert_into_database($user_id, $file_path)
   {
      global $file_status;
      // load file
      try
      {
         $input_file_type = PHPExcel_IOFactory::identify($file_path);
         $reader = PHPExcel_IOFactory::createReader($input_file_type);
         $excel = $reader->load($file_path);
         // parse file
         $sheet = $excel->getSheet(0);
         $sheet_title = $sheet->getTitle();
         $highest_row = $sheet->getHighestRow();
         for($row = 0; $row <= $highest_row; $row++){
            $key = $sheet->getCellByColumnAndRow(0, $row)->getValue();
            $value = $sheet->getCellByColumnAndRow(1, $row)->getValue();
            $arr[] = array('key' => $key, 'value' => $value);
         }

         $post = [
            'userid' => $user_id,
            'mapping_table_name' => $mapping_table_name,
            'mapping_table' => json_encode($arr)
         ];

         $ch = curl_init();
         $url = TASK_ENGINE_URL.'task_engine_mapping_table';
         curl_setopt($ch, CURLOPT_URL, $url);
         curl_setopt($ch, CURLOPT_POST, 1);
         curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         $response = curl_exec($ch);
         curl_close($ch);
         $rtn_obj = json_decode($response);
         if ($rtn_obj->{'msg'} == 'DB connection error!!')
            return false;
         else
            return true;
      }
      catch (Exception $e)
      {
         return false;
      }
   }

   err_exit:
   if ($file_status->status == SUCCESS)
   {
      if(count($file_status->errors) > 0)
      {
         echo "{\"return\": -1, \"error\": \"{$file_status->errors[0]["message"]}\"}";
      }
      else
      {
         echo "{\"return\": 0, \"error\": \"\"}";
      }
   }
   else if ($file_status->status != SUCCESS) {
      $error = $file_status->errors[0];
      echo "{\"return\": -1, \"error\": \"{$error["message"]}\"}";
   }

   return;
?>
