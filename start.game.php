<?PHP
  define('CONFIG',true);
  include 'config.php';
  header("Content-Type: text/json");

  $admin = $my_code;

  $return=array(
    "return" => "error"
  );

  if(!$admin || preg_match('/[^a-z_\-0-9]/i',$_POST['game']) || strlen($_POST['game'])>32) die(json_encode($return));

  if($game = $link->query("SELECT * FROM `totd_games` WHERE `game_id` = '".$_POST['game']."' AND `admin` = '".$admin."' AND `started` IS NULL AND `ended` IS NULL")){
    $game = $game->fetch_array();
    $deck = $game['deck'];
    $deck = explode(";",$game['deck']);
    $deck = $deck[0];
    $player_array = array();
    $players = $link->query("SELECT * FROM `totd_auth` WHERE `game` = '".$game['game_id']."' AND `spectate` IS NULL");
    $player_count = $players->num_rows;
    if($player_count<2){
      $return['return'] = "players";
      die(json_encode($return));
    }
    while($player = $players->fetch_array()) array_push($player_array,$player['user']);
    $cards = $link->query("SELECT * FROM `totd_deck_cards` WHERE `deck` = '".$deck."' AND `active` = '1'");
    $cards = $cards->num_rows;
    $card_count = floor($cards / $player_count);
    $cards_count = $card_count * $player_count;
    $current_p_count = 0;
    $current_c_count = 0;
    $cards = $link->query("SELECT * FROM `totd_deck_cards` WHERE `deck` = '".$deck."' AND `active` = '1' ORDER BY RAND() LIMIT ".$cards_count);
    while($card = $cards->fetch_array()){
      $current_c_count++;
      $link->query("INSERT INTO `totd_game_cards` (`id`,`user`,`game`,`card`,`current`,`played`) VALUES (NULL,'".$player_array[$current_p_count]."','".$game['game_id']."',".$card['id'].",NULL,NULL);");
      if($current_c_count==$card_count){
        $current_c_count=0;
        $current_p_count++;
      }
    }
    foreach($player_array as $player) $link->query("UPDATE `totd_game_cards` SET `current` = '1' WHERE `user` = '".$player."' AND `game` = '".$game['game_id']."' ORDER BY RAND() LIMIT 3");
    $link->query("UPDATE `totd_auth` SET `current_turn` = '1', `turn_start` = ".time()." WHERE `game` = '".$game['game_id']."' ORDER BY RAND() LIMIT 1");
    $link->query("UPDATE `totd_games` SET `started` = '1' WHERE `id` = ".$game['id']);
    $return['return'] = "success";
  }
  die(json_encode($return));