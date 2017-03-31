<?PHP
  define('CONFIG',true);
  include 'config.php';
  header("Content-Type: text/json");

  $admin = $my_code;

  $return=array(
    "return" => "error"
  );

  if(!$admin) die(json_encode($return));

  $games = $link->query("SELECT * FROM `totd_auth` WHERE `user` = '".$admin."' AND `spectate` IS NULL");
  while($game = $games->fetch_array()) $link->query("INSERT INTO `totd_game_log` (`id`,`game`,`user`,`type`,`timestamp`) VALUES (NULL,'".$game['game']."','".$my_name."','eliminated',".time().")");
  $link->query("DELETE FROM `totd_auth` WHERE `user` = '".$admin."'");
  $link->query("UPDATE `totd_games` SET `ended` = '1', `ended_reason` = 'admin_left' WHERE `admin` = '".$admin."' AND `ended` IS NULL");

  $return['return']="success";
  die(json_encode($return));
