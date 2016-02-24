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
  die("Error: Please supply type name and region ID.");

//Just for making sure nobody messes up our database
$name=$mysqli->real_escape_string($_GET["typename"]);
$region_id=$mysqli->real_escape_string($_GET["regionid"]); //Gotta love PHP

//Get Type ID of blueprint
$query = "SELECT id FROM typeids WHERE name='$name Blueprint'";
$result = $mysqli->query($query);
if(!$result)
  die("Database Error: ".$mysqli->error());
$type_id = $result->fetch_array(MYSQLI_ASSOC)["id"];

//Get blueprint data
$query = "SELECT * FROM blueprints WHERE id=$type_id";
$result = $mysqli->query($query);
if(!$result)
  die("Database Error: ".$mysqli->error());

$arrBlueprint = $result->fetch_array(MYSQLI_ASSOC);
$arrMaterials = unserialize(base64_decode($arrBlueprint["materials"]));
$arrProducts = unserialize(base64_decode($arrBlueprint["products"]));

//Get actual Type ID
$query = "SELECT id FROM typeids WHERE name='$name'";
$result = $mysqli->query($query);
if(!$result)
  die("Database Error: ".$mysqli->error());
$type_id = $result->fetch_array(MYSQLI_ASSOC)["id"];

$return_array = array("name" => $name,
  "typeID" => $type_id,
  "price" => get_price($region_id, $type_id, "sell", FLOOR),
  "mats" => array());

//fill mats array
foreach($arrMaterials as $material) {
  $query = "SELECT name FROM typeids WHERE id=$material[typeID]";
  $result = $mysqli->query($query);
  if(!$result)
    die("Database Error: ".$mysqli->error());
  $mat_name = $result->fetch_array(MYSQLI_ASSOC)["name"];

  $return_array["mats"][] = array("name" => $mat_name, "typeID" => $material["typeID"], "price" => get_price($region_id, $material["typeID"], "buy", CEILING), "quantity" => $material["quantity"]);
}

//Return Array
echo json_encode($return_array);

//returns lowest or highest ask/bid price for specified Item
function get_price($region, $type, $action, $direction) {
  $request_url="https://public-crest.eveonline.com/market/$region/orders/$action/?type=https://public-crest.eveonline.com/types/$type/";

  $ret = ($direction == FLOOR) ? INF:0;

  $orders = json_decode(file_get_contents($request_url), true);
  foreach($orders["items"] as $item) {
    if($direction == FLOOR && $item["price"] < $ret)
      $ret = $item["price"];
    elseif($direction == CEILING && $item["price"] > $ret)
      $ret = $item["price"];
  }

  return ($ret == INF)?0:$ret;
}

?>
