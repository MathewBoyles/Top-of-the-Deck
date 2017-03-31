$(document).ready(function(){
  var game_id, done_before;
  var kick = false;
  var kick_to = setTimeout(function(){});
  var alerts_seen = {};
  function _server(url){
    return "/totd/" + url;
  }
  function joinGame(id,is_spectate,password){
    window.history.pushState({}, document.title, window.location.pathname);
    game_id = id;
    msgBox('Joining...','Connecting to game. Please wait.',true,'green');
    $.post({
      url: _server("connect.game.php"),
      type: "post",
      data: {
        game: id,
        password: password,
        spectate: is_spectate?'1':'0'
      },
      success: function(data){
        if(data.return=='user') $('#loadingbox .mdl-button').click(),nickname_box();
        else if(data.return=='none') msgBox('Error','This game does not exist.',false,'red');
        else if(data.return=='ended') msgBox('Game ended','Sorry, this game has ended.',false,'red');
        else if(data.return=='started') msgBox('Game already started','This game has already begun.',false,'red');
        else if(data.return=='password'){
          $('#joingame').data({
            game: data.auth.id,
            spectate: data.auth.spectate
          });
          $('#loadingbox .mdl-button').click();
          if(data.haspassword) $('#passwordbox > .mdl-card .mdl-card__supporting-text p').show(); else $('#passwordbox > .mdl-card .mdl-card__supporting-text p').hide();
          $('#passwordbox').stop().fadeIn(500,function(){
            $('#passwordbox .mdl-card').stop().animate({'top':'60%'},500,function(){
              $('#passwordbox .mdl-card').animate({'top':'50%'},100);
            });
          });
        }
        else if(data.return=='full') msgBox('Game full','Sorry, this game is full. Please try another.',false,'red');
        else if(data.return=='success'){
          joinGame_s2(data.auth);
        }
      }
    });
  }
  function joinGame_s2(auth_data){
    window.history.pushState({}, document.title, '?game='+auth_data['id']+(auth_data['spectate']=='1'?'&spectate=1':''));
    $('html').addClass('in-game');
    gameStatus(true);
  }
  function gameStatus(is_msg){
    if($('html').hasClass('in-game')){
      $.post({
        url: _server("status.game.php"),
        type: "post",
        data: {
          game: game_id
        },
        success: function(data){
          if(is_msg) $('#loadingbox .mdl-button').click();
          if(data.return=='error'){
            msgBox('Error','Unable to connect to game.',false,'red');
            $('#playedcards,#yourcard,#startgame').addClass('hidden');
            $('html').removeClass('in-game');
          }else{
            $('#startgame,#startwait').addClass('hidden');
            if(data.started=='ready' || !data.started){
              if(data.started=='ready') $('#startgame').removeClass('hidden');
              $('#startwait').removeClass('hidden');
            }else{
              $('#playedcards h4:first').html(data.turn);
              $('#playedcards,#yourcard').removeClass('hidden');
            }
            if(data.mycards.length == 0) $('#yourcard').addClass('hidden');
            if(data.ended){
              $('#playedcards,#yourcard,#startgame').addClass('hidden');
              $('html').removeClass('in-game');
              if(data.won=='you') msgBox('Congratulations!','Congratulations, you won the game!',false,'green');
              else if(data.won) msgBox('Game finished',data.won+' won the game!',false);
            }
            else if(data.eliminated==true) msgBox('Game over!','You have been eliminated!',false,'red');
            $('[data-section="playing"],[data-section="spectating"],#playedcards .playedcards').html('');
            $.each(data.alerts,function(id,alert){
              if(!alerts_seen[alert.i]){
                alerts_seen[alert.i] = true;
                if(alert.t=='CARDS') data.cards = alert.m;
                else msgBox(alert.t,alert.m,2000,alert.c);
              }
            });
            $.each(data.players,function(id,player){
              $('[data-section="playing"]').append('<li>'+(player.played?'<i class="material-icons">checked</i> ':'')+player.name+((player.cards==0)?'':(' ('+player.cards+' card'+(player.cards=='1'?'':'s')+' left)'))+'</li>');
            });
            $.each(data.spectators,function(id,player){
              $('[data-section="spectating"]').append('<li>'+player+'</li>');
            });
            if(data.alerts.length > 0) $('.mdl-layout,.mdl-layout__content').animate({scrollTop:0},100);
            if(data.cards.length > 0) $('#playedcards').removeClass('hidden');
            else $('#playedcards').addClass('hidden');
            $.each(data.cards,function(id,card){
              var code = $($('[data-template="playedcard"]').html());
              code.find('[data-card="image"]').css('background-image','url('+card.image+')');
              code.find('[data-card="name"]').html(card.name);
              code.find('[data-card="player"]').html(card.player);
              var id = 0;
              $.each(card.attr,function(attr_name,attr_value){
                code.find('[data-card-attribute]:eq('+(id)+')').find('td:first').html(attr_name);
                code.find('[data-card-attribute]:eq('+(id)+')').find('td:last').html(attr_value);
                id++;
              });
              code.find('.mdl-button').remove();
              $('#playedcards .playedcards').append(code);
            });
            $('#yourcard .playedcards').html('');
            $('#yourcard h4:first span').html(data.mycards_left+' left');
            $.each(data.mycards,function(id,card){
              var code = $($('[data-template="playedcard"]').html());
              code.find('[data-card="image"]').css('background-image','url('+card.image+')');
              code.find('[data-card="name"]').html(card.name);
              code.find('[data-card="player"]').html('');
              var id = 0;
              $.each(card.attr,function(attr_name,attr_value){
                code.find('[data-card-attribute]:eq('+(id)+')').find('td:first').html(attr_name);
                code.find('[data-card-attribute]:eq('+(id)+')').find('td:last').html(attr_value);
                id++;
              });
              code.find('[data-card-attribute]:eq('+(data.attr-1)+')').css('background','#E0E0E0');
              code.find('button').click(function(){
                $('.mdl-layout,.mdl-layout__content').animate({scrollTop:0},100);
                kick = false;
                clearTimeout(kick_ts);
                $.post({
                  url: _server("card.game.php"),
                  type: "post",
                  data: {
                    card: card.id,
                    game: game_id
                  },
                  success: function(data){
                    gameStatus();
                  }
                });
                return false;
              });
              if(!data.waiting) code.find('button').remove();
              $('#yourcard .playedcards').append(code);
            });
            if(!data.waiting && kick) kick = false,clearTimeout(kick_ts);
            if(data.waiting && data.started && data.started!='ready' && !kick) kick = true, kick_ts = setTimeout(function(){
              msgBox('Automatic elimination','You have been eliminated as you failed to select a card within a reasonable time.',false,'red');
              $('[data-action="leavegame"]:first').click();
            },60000);
          }
        }
      });
    }
  }
  function msgBox(title,message,no_close,color){
    $('#loadingbox').stop();
    $('#loadingbox .mdl-card').css({'top':'-100%'});
    $('#loadingbox [data-area="title"]').parent().removeClass('mdl-color--'+$('#loadingbox [data-area="title"]').parent().data('bgcolor'));
    $('#loadingbox [data-area="title"]').parent().addClass('mdl-color--'+color).data('bgcolor',color);
    $('#loadingbox [data-area="title"]').html(title);
    $('#loadingbox [data-area="main"]').html(message);
    if(no_close) $('#loadingbox [data-area="close"]').hide(); else $('#loadingbox [data-area="close"]').show();
    $('#loadingbox').fadeIn(500,function(){
      $('#loadingbox .mdl-card').animate({'top':'60%'},500,function(){
        $('#loadingbox .mdl-card').animate({'top':'50%'},100,function(){
          if(no_close >= 100) setTimeout(function(){
            $('#loadingbox .mdl-button').click();
          },no_close);
        });
      });
    });
  }
  function setTitle(title){
    document.title = title;
    $('[data-text="title"]').html(title);
  }
  function openGames(gamelist){
    $('#gameslist').html('');
    $.each(gamelist,function(g_id,data){
      var code = $($('[data-template="gameitem"]').html());
      $.each(data,function(data_name,data_val){
        var do_find = '{{game.'+data_name+'}}';
        var re = new RegExp(do_find, 'g');
        code.html(code.html().replace(re, data_val));
      });
      code.find('[data-action="play"]').click(function(){
        joinGame(data.id,false,'');
        return false;
      });
      code.find('[data-action="spectate"]').click(function(){
        joinGame(data.id,true,'');
        return false;
      });
      $('#gameslist').append(code);
    });
  }
  function a_loadGames(){
    if($('#play').hasClass('is-active') && !$('html').hasClass('in-game')){
      $.post({
        url: _server("games.php"),
        success: function(data){
          if(data.return=='user') $('#loadingbox .mdl-button,#cancelgame').click(),nickname_box();
          else if(data.return=='none') $('#gameslist').html('There are currently no public games with available slots. You can start a game below!');
          else if(data.return=='success') openGames(data.games);
        }
      });
    }
  }
  function nickname_box(){
    if(!$('#nicknamebox').is(':visible')) $('#nicknamebox').fadeIn(500,function(){
      $('#nicknamebox .mdl-card').animate({'top':'60%'},500,function(){
        $('#nicknamebox .mdl-card').animate({'top':'50%'},100);
      });
    });
  }
  function _get(part){
    var qs = window.location.search.split('+').join(' ');
    var params = {},
        tokens,
        re = /[?&]?([^=]+)=([^&]*)/g;
    while(tokens = re.exec(qs)){
      params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
    }
    return part?params[part]:params;
  }
  $(window).scrollTop(0);
  if(($('#nicknamebox .mdl-card').height()) > $(window).height()) $('#nicknamebox .mdl-card .mdl-card__title,#passwordbox .mdl-card .mdl-card__title,#loadingbox .mdl-card .mdl-card__title').height('61px');
  $('#actionbutton').click(function(){
    if(!$('html').hasClass('in-game')) nickname_box();
    else {
      $('#playedcards,#yourcard,#startgame').addClass('hidden');
      $('html').removeClass('in-game');
    }
  });
  $('#playerlimit input').on('change',function(){
    $('#playerlimit > span').html($(this).val());
  });
  $('#spectatorlimit input').on('change',function(){
    $('#spectatorlimit > span').html($(this).val());
  });
  $('#publicaccess input').on('change',function(){
    $('#publicaccess > span').html($(this).is(':checked')?'Public':'Unlisted');
    if($(this).is(':checked')){
      $('#passwordprotected,#passwordaccess').hide();
      $('#passwordprotected input:checked').click();
    } else $('#passwordprotected').show();
  });
  $('#passwordprotected input').click(function(){
  });
  $('#passwordprotected input').on('change',function(){
    $('#passwordprotected > span').html($(this).is(':checked')?'Enabled':'Disabled');
    if($(this).is(':checked')){
      $('#passwordaccess').show();
      $('#passwordaccess input').focus();
    } else $('#passwordaccess').hide();
  });
  $('#creategame > form').on('submit',function(){
    if($('#passwordprotected input').is(':checked') && $('#passwordaccess input').val()==''){
      msgBox('No password','You have password protected this game but have not entered a password. Please enter a password or disable password protection.',false,'red');
      return false;
    }
    msgBox('Creating game...','Creating your game. Won\'t be a sec.',true,'indigo');
    $.post({
      url: _server("create.game.php"),
      type: "post",
      data: $('#creategame > form').serialize(),
      success: function(data){
        if(data.error) msgBox('Error',data.message,false,'red');
        else {
          joinGame(data.game,false,'');
        }
      }
    });
    return false;
  });
  $('#entergame').click(function(){
    var allowedChars = new RegExp("^[a-zA-Z0-9\_]+$");
    if(allowedChars.test($('#nicknameinput').val()) && $('#nicknameinput').val().length>=3 && $('#nicknameinput').val().length<=16){
      msgBox('Verifying...','Checking name availablity.',true,'indigo');
      $('#nicknamebox .mdl-card').animate({'top':'60%'},100,function(){
        $('#nicknamebox .mdl-card').animate({'top':'-100%'},500,function(){
          $('#nicknamebox').fadeOut();
        });
      });
      $.post({
        url: _server("name.php"),
        type: "post",
        data: {
          name: $('#nicknameinput').val()
        },
        success: function(data){
          if(data.return=='success'){
            msgBox('Welcome!','Welcome, '+data.name+'.',500);
            if($('#entergame').data('autojoin')){
              setTimeout(function(game_id){
                joinGame(game_id);
              },2500,$('#entergame').data('autojoin'));
              $('#entergame').data({'autojoin':''});
            }else if(!done_before && _get('game')) joinGame(_get('game'),_get('spectate')=='1');
            done_before = true;
          }
          else {
            msgBox('Sorry','Sorry, that nickname is taken.',1000,'red');
            setTimeout(function(){
              nickname_box();
            },1000);
          }
        }
      });
    }else{
      msgBox('Error','Your nickname may only contain letters, numbers, and underscores ("_"), and must be 3-16 characters long.',false,'red');
    }
  });
  $.post({
    url: _server("checkname.php"),
    success: function(data){
      if(data.return=='success'){
        $('#nicknameinput').val(data.name).parent().addClass('is-dirty');
        $('#entergame').data({'autojoin':data.game}).click();
      }
      $.each(data.decks,function(d_id,deck_info){
        $('#deckselect select').append('<option value="'+deck_info['code']+'">'+deck_info['name']+' ('+deck_info['cards']+' cards)</option>');
      });
    }
  });
  $('#joingame').click(function(){
    msgBox('Checking password...','Checking password to join game.',true,'indigo');
    $('#cancelgame').click();
    joinGame($('#joingame').data('game'),$('#joingame').data('spectate')=='1',$('#passwordinput').val());
  });
  $('#cancelgame').click(function(){
    $('#passwordbox .mdl-card').animate({'top':'60%'},100,function(){
      $('#passwordbox .mdl-card').animate({'top':'-100%'},500,function(){
        $('#passwordbox').fadeOut();
      });
    });
  });
  $('#loadingbox .mdl-button').click(function(){
    $('#loadingbox .mdl-card').animate({'top':'60%'},100,function(){
      $('#loadingbox .mdl-card').animate({'top':'-100%'},500,function(){
        $('#loadingbox').fadeOut();
      });
    });
  });
  $('#startgame button').click(function(){
    msgBox('Starting game...','Shffling and dealing cards... Taking care of game preparations.',true,'green');
    $.post({
      url: _server("start.game.php"),
      type: "post",
      data: {
        game: game_id
      },
      success: function(data){
        if(data.return=='success'){
          gameStatus();
          $('#startgame,#startwait').addClass('hidden');
          $('#loadingbox .mdl-button').click();
        }
        else if(data.return=='error') msgBox('Error','The game could not be started.',false,'red');
        else if(data.return=='players') msgBox('Too few players','You cannot start the game until there are at least 2 players.',false,'red');
      }
    });
    return false;
  });
  $('[data-action="leavegame"]').click(function(){
    $.post({
      url: _server("leave.game.php")
    })
    $('#playedcards,#yourcard,#startgame').addClass('hidden');
    $('html').removeClass('in-game');
  });
  a_loadGames();
  setInterval(function(){
    a_loadGames();
  },10000);
  setInterval(function(){
    gameStatus();
  },3000);
  $.post({
    url: _server("ping.php")
  });
  $('[data-height]').each(function(){
    $(this).height($(this).width()*$(this).attr('data-height'));
  });
  $(window).on('resize',function(){
    $('[data-height]').each(function(){
      $(this).height($(this).width()*$(this).attr('data-height'));
    });
  });
});
