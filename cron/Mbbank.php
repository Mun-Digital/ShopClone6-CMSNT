<?php
header('content-type: application/json');
    define("IN_SITE", true);
    require_once(__DIR__.'/../libs/db.php');
    require_once(__DIR__.'/../config.php');
    require_once(__DIR__.'/../libs/helper.php');
    require_once(__DIR__.'/../libs/database/users.php');
    require_once(__DIR__.'/../libs/database/invoices.php');
    $CMSNT = new DB();
    $user = new users();

    queryCancelInvoices();
    if (time() > $CMSNT->site('check_time_cron_bank')) {
        if (time() - $CMSNT->site('check_time_cron_bank') < 0) {
            die('[ÉT O ÉT ]Thao tác quá nhanh, vui lòng đợi');
        }
    }
    $CMSNT->update("settings", ['value' => time()], " `name` = 'check_time_cron_bank' ");
    
    if ($CMSNT->site('status_bank') != 1) {
        die('Chức năng đang tắt.');
    }
    if ($CMSNT->site('token_bank') == '') {
        die('Thiếu Token Bank');
    }
    $token = $CMSNT->site('token_bank');
    $stk = $CMSNT->site('stk_bank');
    $mk = $CMSNT->site('mk_bank');

        $curl = curl_init();
        curl_setopt_array($curl, array(
             CURLOPT_URL => 'https://api.dichvu365.xyz/api/check-history/mbbank',
             CURLOPT_RETURNTRANSFER => true,
             CURLOPT_ENCODING => '',
             CURLOPT_MAXREDIRS => 10,
             CURLOPT_TIMEOUT => 0,
             CURLOPT_FOLLOWLOCATION => true,
             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
             CURLOPT_CUSTOMREQUEST => 'POST',
             CURLOPT_POSTFIELDS => json_encode([
                   "accountNo" => $stk
               ]),
             CURLOPT_HTTPHEADER => array(
                   'access-token: '.$token,
                   'content-type: application/json'
              ) ,
          ));
       $result = curl_exec($curl);
       curl_close($curl);

        $result = json_decode($result, true);
        Print_r($result);
        foreach ($result['data'] as $data) {
            $tid            = check_string($data['refNo']);
            $description    = check_string($data['description']);
            $amount         = check_string($data['creditAmount']);
            $user_id        = parse_order_id($description, $CMSNT->site('prefix_autobank'));         // TÁCH NỘI DUNG CHUYỂN TIỀN
            // XỬ LÝ AUTO SERVER 2
            if($CMSNT->site('sv2_autobank') == 1 && checkAddon(24) == true){
                if($getUser = $CMSNT->get_row(" SELECT * FROM `users` WHERE `id` = '$user_id' ")){
                    if($CMSNT->num_rows(" SELECT * FROM `server2_autobank` WHERE `tid` = '$tid' AND `description` = '$description'  ") == 0){
                        $insertSv2 = $CMSNT->insert("server2_autobank", array(
                            'tid'               => $tid,
                            'user_id'           => $getUser['id'],
                            'description'       => $description,
                            'amount'            => $amount,
                            'received'          => checkPromotion($amount),
                            'create_gettime'    => gettime(),
                            'create_time'       => time()
                        ));
                        if ($insertSv2){
                            $received = checkPromotion($amount);
                            $isCong = $user->AddCredits($getUser['id'], $received, "Nạp tiền tự động qua MBBank (#$tid - $description - $amount)");
                            if($isCong){
                                /** SEND NOTI CHO ADMIN */
                                $my_text = $CMSNT->site('naptien_notification');
                                $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                                $my_text = str_replace('{username}', $getUser['username'], $my_text);
                                $my_text = str_replace('{method}', 'MBBank - Server 2', $my_text);
                                $my_text = str_replace('{amount}', format_cash($amount), $my_text);
                                $my_text = str_replace('{price}', format_currency($received), $my_text);
                                $my_text = str_replace('{time}', gettime(), $my_text);
                                sendMessAdmin($my_text);
                                echo '[<b style="color:green">-</b>] Xử lý thành công 1 hoá đơn.'.PHP_EOL;
                            }
                        }
                    }
                }
            }
            // XỬ LÝ AUTO SERVER 1
            if($CMSNT->num_rows(" SELECT * FROM `invoices` WHERE `description` = '$description' AND `tid` = '$tid' ") > 0){
                continue;
            }
            if($CMSNT->num_rows(" SELECT * FROM `server2_autobank` WHERE `tid` = '$tid' AND `description` = '$description' ") > 0){
                continue;
            }
            foreach (whereInvoicePending('MBBank', $amount) as $row) {
                if($row['description'] == $description && $row['tid'] == $tid){
                    continue;
                }
                if (isset(explode($row['trans_id'], strtoupper($description))[1])) {
                    $isUpdate = $CMSNT->update("invoices", [
                        'status'        => 1,
                        'description'   => $description,
                        'tid'           => $tid,
                        'update_date'   => gettime(),
                        'update_time'   => time()
                    ], " `id` = '".$row['id']."' AND `status` = 0 ");
                    if($isUpdate){
                        $isCong = $user->AddCredits($row['user_id'], $row['amount'], "Thanh toán hoá đơn nạp tiền #".$row['trans_id']);
                        if (!$isCong) {
                            $CMSNT->update("invoices", [
                            'status'  => 0
                            ], " `id` = '".$row['id']."' ");
                        }
                        /** SEND NOTI CHO ADMIN */
                        $my_text = $CMSNT->site('naptien_notification');
                        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                        $my_text = str_replace('{username}', getRowRealtime('users', $row['user_id'], 'username'), $my_text);
                        $my_text = str_replace('{method}', 'MBBank - Server 1', $my_text);
                        $my_text = str_replace('{amount}', format_cash($row['pay']), $my_text);
                        $my_text = str_replace('{price}', format_currency($row['amount']), $my_text);
                        $my_text = str_replace('{time}', gettime(), $my_text);
                        sendMessAdmin($my_text);
                        echo '[<b style="color:green">-</b>] Xử lý thành công 1 hoá đơn.'.PHP_EOL;
                    }
                    break;
                }
            }
        }
        die();
