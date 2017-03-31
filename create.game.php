<?PHP
  define('CONFIG',true);
  include 'config.php';
  header("Content-Type: text/json");

  $return = array(
    "error" => true,
    "game" => 0,
    "message" => "An error occurred."
  );

  $create = false;

  $admin = $my_code;

  if(!isset($_POST['public'])) $_POST['public'] = '';
  if(!isset($_POST['protected'])) $_POST['protected'] = '';
  if(!isset($_POST['password'])) $_POST['password'] = '';

  $public = true;
  $password = '';
  if(!$_POST['public'])  $public = false;
  if(!$public && $_POST['protected'] && strlen($_POST['password'])) $password = $_POST['password'];

  if($admin && $_POST['players'] && $_POST['players']>=2 && $_POST['players']<=8 && is_numeric($_POST['players']) && $_POST['spectators']>=0 && $_POST['spectators']<=5 && is_numeric($_POST['spectators']) && $_POST['deck'] && !preg_match('/[^a-z_\-0-9]/i',$_POST['deck'])){
    $valid_deck = false;

    $deck_check = $link->query("SELECT * FROM `totd_decks` WHERE `code` = '".$_POST['deck']."' AND `active` = '1'");
    if($deck_check->num_rows > 0) $valid_deck = true;

    if($valid_deck){
      $link->query("INSERT INTO `ids` (`id`) VALUES (NULL)");
      $game_id = randomString(3) . $link->insert_id . randomString(3);
      $return = array("error"=>false,"game"=>$game_id,"message"=>"");
      $create = true;
    }
  }

  if($create){
    $deck_info = $deck_check->fetch_array();
    $link->query("INSERT INTO `totd_games` (`id`,`game_id`,`admin`,`player_limit`,`spectator_limit`,`deck`,`public`,`protected`,`password`,`admin_active`,`ended`) VALUES (NULL,'".$game_id."','".$admin."','".$_POST['players']."','".$_POST['spectators']."','".$deck_info['code'].";".$deck_info['title']."',".($public?'"1"':'NULL').",".(strlen($password)?'"1"':'NULL').",'".password_hash($password,PASSWORD_DEFAULT)."',".time().",NULL);");
  }

  die(json_encode($return));
?>
