<?PHP
  define('CONFIG',true);
  include 'config.php';
  header("Content-Type: text/json");

  $return = array();
  $admin = $my_code;

  if(!$admin || preg_match('/[^a-z_\-0-9]/i',$_POST['game']) || strlen($_POST['game'])>32 || !$_POST['card'] || !is_numeric($_POST['card'])) die(json_encode($return));

  if($link->query("SELECT * FROM `totd_game_cards` WHERE `played` = '1' AND `user` = '".$admin."' AND `game` = '".$_POST['game']."'")->num_rows == 0){
    $link->query("UPDATE `totd_game_cards` SET `played` = '1' WHERE `user` = '".$admin."' AND `game` = '".$_POST['game']."' AND `current` = '1' AND `id` = ".$_POST['card']);
  }

  die(json_encode($return));
?>