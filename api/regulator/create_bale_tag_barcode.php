<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");
header("Access-Control-Allow-Origin-Methods:POST");
header("Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,Access-Control-Allow-Origin-Methods,Authorization,X-Requested-With");

require_once("conn.php");
require "validate.php";
require_once("vendor/autoload.php");


$data = json_decode(file_get_contents("php://input"));


$code="";
$seasonid=0;
$description="";
$growerid=0;
$grower_bales=0;
$data1=array();

//userid=1&name="bright"&surname="kaponda"&grower_num="12333"&area="ggg"&province="tttt"&phone="0784428797"&id_num="12345666"&created_at="44-44-44"&lat="12.2223"&long="15.45555"

if (isset($data->code)){

$code=$data->code;
$barcodes='.../images/'.$code.'.png';


$color=[255,255,0];
$generator=new Picqer\Barcode\BarcodeGeneratorPNG();
$image=file_put_contents("$barcodes",$generator->getBarcode($code,$generator::TYPE_CODE_128,3,50,$color));

$temp=array("response"=>$barcodes);
array_push($data1,$temp);

}



echo json_encode($data1);


?>