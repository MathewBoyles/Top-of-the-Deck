<?PHP
  define('CONFIG',true);
  include 'config.php';
  header("Content-Type: text/json");

  $return = array(
    "auth" => array(
      "code" => "",
      "id" => 0,
      "spectate" => 0
    ),
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
      "return" => "user"
    );
    die(json_encode($return));
  }
  $spectate = false;
  if($_POST['spectate']=='1') $spectate = true;
  $return['auth']['spectate'] = $spectate?'1':'0';

  if(strlen($_POST['code'])<=40 && !preg_match('/[^a-z_\-0-9]/i',$_POST['code'])){

    $player_count = 2;
    $spectator_limit = 1;

    if($game = $link->query("SELECT * FROM `totd_games` WHERE `game_id` = '".$_POST['game']."'")){
      $game = $game->fetch_array();
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
            "return" => "success"
          );
        }
      }
      if($return['auth']['id']) $return=$return;
      else if(!$game['id']) $return['return']='none';
      else if($game['ended']) $return['return']='ended';
      else if($game['admin']==$admin) $return['return']='success';
      else if($game['protected']) $return['return']='password';
      else if($game['player_limit']<=$player_count && !$spectate) $return['return']='full';
      else if($game['spectator_limit']<=$spectator_limit && $spectate) $return['return']='full';
      else $return['return']='success';
    }
    if($return['return']=='success' && !$return['auth']['id']){
      $link->query("INSERT INTO `ids` (`id`) VALUES (NULL)");
      $auth_code = randomString(36) . $link->insert_id . randomString(36);
      $link->query("INSERT INTO `totd_auth` (`id`,`user`,`game`,`code`,`spectate`,`active`) VALUES (NULL,'".$admin."','".$game['game_id']."','".$auth_code."',".($spectate?'"1"':'NULL').",".time().");");
      $return['auth']['code'] = $auth_code;
      $return['auth']['id'] = $game['game_id'];
    }
  }

  die(json_encode($return));
?>