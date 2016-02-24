<?php
require_once("config.inc.php");
require_once(getcwd()."/spyc/Spyc.php");

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
foreach($blueprints as $blueprint) {
  $id = $blueprint["blueprintTypeID"];
  //Not the prettiest way to save this type of data, but it suffices in this case
  $manufacturing = $blueprint["activities"]["manufacturing"];
  $materials = base64_encode(serialize($manufacturing["materials"]));
  $products = base64_encode(serialize($manufacturing["products"]));

  $query = "INSERT INTO blueprints(id, materials, products) VALUES($id, '$materials', '$products')";
  $result = $mysqli->query($query);
  if(!$result)
    die("Database Error while importing:".$mysqli->error);
}

//Free memory
unset($blueprints);

//Import typeids
echo "Loading typeIDs...\n";
$typeids = yaml_parse(file_get_contents(getcwd()."/typeids.yaml"));
echo "Importing typeIDs...\n";
foreach($typeids as $id=>$array) {
  $name = $array["name"]["en"];

  $query = "INSERT INTO typeids(id, name) VALUES($id, '$name')";
  $result = $mysqli->query($query);
  if(!$result)
    die("Database Error while importing:".$mysqli->error);
}

echo "All done :)";

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
