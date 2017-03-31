<?PHP
  define('CONFIG',true);
  include 'config.php';
  header("Content-Type: text/json");

  $return=array();

  if($my_code) $link->query("UPDATE `totd_names` SET `active` = '".time()."' WHERE `code` = '".$my_code."'");

  die(json_encode($return));