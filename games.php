<?PHP
  define('CONFIG',true);
  include 'config.php';
  header("Content-Type: text/json");

  $return = array(
    "games" => array(),
    "return" => "none"
  );
  $admin = $my_code;

  if(!$admin){
    $return = array(
      "games" => array(),
      "return" => "user"
    );
    die(json_encode($return));
  }

  $link->query("UPDATE `totd_names` SET `active` = '".time()."' WHERE `code` = '".$my_code."'");

  $games = array();
  $get_games = $link->query("SELECT * FROM `totd_games` WHERE `public` = '1' AND `started` IS NULL AND `ended` IS NULL");
  while($game = $get_games->fetch_array()){
    $game_name = $link->query("SELECT * FROM `totd_names` WHERE `code` = '".$game['admin']."'");
    $game_name = $game_name->fetch_array();
    $game_name = $game_name['name'].'\'s game';
    $players = $link->query("SELECT * FROM `totd_auth` WHERE `spectate` IS NULL AND `active` >= ".(time()-30))->num_rows;
    $spectators = $link->query("SELECT * FROM `totd_auth` WHERE `spectate` = '1' AND `active` >= ".(time()-30))->num_rows;
    array_push($games,array(
      "decks" => (explode(";",$game['deck'])[1]),
      "id" => $game['game_id'],
      "name" => $game_name,
      "players" => $players,
      "playerlimit" => $game['player_limit'],
      "spectators" => $spectators,
      "spectatorlimit" => $game['spectator_limit'],
      "status" => "Ready"
    ));
  }

  $return = array(
    "games" => $games,
    "return" => "success"
  );

  if(!$get_games->num_rows) $return['return'] = "none";

  die(json_encode($return));
?>
