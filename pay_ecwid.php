<?php
$db = mysql_connect("localhost","----","----");  // Логин и пароль от базы данных
mysql_select_db("----",$db);   //Имя базы данных
 mysql_set_charset('utf8',$db);
?>
<?php
@session_start();
header('Content-Type: text/html;charset=UTF-8');

$ShopID 	= '----';  //Логин
$ShopPasswd = '-----';  //Пароль
$BackURL 	= 'http://example.com/avangard/callback_ecwid.php';   //Адрес возврата

$Error = false;
$Step = 'form';

if (!empty($_POST)) {
	$err = 0;

	if (empty($_POST['x_invoice_num'])) $err++;
	//~ if (empty($_POST['sum'])) $err++;

	if ($err == 0) {
		$Step = 'redirect';

        $order_id = $_POST['x_invoice_num'];
        $amount = $_POST['x_amount'];
        $desc = $_POST['x_description'];
        $email = $_POST['x_email'];
        $phone = $_POST['x_phone'];
        $address = sprintf("%s, %s, %s, %s, %s", $_POST['x_address'], $_POST['x_city'], $_POST['x_state'], $_POST['x_country'], $_POST['x_zip']);
        $fullname = $_POST['x_first_name'] . ' ' . $_POST['x_last_name'];
        $contents = $_POST['x_line_item'];

		$data = requestTicket($order_id,
                              $fullname,
                              $address,
                              $phone,
                              $email,
                              $amount,
                              $desc,
                              $_SERVER['REMOTE_ADDR'] );



        // Сохраняем в mysql

        $sql_query = sprintf("INSERT INTO tickets (order_id, amount, ticket, ok_code, failure_code, description, contents, fullname, phone, email) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                                            mysql_real_escape_string($order_id),
                                            mysql_real_escape_string($amount),
                                            mysql_real_escape_string($data['data']['ticket']),
                                            mysql_real_escape_string($data['data']['ok_code']),
                                            mysql_real_escape_string($data['data']['failure_code']),
                                            mysql_real_escape_string($desc),
                                            mysql_real_escape_string($contents),
                                            mysql_real_escape_string($fullname),
                                            mysql_real_escape_string($phone),
                                            mysql_real_escape_string($email));

        $result = mysql_query ($sql_query);




		header("Location: {$data['url']}");
        print($data['url']);


	} else {
		$Error = 'Не заполнены обязательные поля формы!';
	}
}

function ticketXML($id, $fullname, $address, $phone, $email, $amount, $desc, $ip) {
	global $ShopID, $ShopPasswd, $BackURL;

	$amount = (int) ($amount * 100);

	$xml = "<?xml version=\"1.0\" encoding=\"windows-1251\"?>
<new_order>
<shop_id>$ShopID</shop_id>
<shop_passwd>$ShopPasswd</shop_passwd>
<amount>$amount</amount>
<order_number>$id</order_number>
<order_description>$desc</order_description>
<language>RU</language>
<back_url>$BackURL</back_url>
<client_name>$fullname</client_name>
<client_address>$address</client_address>
<client_email>$email</client_email>
<client_phone>$phone</client_phone>
<client_ip>$ip</client_ip>
</new_order>";
	$xml = iconv('utf-8', 'windows-1251', $xml);

	return $xml;
}

// Запрос тикета
function requestTicket($id, $fullname, $address, $phone, $email, $amount, $desc, $ip) {
	$xml = ticketXML($id, $fullname, $address, $phone, $email, $amount, $desc, $ip);
    file_put_contents("a_test.xml", $xml);


    // новый метод из avangard-запрос тикета.php
    $headers = array
    (
        'Content-type: application/x-www-form-urlencoded;charset=windows-1251',
        'Expect:'
    );

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, 'https://www.avangard.ru/iacq/h2h/reg');
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_POSTFIELDS, "xml=".$xml);

    $resp = curl_exec($curl);
    //~ echo $resp;

    curl_close($curl);


	if ($respXML = simplexml_load_string($resp)) {
		if ($respXML->response_code == 0 && !empty($respXML->ticket)) {
			$url = "https://www.avangard.ru/iacq/pay?ticket={$respXML->ticket}";

			// Сохраняем в сессии
			$data = $_SESSION['avangard_order'] = $HTTP_SESSION_VARS['avangard_order'] = array(
				'form' 			=> $_POST,
				'order_id' 		=> $id,
                'amount'        => $amount,
				'ticket' 		=> (string) $respXML->ticket,
				'ok_code' 		=> (string) $respXML->ok_code,
				'failure_code' 	=> (string) $respXML->failure_code
			);



			return array('url' => $url, 'response' => $resp, 'data' => $data);
		}
	}

	return false;
}
?>

<? if ($Step == 'form'): ?>
	<?=$Error ? "$Error<br>" : ''?>
<h2> Error</h2>

<? endif; ?>

<?php


?>
