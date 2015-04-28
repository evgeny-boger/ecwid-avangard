<?php
error_reporting(E_ALL);
@session_start();

//var_dump($_SESSION);

$x_login = 'avangard_gateway';
$hash_value = '----------';
$url = "http://app.ecwid.com/authorizenet/-----";

$shop_url = "http://contactless.ru/store";


if (!empty($_SESSION)) $session = &$_SESSION;
else $session = &$HTTP_SESSION_VARS;


if ( ! isset($session['avangard_order'])) {
    header("Location: $shop_url");
//    print("<h1> Error 0 </h1>");
    exit(1);
};


$x_response_reason_text = "";
if ( $session['avangard_order']['ok_code'] == $_GET['result_code'] ) {
    // x_response_code must = 1 for the cart to update with an approved sale, your script should determine this before hand
    $x_response_code = '1';
    // x_response_reason_code must = 1  for the cart to update with an approved sale, your script should determine this before hand
    $x_response_reason_code = '1';

} else {
    $x_response_code = '2';
    $x_response_reason_code = '2';
    $x_response_reason_text = '<div class="avangard-gate-error-msg">Оплата не проведена: <br/> Отказ банка – эмитента карты. </br>Ошибка в процессе оплаты, указаны неверные данные карты.</div>';

}



// your script needs to obtain the banks reference number and put that into this variable
// change banks reference to the reference from the merchant
$x_trans_id = $session['avangard_order']['ticket'];

// your script needs to supply the original invoice number that you requested from ecwid at the beginning of the process
// change invoice number to the original number you received
$x_invoice_num = $session['avangard_order']['order_id'];

// change total paid to the total paid. Please note it must match the original total that ecwid sent at the beginning
$x_amount = $session['avangard_order']['amount'];

// Do not change anything below this line, other than your_store_id_#

$string = $hash_value.$x_login.$x_trans_id.$x_amount;
$x_MD5_Hash = md5($string);
$datatopost = array (
"x_response_code" => $x_response_code,
"x_response_reason_code" => $x_response_reason_code,
"x_response_reason_text" => $x_response_reason_text,
"x_trans_id" => $x_trans_id,
"x_invoice_num" => $x_invoice_num,
"x_amount" => $x_amount,
"x_MD5_Hash" => $x_MD5_Hash,
);


$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $datatopost);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);


unset($session['avangard_order'])
?>

<?php echo $response; ?><br />
