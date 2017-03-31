<?PHP
  define('CONFIG',true);
  include 'config.php';
  header("Content-Type: text/json");

  $admin = $my_code;
  $admin_c = $my_code_c;

  $return=array("name"=>"","return"=>"error");

  if(preg_match("/[a-z0-9_]+/i",$_POST['name']) && strlen($_POST['name'])>=3 && strlen($_POST['name'])<=16){
    $available =  $link->query("SELECT * FROM `totd_names` WHERE `name` = '".$_POST['name']."' AND `code` != '".$admin."'");
    if(!$available->num_rows){
      if($admin) $link->query("UPDATE `totd_names` SET `name` = '".$_POST['name']."', `active` = '".time()."' WHERE `code` = '".$my_code."'");
      else $link->query("INSERT INTO `totd_names` (`id`,`code`,`name`,`active`) VALUES (NULL,'".$admin_c."','".$_POST['name']."',".time().");");
      $return['name'] = $_POST['name'];
      $return['return'] = 'success';
    }
  }

  die(json_encode($return));