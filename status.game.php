<?PHP
  define('CONFIG',true);
  include 'config.php';
  header("Content-Type: text/json");

  $admin = $my_code;

  $return=array(
    "alerts" => array(),
    "attr" => 0,
    "cards" => array(),
    "eliminated" => false,
    "ended" => false,
    "mycards" => array(),
    "mycards_left" => 0,
    "players" => array(),
    "return" => "error",
    "started" => false,
    "spectators" => array(),
    "turn" => "",
    "waiting" => false,
    "won" => ""
  );

  if(!$admin || preg_match('/[^a-z_\-0-9]/i',$_POST['game']) || strlen($_POST['game'])>32) die(json_encode($return));

  $link->query("UPDATE `totd_names` SET `active` = '".(time())."' WHERE `code` = '".$my_code."'");

  if($auth = $link->query("SELECT * FROM `totd_auth` WHERE `game` = '".$_POST['game']."' AND `user` = '".$admin."'")){
    $auth = $auth->fetch_array();
    if($auth['id']){
      $link->query("UPDATE `totd_auth` SET `active` = ".time()." WHERE `id` = ".$auth['id']);
      $game = $link->query("SELECT * FROM `totd_games` WHERE `game_id` = '".$auth['game']."'");
      $game = $game->fetch_array();
      if($game['admin']==$admin) $link->query("UPDATE `totd_games` SET `admin_active` = ".time()." WHERE `id` = ".$game['id']);
      $won_by = "";
      if($game['ended'] && $game['ended_reason']=='won'){
        if($game['won_by']==$admin) $won_by = "you";
        else {
          $won_by = $link->query("SELECT * FROM `totd_names` WHERE `code` = '".$game['won_by']."'");
          $won_by = $won_by->fetch_array();
          $won_by = $won_by['name'];
        }
      }
      if($game['started'] && !$auth['spectate']){
        $card_count = $link->query("SELECT * FROM `totd_game_cards` WHERE `user` = '".$admin."' AND `game` = '".$game['game_id']."'")->num_rows;
        $auth['eliminated'] = $card_count==0;
      }else $card_count = 0;
      if($return['eliminated']) $link->query("INSERT INTO `totd_game_log` (`id`,`game`,`user`,`type`,`timestamp`) VALUES (NULL,'".$game['game_id']."','".$my_name."','eliminated',".time().")");
      $return=array(
        "alerts" => array(),
        "attr" => $game['attr'],
        "cards" => array(),
        "eliminated" => (!$auth['spectate'] && $auth['eliminated']),
        "ended" => !!$game['ended'],
        "mycards" => array(),
        "mycards_left" => 0,
        "players" => array(),
        "return" => "success",
        "started" => ($game['started']?true:($game['admin']==$admin?'ready':false)),
        "spectators" => array(),
        "turn" => "",
        "waiting" => false,
        "won" => $won_by
      );

      $g_players = array();

      if($players = $link->query("SELECT * FROM `totd_auth` WHERE `game` = '".$game['game_id']."' AND `spectate` IS NULL")){
        while($player = $players->fetch_array()){
          $add = $link->query("SELECT * FROM `totd_names` WHERE `code` = '".$player['user']."'");
          $add = $add->fetch_array();
          $g_players[$add['code']] = $add['name'];
          $t_array = array();
          $t_array['cards'] = $link->query("SELECT * FROM `totd_game_cards` WHERE `user` = '".$add['code']."' AND `game` = '".$game['game_id']."'")->num_rows;
          $t_array['name'] = $add['name'];
          $t_array['played'] = false;
          $return['players'][$add['name']] = $t_array;
        }
      }

      if(!$auth['spectate'] && $auth['eliminated']) $link->query("UPDATE `totd_auth` SET `spectate` = '1' WHERE `id` = ".$auth['id']);
      if($auth['spectate'] || !$auth['eliminated']){
        $deck = $game['deck'];
        $deck = explode(";",$deck);
        $deck = $deck[0];

        $all_played = true;
        $i_played = false;

        foreach($g_players as $player_code => $player_name){
          $has_played = ($link->query("SELECT * FROM `totd_game_cards` WHERE `user` = '".$player_code."' AND `game` = '".$game['game_id']."' AND `played` = '1'")->num_rows) > 0;
          if($player_code == $admin) $i_played = $has_played;
          if(!$has_played) $all_played = false;
          $return['players'][$player_name]['played'] = $has_played;
        }
        $return['waiting'] = !$i_played;
        $high_cards = array(array());
        $current_high = 0;
        if($all_played || $i_played || $auth['spectate']=='1'){
          $my_cards = $link->query("SELECT * FROM `totd_game_cards` WHERE `played` = '1' AND `game` = '".$game['game_id']."'");
          while($my_card = $my_cards->fetch_array()){
            $card_info = $link->query("SELECT * FROM `totd_deck_cards` WHERE `id` = ".$my_card['card']);
            $card_info = $card_info->fetch_array();
            $card_array = array();
            $card_array['attr'] = array();
            $card_array['attr'][$card_info['a_1']] = $card_info['v_1'];
            $card_array['attr'][$card_info['a_2']] = $card_info['v_2'];
            $card_array['attr'][$card_info['a_3']] = $card_info['v_3'];
            $card_array['attr'][$card_info['a_4']] = $card_info['v_4'];
            $card_array['attr'][$card_info['a_5']] = $card_info['v_5'];
            $card_array['image'] = "images/decks/".$deck."/".$card_info['image'];
            $card_array['name'] = $card_info['name'];
            $card_array['player'] = (!$all_played)?"Waiting for all players to select...":($g_players[$my_card['user']]."'s card");
            array_push($return['cards'],$card_array);
            $add_card = array();
            $add_card['card'] = $card_info['id'];
            $add_card['player'] = $my_card['user'];

            if($card_info['v_'.$game['attr']] > $current_high){
              $current_high = $card_info['v_'.$game['attr']];
              $high_cards = array();
              array_push($high_cards,$add_card);
            }
            else if($card_info['v_'.$game['attr']] == $current_high){
              $current_high = $card_info['v_'.$game['attr']];
              array_push($high_cards,$add_card);
            }
          }
        }
        if($all_played){
          $round = array();
          foreach($high_cards as $card_info_ => $card_info){
            array_push($round,$g_players[$card_info['player']]);
          }
          $link->query("INSERT INTO `totd_game_log` (`id`,`game`,`user`,`type`,`timestamp`) VALUES (NULL,'".$game['game_id']."','".implode(", ",$round)."','round',".time().")");
          $link->query("INSERT INTO `totd_game_log` (`id`,`game`,`user`,`type`,`timestamp`) VALUES (NULL,'".$game['game_id']."','".str_replace("'","&#39;",json_encode($return['cards']))."','cards',".time().")");
          if(count($high_cards) > 1){
            foreach($high_cards as $card_info) $link->query("UPDATE `totd_game_cards` SET `current` = NULL, `played` = NULL WHERE `game` = '".$game['game_id']."' AND `user` = '".$card_info['player']."' AND `played` = '1'");
          }else $link->query("UPDATE `totd_game_cards` SET `user` = '".$high_cards[0]['player']."', `current` = NULL, `played` = NULL WHERE `played` = '1' AND `game` = '".$game['game_id']."'");
          $link->query("UPDATE `totd_game_cards` SET `user` = NULL, `played` = NULL, `current` = NULL WHERE `game` = '".$game['game_id']."' AND `played` = '1'");
          $link->query("UPDATE `totd_games` SET `attr` = ".rand(1,5)." WHERE `game_id` = '".$game['game_id']."'");
          foreach($g_players as $player_code => $player_name) $link->query("UPDATE `totd_game_cards` SET `current` = '1' WHERE `user` = '".$player_code."' AND `game` = '".$game['game_id']."' AND `current` IS NULL ORDER BY RAND() LIMIT 1");
        }
        if(!$auth['spectate']){
          $my_cards = $link->query("SELECT * FROM `totd_game_cards` WHERE `user` = '".$admin."' AND `game` = '".$game['game_id']."' AND `current` = '1'");
          while($my_card = $my_cards->fetch_array()){
            $card_info = $link->query("SELECT * FROM `totd_deck_cards` WHERE `id` = ".$my_card['card']);
            $card_info = $card_info->fetch_array();
            $card_array = array();
            $card_array['attr'] = array();
            $card_array['attr'][$card_info['a_1']] = $card_info['v_1'];
            $card_array['attr'][$card_info['a_2']] = $card_info['v_2'];
            $card_array['attr'][$card_info['a_3']] = $card_info['v_3'];
            $card_array['attr'][$card_info['a_4']] = $card_info['v_4'];
            $card_array['attr'][$card_info['a_5']] = $card_info['v_5'];
            $card_array['id'] = $my_card['id'];
            $card_array['image'] = "images/decks/".$deck."/".$card_info['image'];
            $card_array['name'] = $card_info['name'];
            array_push($return['mycards'],$card_array);
          }
          $return['mycards_left'] = $card_count;
        }
      }

      $new_alerts = $link->query("SELECT * FROM `totd_game_log` WHERE `game` = '".$game['game_id']."' AND `timestamp` >= ".($auth['active']));
      while($new_alert = $new_alerts->fetch_array()){
        $msg = "";
        if($new_alert['type']=='eliminated') $msg = array("c"=>"red","i"=>$new_alert['id'],"m"=>($new_alert['user']." has been eliminated!"),"t"=>"Player eliminated");
        if($new_alert['type']=='round') $msg = array("c"=>"green","i"=>$new_alert['id'],"m"=>("Round won by: ".$new_alert['user']."."),"t"=>"Round over");
        if($new_alert['type']=='cards') $msg = array("c"=>"CARDS","i"=>$new_alert['id'],"m"=>json_decode($new_alert['user'],true),"t"=>"CARDS");
        array_push($return['alerts'],$msg);
      }

      if($game['started'] && !$game['ended'] && $players->num_rows==1){
        $link->query("UPDATE `totd_names` SET `game` = NULL WHERE `game` = '".$game['game_id']."'");
        $link->query("UPDATE `totd_games` SET `won_by` = '".$add['code']."', `ended` = '1', `ended_reason` = 'won' WHERE `id` = ".$game['id']);
        if($add['code']==$admin) $return['won'] = "you";
        else {
          $won_by = $link->query("SELECT * FROM `totd_names` WHERE `code` = '".$add['code']."'");
          $won_by = $won_by->fetch_array();
          $won_by = $won_by['name'];
          $return['ended'] = true;
          $return['won'] = $won_by;
        }
      }
      if($players = $link->query("SELECT * FROM `totd_auth` WHERE `game` = '".$game['game_id']."' AND `spectate` = '1'")){
        while($player = $players->fetch_array()){
          $add = $link->query("SELECT * FROM `totd_names` WHERE `code` = '".$player['user']."'");
          $add = $add->fetch_array();
          array_push($return['spectators'],$add['name']);
        }
      }
    }
  }

  die(json_encode($return));
