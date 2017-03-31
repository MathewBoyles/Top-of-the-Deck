<?PHP
  header("Access-Control-Allow-Origin: http://192.168.1.8");
  header("Access-Control-Allow-Credentials: true");

  if(!defined('CONFIG')){
    header("HTTP/1.0 403 Forbidden");
    header("Content-Type: text/plain");
    die('Access Denied');
  }

  session_name('SESSION_USER');
  session_start();
  $link = mysqli_connect("localhost", "totd_user", "e]85&J(p6Z'dQ-WM25", "primary_db");

  if(isset($_COOKIE['SESSION_USER'])) $my_code = $_COOKIE['SESSION_USER']; else $my_code = "";

  $my_code_c = $my_code;

  $offline_users = $link->query("SELECT * FROM `totd_names` WHERE `active` < ".(time()-20));
  while($offline_user = $offline_users->fetch_array()){
    $link->query("UPDATE `totd_games` SET `ended` = '1', `ended_reason` = 'admin_left' WHERE `admin` = '".$offline_user['code']."'");
    if($offline_user_ingames = $link->query("SELECT * `totd_auth` WHERE `spectate` IS NULL AND `user` = '".$offline_user['code']."'")){
      while($offline_user_ingame = $offline_user_ingames->fetch_array()) $link->query("INSERT INTO `totd_game_log` (`id`,`game`,`user`,`type`,`timestamp`) VALUES (NULL,'".$offline_user_ingame['game']."','".$offline_user['name']."','eliminated',".time().")");
    }
    $link->query("DELETE FROM `totd_auth` WHERE `code` = '".$offline_user['code']."'");
    $link->query("DELETE FROM `totd_names` WHERE `code` = '".$offline_user['code']."'");
  }
  $link->query("UPDATE `totd_games` SET `ended` = '1', `ended_reason` = 'admin_left' WHERE `admin_active`  < ".(time()-30));
  $link->query("DELETE FROM `totd_auth` WHERE `active` < ".(time()-30));

  $my_c = $link->query("SELECT * FROM `totd_names` WHERE `code` = '".$my_code."'");
  $my_c = $my_c->fetch_array();
  if($my_c['id']) $my_name = $my_c['name']; else $my_code = false;

  function randomString($length=10){
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }

?>
