<?php
require_once("config.inc.php");

//Evil! I'm really sorry for this.
ini_set('memory_limit', '1024M');

/*
imports data from SDE (typeIDs, blueprints)
*/

if(!is_writable(getcwd()))
  die("Directory not writable, aborting...");

//Database connection
$mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);
if ($mysqli->connect_error) {
  die('Connect Error (' .$mysqli->connect_errno.') '.$mysqli->connect_error);
}

//Create Tables
echo "Creating Database Tables...\n";
$query = "CREATE TABLE IF NOT EXISTS blueprints (id INT NOT NULL, materials TEXT NOT NULL default '', products TEXT NOT NULL default '', PRIMARY KEY (id))";
$result = $mysqli->query($query);
if(!$result)
  die("Database error: ".$mysqli->error);

$query = "CREATE TABLE IF NOT EXISTS typeids (id INT NOT NULL, name VARCHAR(255) NOT NULL default '', PRIMARY KEY (id))";
$result = $mysqli->query($query);
if(!$result)
  die("Database error: ".$mysqli->error);

//Download and unpack sources
echo "Downloading and unpacking sources...\n";
download_and_unpack($src_blueprint, getcwd()."/blueprints.yaml");
download_and_unpack($src_typeIDs, getcwd()."/typeids.yaml");

//Import blueprints
echo "Loading blueprints...\n";
$blueprints = yaml_parse(file_get_contents(getcwd()."/blueprints.yaml"));
echo "Importing blueprints...\n";

$arr_unusable_blueprints=array();

foreach($blueprints as $blueprint) {
  $id = $blueprint["blueprintTypeID"];

  //Appereantly, some blueprints can not be manufactured
  if(!isset($blueprint["activities"]["manufacturing"]) || !isset($blueprint["activities"]["manufacturing"]["materials"]) || !isset($blueprint["activities"]["manufacturing"]["products"])){
    $arr_unusable_blueprints[] = $id;
    continue;
  }

  //Not the prettiest way to save this type of data, but it suffices in this case
  $manufacturing = $blueprint["activities"]["manufacturing"];
  $materials = base64_encode(serialize($manufacturing["materials"]));
  $products = base64_encode(serialize($manufacturing["products"]));

  $query = "INSERT INTO blueprints(id, materials, products) VALUES($id, '$materials', '$products')";
  $result = $mysqli->query($query);
  if(!$result)
    die("Database Error while importing:".$mysqli->error);
}

if(sizeof($arr_unusable_blueprints) > 0)
  echo "Skipped ".sizeof($arr_unusable_blueprints)." blueprints: Missing manufacturing information.\n";

//Free memory
unset($blueprints);

//Import typeids
echo "Loading typeIDs...\n";
$typeids = yaml_parse(file_get_contents(getcwd()."/typeids.yaml"));
echo "Importing typeIDs...\n";

$arr_unusable_typeids = array();

foreach($typeids as $id=>$array) {

  if(!isset($array["name"]["en"])) {
    $arr_unusable_typeids[] = $id;
    continue;
  }

  $name = str_replace("'", "''", $array["name"]["en"]); //Some escaping

  $query = "INSERT INTO typeids(id, name) VALUES($id, '$name')";
  $result = $mysqli->query($query);
  if(!$result)
    die("Database Error while importing: ".$mysqli->error." $query");
}

if(sizeof($arr_unusable_typeids) > 0)
  echo "Skipped ".sizeof($arr_unusable_typeids)." blueprints: Missing manufacturing information.\n";

echo "All done :)\n";

$mysqli->close();

function download_and_unpack($source, $destination) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $source);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $data = curl_exec ($ch);
  $error = curl_error($ch);
  if($error)
    die($error);
  curl_close ($ch);

  $file = fopen($destination, "w+") or die("Could not open $destination. Aborting.");
  $data = bzdecompress($data);
  fputs($file, $data);
  fclose($file);
}

?>
