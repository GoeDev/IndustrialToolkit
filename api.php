<?php
require_once("config.inc.php");

define('FLOOR', -1);
define('CEILING', 1);

//Database connection
$mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);
if ($mysqli->connect_error) {
  die('Connect Error (' .$mysqli->connect_errno.') '.$mysqli->connect_error);
}

//Check URL params
if(!isset($_GET["typename"]) || !isset($_GET["regionid"]))
  die("Error: Please supply type name and region ID like this: /api.php?typename=Warrior%20I&amp;regionid=10000002");

//Just for making sure nobody messes up our database
$name=$mysqli->real_escape_string(str_ireplace("+"," ",$_GET["typename"]));
$region_id=$mysqli->real_escape_string($_GET["regionid"]); //Gotta love PHP

//Get Type ID of blueprint
$query = "SELECT typeID FROM invTypes WHERE typeName='$name Blueprint'";
$result = $mysqli->query($query);
if(!$result)
  die("Database Error: ".$mysqli->error);
if($result->num_rows != 1)
  die("TypeID not found or multiple Items found.");
$blueprint_type_id = $result->fetch_array(MYSQLI_ASSOC)["typeID"];

//Get blueprint product data
$query = "SELECT * FROM industryActivityProducts WHERE typeID=$blueprint_type_id AND activityID=1";
$result = $mysqli->query($query);
if(!$result)
  die("Database Error: ".$mysqli->error);
if($result->num_rows < 1)
  die("Blueprint products not found.");

$arrProducts = $result->fetch_array(MYSQLI_ASSOC);

//Get actual Type ID
$query = "SELECT typeID FROM invTypes WHERE typeName='$name'";
$result = $mysqli->query($query);
if(!$result)
  die("Database Error: ".$mysqli->error);
if($result->num_rows != 1)
  die("TypeID not found or multiple Items found.");
$type_id = $result->fetch_array(MYSQLI_ASSOC)["typeID"];

$return_array = array("name" => $name,
  "typeID" => $type_id,
  "price" => get_price($region_id, $type_id, "sell", FLOOR),
  "quantity" => $arrProducts["quantity"],
  "mats" => array());

//Get blueprint material data
$query = "SELECT * FROM industryActivityMaterials WHERE typeID=$blueprint_type_id AND activityID=1"; //activityID=1 => manufacturing
$result = $mysqli->query($query);
if(!$result)
  die("Database Error: ".$mysqli->error);
if($result->num_rows < 1)
  die("Blueprint materials not found.");

//Because fetch_all doesn't always work
$arrMaterials=array();
while($material = $result->fetch_array()) {
  $arrMaterials[] = $material;
}

//fill mats array
foreach($arrMaterials as $material) {
  $query = "SELECT typeName FROM invTypes WHERE typeID=$material[materialTypeID]";
  $result = $mysqli->query($query);
  if(!$result)
    die("Database Error: ".$mysqli->error);
  if($result->num_rows != 1)
    die("Multiple or no entries for material $material[materialTypeID]");
  $mat_name = $result->fetch_array(MYSQLI_ASSOC)["typeName"];

  $return_array["mats"][] = array("name" => $mat_name, "typeID" => $material["materialTypeID"], "price" => get_price($region_id, $material["materialTypeID"], "buy", CEILING), "quantity" => $material["quantity"]);
}
echo " ,$blueprint_type_id";
//Return Array
echo json_encode($return_array);

//returns lowest or highest ask/bid price for specified Item
function get_price($region, $type, $action, $direction) {
  $request_url="https://public-crest.eveonline.com/market/$region/orders/$action/?type=https://public-crest.eveonline.com/types/$type/";

  $ret = ($direction == FLOOR) ? INF:0;

  $orders = json_decode(file_get_contents($request_url), true);
  foreach($orders["items"] as $item) {
    if(($direction == FLOOR && $item["price"] < $ret) || ($direction == CEILING && $item["price"] > $ret))
      $ret = $item["price"];
  }

  return ($ret == INF)?0:$ret;
}

?>
