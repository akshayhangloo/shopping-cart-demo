<?php
session_start();
include_once("config.php");

//return here if cart is empty
if(empty($_SESSION["cart_item"])) {
    echo $json_response = json_encode(array("error" => "Cart Is Empty"));
    return;
}

$data = '';
$order_id = uniqid();

$options = array( 
    'http' => array(
        'header'  => 'Authorization: Bearer '.$API_KEY,
        'method'  => 'POST',
        'content' => $data
    )   
);  

//Generate new address for this invoice
$context = stream_context_create($options);
$contents = file_get_contents($NEW_ADDRESS_URL, false, $context);
$new_address = json_decode($contents);

//Getting price
$options = array( 'http' => array( 'method'  => 'GET') );  
$context = stream_context_create($options);
$contents = file_get_contents($PRICE_URL, false, $context);
$price = json_decode($contents);

$total_cost = 0;
foreach($_SESSION["cart_item"] as $key => $value) {
    $current_cart[] = $value;
    $total_cost += ($value["price"]*$value["quantity"]);
}

//Total Cart value in bits
$bits = intval(100000000.0*$total_cost/$price->price);

$db_conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$cart_string = json_encode($current_cart);
$current_time = time();
$query="INSERT INTO order_table (order_id, timestamp,  addr, txid, status, cart, value, bits, bits_payed) VALUES 
    ('".$order_id."','".$current_time."','".$new_address->address."', '', -1, '".$cart_string."', '".$total_cost."','".$bits."', 0)";
$result = $db_conn->query($query) or die($db_conn->error.__LINE__);

//Clear current cart
unset($_SESSION["cart_item"]);
echo json_encode(array("order_id" => $order_id));

?>
