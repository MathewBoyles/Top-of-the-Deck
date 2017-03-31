<?PHP
  define('CONFIG',true);
  include 'config.php';
  header("Content-Type: text/json");
  if(!isset($_POST['password'])) $_POST['password']='';
  $return = array(
    "auth" => array(
      "code" => "",
      "id" => 0,
      "spectate" => 0
    ),
    "haspassword" => false,
    "return" => "none"
  );
  $admin = $my_code;
  if(!$admin){
    $return = array(
      "auth" => array(
        "code" => "",
        "id" => 0,
        "spectate" => 0
      ),
      "haspassword" => false,
      "return" => "user"
    );
    die(json_encode($return));
  }
  $link->query("UPDATE `totd_names` SET `game` = NULL WHERE `code` = '".$admin."'");
  $spectate = false;
  if($_POST['spectate']=='1') $spectate = true;
  $return['auth']['spectate'] = $spectate?'1':'0';
  $return['haspassword'] = strlen($_POST['password'])>0;
  if(strlen($_POST['game'])<=40 && !preg_match('/[^a-z_\-0-9]/i',$_POST['game'])){
    $player_count = $link->query("SELECT * FROM `totd_auth` WHERE `game` = '".$_POST['game']."' AND `spectate` IS NULL")->num_rows;
    $spectator_limit = $link->query("SELECT * FROM `totd_auth` WHERE `game` = '".$_POST['game']."' AND `spectate` = '1'")->num_rows;
    if($game = $link->query("SELECT * FROM `totd_games` WHERE `game_id` = '".$_POST['game']."'")){
      $game = $game->fetch_array();
      $return['auth']['id'] = $game['game_id'];
      if($auth = $link->query("SELECT * FROM `totd_auth` WHERE `user` = '".$admin."' && `game` = '".$game['game_id']."'")){
        $auth = $auth->fetch_array();
        if($auth['id'] && (($auth['spectate'] && !$spectate) || (!$auth['spectate'] && $spectate))){
          $link->query("DELETE FROM `totd_auth` WHERE `id` = ".$auth['id']);
          $auth['id'] = false;
        }
        if($auth['id']){
          $return = array(
            "auth" => array(
              "code" => $auth['code'],
              "id" => $game['game_id'],
              "spectate" => $spectate?'1':'0'
            ),
            "haspassword" => strlen($_POST['password'])>0,
            "return" => "success"
          );
        }
      }
      if($return['auth']['code']) $return=$return;
      else if(!$game['id']) $return['return']='none';
      else if($game['ended']) $return['return']='ended';
      else if($game['started'] && !$spectate) $return['return']='started';
      else if($game['admin']==$admin) $return['return']='success';
      else if($game['protected']){
        if(strlen($_POST['password'])){
          $return['return']=password_verify($_POST['password'],$game['password'])?'success':'password';
        }else $return['return']='password';
      }
      else if($game['player_limit']<=$player_count && !$spectate) $return['return']='full';
      else if($game['spectator_limit']<=$spectator_limit && $spectate) $return['return']='full';
      else $return['return']='success';
    }
    if($return['return']=='success' && !$return['auth']['code']){
      $link->query("UPDATE `totd_names` SET `game` = '".$game['game_id']."' WHERE `code` = '".$admin."'");
      $link->query("INSERT INTO `ids` (`id`) VALUES (NULL)");
      $auth_code = randomString(36) . $link->insert_id . randomString(36);
      $link->query("INSERT INTO `totd_auth` (`id`,`user`,`game`,`code`,`spectate`,`active`) VALUES (NULL,'".$admin."','".$game['game_id']."','".$auth_code."',".($spectate?'"1"':'NULL').",".time().");");
      $return['auth']['code'] = $auth_code;
      $return['auth']['id'] = $game['game_id'];
    }
  }
  die(json_encode($return));
?>