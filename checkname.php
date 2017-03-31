<?PHP
  define('CONFIG',true);
  include 'config.php';
  header("Content-Type: text/json");

  $admin = $my_code;
  $admin_c = $my_code_c;

  $return=array("decks"=>array(),"game"=>0,"name"=>"","return"=>"error");

  if($admin){
    $return['game'] = $my_c['game'];
    $return['name'] = $my_name;
    $return['return'] = "success";
  }

  $decks = $link->query("SELECT * FROM `totd_decks` WHERE `active` = '1'");
  while($deck = $decks->fetch_array()){
    $card_count = $link->query("SELECT * FROM `totd_deck_cards` WHERE `deck` = '".$deck['code']."'");
    $card_count = $card_count->num_rows;
    array_push($return['decks'],array("cards"=>$card_count,"code"=>$deck['code'],"name"=>$deck['title']));
  }

  die(json_encode($return));