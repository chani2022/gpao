var data_socket = [];
    var timeOutMsg;
    var titleInit = "";
    var msgReceving = false;
    var compteurMsgRecev = 0;
    var panelConversationSlideUp = false;
    var oneMsg = 0;
    var twoMsg = 0;
    var animation;
    var Color;
    var animationColor;
    var msg1 = false;
    var newMsg = false;
    var nbPanelShow = 0;
    var isIdPanelEqual = false;
    var animRun = true;
    var evit_not_doublons = false;
    var list_id_and_compteur_msg_last = [];
    var list_message_not_view = [];
    
    var list_panel_default = [];
    var list_final_msg_not_view = [];
    var list_panel_block = [];
    var list_user_show_preview = [];
    moment.locale('fr');
    var me = $('.me').attr("id");

    //socket
    //var ws = new WebSocket("ws://192.168.8.9:8765");
    
    $(document).ready(function () {
        
        
        
        $("label.required").addClass("text-danger");
        titleInit = $('title').text();
    });
    //messagerie
    $(function () {
        
      
        
        //Couleur du panneaux de messagerie
        
        var $card_body = $("#minimize_messagerie").parents('.chatbox ').children('.card-body');
        $card_body.slideToggle();
                
        //Opacité du boutton ancien message
        
        $(".float2").css("opacity","1");
        $(".float").css("opacity","1");
        $(".float3").css("opacity","0.1");
        $(".float0").css("opacity","0.1");
        
        
        
        $( ".float2" ).click(function() {
            //alert("okok");
      })
        
      $( ".float2" ).mouseover(function() {
            $(".float2").css("opacity","1");
      })
      .mouseout(function() {
            $(".float2").css("opacity","0.1");
      });
      
      
      $( ".float" ).mouseover(function() {
            $(".float").css("opacity","1");
        
            
      })
      .mouseout(function() {
            $(".float").css("opacity","0.1");
            $(".float").css("font-size","30");
      });
      
      
      $( ".float3" ).mouseover(function() {
            $(".float3").css("opacity","1");
      })
      .mouseout(function() {
            $(".float3").css("opacity","0.1");
      });
      
      $( ".float0" ).mouseover(function() {
            $(".float0").css("opacity","1");
      })
      .mouseout(function() {
            $(".float0").css("opacity","0.1");
      });
      
        
    
    //chatboxdiscut-gauche
    
    
    $( ".rounded-circle user_img" ).mouseover(function() {
        //$( "div", this ).css("background","red");
      })
      .mouseout(function() {
        //$( "div", this ).css("background","green");
      });
     

    
    
    /**jQuery("#message_not_view li a:hover, a").mouseenter(function ()
        {
            alert("okokok");
            //jQuery("div#login-container").css("display", "block");
        });***/
    
    
    
       //...Lorsqu'on clique sur le bulle du chat reduction à droite
    $('.chatopacity').find( "ui" ).mouseenter(function(){
        //$('.user_img').css("background","yellow");
        //var allAs=$("#message_not_view li a:hover, a").find("#circle_right");
         //allAs.parents(allAs).children('#circle_right').css("display","block");
         //$('#message_not_view li a:hover, a' + $(this).data('id')).find("#circle_right").css("display","block");
    });

    //Lorsque la souris ressort du div...
    $(".chatopacity").mouseleave(function(){
        //var allAs=$("#message_not_view li").find("#circle_right");
        //allAs.css("display","none");
    });
        
        
    $("#couleur_pannel").mouseenter(function(){
        //...On ajoute une couleur de fond au div
        $(this).addClass('couleur_pannels');
        
    });

    //Lorsque la souris ressort du div...
    $("#couleur_pannel").mouseleave(function(){
        //...On remet un fond blanc
        $(this).addClass('couleur_pannels');
    });
        
        //mise a jour de donnees socket
        function updateAndSendData(data, $textarea, destinataire) {
            if ($textarea.val() !== "") {
                data["type"] = "message";
                data["data"]["destinataire"] = destinataire;
                data["data"]["text"] = $textarea.val();
                //if(msgReceving){
                    data["compteur"] = 1;
                //}
                ws.send(JSON.stringify(data));

                $textarea.val("");
                $textarea.focus();
            }
        }

        function createListMessageNotView(container, message_not_view){
                container.html("");

                //console.log("list", message_not_view);
                /**
                raha ita ao @panel de conversation ilay ul de esorin ao anatin le panel message not view
                */
                for(var i=0; i< message_not_view.length; i++){
                    var UserShowInPanel = false;
                    if(panel_block.length > 0){
                        for(var j=0; j<panel_block.length; j++){
                            if(panel_block[j].id == message_not_view[i].id){
                                UserShowInPanel = true;
                                //message_not_view.splice(i,1);

                                for(var k=0; k<list_message_not_view.length; k++){
                                    if(panel_block[j].id == list_message_not_view[k]["me"]){
                                        list_message_not_view.splice(k,1);
                                    
                                    }
                                }
                            }
                        }
                    }

                    if(!UserShowInPanel){

                        if($('#message_not_view').find('ui#'+message_not_view[i].id).length > 0){
                            $('#message_not_view').find('ui#'+message_not_view[i].id).remove();   
                        }
                        var ui = $('<ui id="'+message_not_view[i]["id"]+'" class="contact">' +
                                    '<li class="">' +
                                        '<a id="'+message_not_view[i]["id"]+'"class="notViewMsg" href="#">' +
                                            '<div class="d-flex bd-highlight">' +
                                                '<div class="img_cont">' +
                                                    '<img class="rounded-circle user_img" src="'+message_not_view[i]["img"]+'">'+
                                                '</div>'+
                                                '<i id="circle_right" style="font-size: 13px;color:currentColor;display:none" class="fa fa-times-circle"></i>'+
                                            '</div>'+
                                        '</a>'+
                                    '</li>'+
                                '</ui>');
                        container.append(ui);
                    }
                }
                /**
                panel_block = [];
                $('.boiteConversation').each(function(){
                    if($(this).css("display") == "block"){
                        panel_block.push($(this).attr("id"));
                    }
                });

                /**
                *affichage des avatars des personnel des message non lu
                **/
                
                
                //permet d'afficher le croix sur le bulle droite
                container.delegate('.notViewMsg', 'mouseenter', function(event) {
                    
                    
                    //$('#message_not_view').find('ui#'+id_msg_not_view).css("background-color","yellow");
                    
                        //$('#message_not_view li a:hover, a' + $(this).data('id')).parent().parent().css("background","yellow");
                    
                             $('#message_not_view li a:hover, a' + $(this).data('id')).find("#circle_right").css("display","block");
                                
                                //Permet de supprimer la message bulle à droite
                                
                                  $('#message_not_view li a:hover, a' + $(this).data('id')).find("#circle_right").click(function(e){
                                      e.preventDefault();
                                      
                                        //$(this).closest("div.post-it").fadeOut();
                                        $('#message_not_view li a:hover, a' + $(this).data('id')).parent().parent().remove();
                                        //console.log(list_message_not_view);
                                        
                                        
                                                var x = $(this).parent().parent().attr('id');
                                                for(var i=0; i<list_message_not_view.length; i++){
                                                        //if(x==list_message_not_view[i]["me"]){
                                                            //list_message_not_view.splice(i);
                                                        //}
                                                        if(list_message_not_view[i]["me"] == x){
                                                            list_message_not_view.splice(i,1);
                                                        }
                                                        
                                                    }
                                                
                                        for(var i=0; i<list_message_not_view.length;i++){
                                            for(var j=0; j<tab_temp_old_data_delete.length;j++){
                                                if(list_message_not_view[i]["me"] == tab_temp_old_data_delete[j]["me"]){
                                                    tab_temp_old_data_delete.splice(j,1);
                                                }
                                            }
                                        }
                                        list_message_not_view = list_message_not_view.concat(tab_temp_old_data_delete);

                                    });
                                    
                      }
                    );
                
                //permet de cacher le croix sur le bulle droite
                    container.delegate('.notViewMsg', 'mouseleave', function(event) {
                             var allAs=$("#message_not_view li").find("#circle_right");
                            allAs.css("display","none");
                        }
                    );
                    
                container.delegate('.notViewMsg', 'click', function(event) {
                    
                    event.preventDefault();
                    var id_user_panel_preview = $('.boiteConversation:first').attr("id");

                    var id_msg_not_view = $(this).attr("id");
                    var nb_user_msg_not_view = "";
                    user_clicked = false;
                    evit_not_doublons = true;

                    nb_user_msg_not_view = $('#message_not_view').find('ui').length;
                    
                    $('.contacts#contacts'+id_msg_not_view).find('a#'+id_msg_not_view).trigger('click');

                    $('#message_not_view').find('ui#'+id_msg_not_view).remove();

                    for(var i=0; i<list_id_and_compteur_msg_last.length; i++){
                        if(list_id_and_compteur_msg_last[i].id == id_msg_not_view){

                        }
                    }
                    
                    /**
                    $('.boiteConversation:first').css({
                        "backgroundColor":"rgb(66, 114, 215)"});
                    //list_message_not_view.push()
                    /**
                    on met a jour la liste des panel apres click
                    **/
                    //if(msgReceving){

                            panel_block = [];
                            $('.boiteConversation').each(function(){
                                if($(this).css("display") == "block"){
                                    var user = {
                                        "id":$(this).attr("id"),
                                        "img":$(this).find('.img_cont').find('img').attr("src"),
                                        "msgNotView":true,
                                        //"data": data
                                    };
                                    panel_block.push(user);
                                    /**
                                    if(nbPanelShow > 0){
                                        animClr(data["me"]);   
                                    } **/
                                }
                            });

                            $('.boiteConversation:first').find('.type_msg').trigger('click');
                           
                            /**
                            on permute l'utilisateur cliquer sur panel not view msg et la panel de conversation
                            */
                            var tab_temp_user_show_preview_in_panel_conversation = [];
                            var user_perm = "";
                            
                            
                            for(var i=0; i<list_message_not_view.length; i++){
                                if(id_msg_not_view == list_message_not_view[i]["me"]){
                                    
                                    var datas = {
                                        "type": "other",
                                        "me": list_message_not_view[i]["me"],
                                        "data": {
                                            //"listeUser": [],
                                            "destinataire": list_message_not_view[i]["me"],
                                        },
                                        "messageData":{

                                        }
                                    };
                                    
                                    list_message_not_view[i]["me"] = id_user_panel_preview;
                                    list_message_not_view[i]["data"]["destinataire"] = id_user_panel_preview;
                                    user_perm = list_message_not_view[i];

                                    tab_temp_old_data_delete.push(datas);
                                        
                                }
                            }
                            
                            /**
                            on enleve les doublons dans eviter la repetition des images dans la panel de message not view
                            */

                            for(var i=0; i<tab_temp_old_data_delete.length;i++){
                                if(tab_temp_old_data_delete[i] !== undefined){
                                    for(var j=0; j<list_message_not_view.length;j++){
                                        if(tab_temp_old_data_delete[i]["me"] == list_message_not_view[j]["me"]){
                                            tab_temp_old_data_delete.splice(i,1);
                                        }
                                    }
                                }
                            }
                            
                            /**
                            enlever les doublons
                            **/
                            for(var i=0; i<panel_block.length;i++){
                                for(var j=0; j<list_message_not_view.length;j++){
                                    var tab_splice = [];//tab pour enlever les doublons
                                    for(var k=j+1; k<list_message_not_view.length; k++){
                                        if(list_message_not_view[j]["me"] == list_message_not_view[k]["me"]){
                                            tab_splice.push(k);
                                        }
                                    }
                                    if(tab_splice.length > 0){
                                        for(var l=0; l<tab_splice.length;l++){
                                            list_message_not_view.splice(tab_splice[l],1);
                                        }
                                    }
                                    if(panel_block[i].id == list_message_not_view[j]["me"]){
                                        list_message_not_view.splice(j,1);
                                        
                                    }
                                }
                            }
                            
                            /**
                            mise a jours des panel de message not view
                            **/
                            if(panel_block.length == 2 || panel_block.length == 1){

                                if(nb_user_msg_not_view > 0){
                                    var userOnPanel = false;
                                    for(var i=0; i<panel_block.length; i++){
                                        if(panel_block[i].id == user_perm["me"]){
                                            userOnPanel = true;
                                        }
                                    }
                                    
                                    if(user_perm && !userOnPanel){
                                        var found = false;
                                        $('#message_not_view').find('ui').each(function(){
                                            if($(this).attr("id") == user_perm["me"]){
                                                found = true;
                                            }
                                        });
                                        if(!found){
                                            var id = $('.lienbox#'+user_perm["me"]).attr("id");
                                            var img = $('.lienbox#'+user_perm["me"]).find('.img_cont').find('img').attr("src");
                                            if(id !== undefined){
                                                var ui = $('<ui id="'+id+'" class="contact">' +
                                                            '<li class="">' +
                                                                '<a id="'+id+'"class="notViewMsg" href="#">' +
                                                                    '<div class="d-flex bd-highlight">' +
                                                                        '<div class="img_cont">' +
                                                                            '<img class="rounded-circle user_img" src="'+img+'">'+
                                                                        '</div>'+
                                                                    '</div>'+
                                                                '</a>'+
                                                            '</li>'+
                                                        '</ui>');
                                                $('#message_not_view').append(ui);
                                            }

                                        }
                                    }
                                }
                            }
                    /**
                            //showUserInPanelMsgNotView(); 
                            //msgReceving = false;
                    //}else{
                        
                        raha 1 ilay panel de conversation de nis message tonga nef nefermena de raha vo clicken de tonga de atw vue
                        
                        for(var i=0; i<list_message_not_view.length; i++){
                            $('.boiteConversation').find('.type_msg#'+list_message_not_view[i]["me"]).trigger('click');
                        }
                    }
                  **/
                });
                /**
                effacement manuel des utilisateur dans la panel not view
                
                container.delegate('.user_delete', 'click', function(event) {
        
                    event.preventDefault();
                   var id_msg_not_view = $(this).attr("id");
                   $(this).parents('ui').remove();
                   
                   for(var i=0; i<list_message_not_view.length;i++){
                        if(list_message_not_view[i]["me"] == id_msg_not_view){
                            list_message_not_view.splice(i,1);
                        }
                   }
                   for(var j=0; j<tab_temp_old_data_delete.length;j++){
                        if(tab_temp_old_data_delete[i]["me"] == id_msg_not_view){
                            tab_temp_old_data_delete.splice(j,1);
                        }
                   }
                });  **/
            }

            function showUserInPanelMsgNotView(){
                var list_msg = [];
                
                //console.log(list_message_not_view);

                for(var i=0; i<list_message_not_view.length;i++){
                        if(list_message_not_view[i]["me"] != "element2"){
                            var id = $('.lienbox#'+list_message_not_view[i]["me"]).attr("id");
                            var img = $('.lienbox#'+list_message_not_view[i]["me"]).find('.img_cont').find('img').attr("src");
                            var user_msg_not_view = {
                                    "id": id,
                                    "img":img
                                    };
                            list_msg.push(user_msg_not_view);    
                        }else{
                            //list_message_not_view.splice(i,1);
                        }       
                }

                createListMessageNotView($('#message_not_view'), list_msg); 
               
            }
            /**
             * notification de nouveau message si il y a 2 panel de conversation
             */
            function animClr(id_destinataire){
                $('.boiteConversation').each(function(index, item){
                    if(id_destinataire == $(this).attr("id")){
                        
                        $(this).children( ".card-header" ).css({ 'background': 'crimson' });
                        //bgCardBody = 'crimson';
                    }
                })
            }

            /**
             * animation du titre de l'app
             */
            animation = function recurs(){
                timeOutMsg = setTimeout(function(){
                            var msg = "Vous avez un nouveau message";
                            var title = $('title').text();

                            if(title == msg){
                                $('title').text(titleInit);
                            }else{
                                $('title').text(msg);
                            }
                            /**
                            if(id_msg_not_view){
                                if(id_msg_not_view == me){
                                    recurs(id_msg_not_view);
                                }
                            }
                            if(msgReceving){
                                recurs();
                            }**/
                            recurs();
                        },500);        
            }

        var list_user_to_create_dialog = [];
        var info_destinataire = {};
        var panel_block = [];
        var list_users = {{ app.session.get('list_user')|json_encode|raw }};
        var user_clicked = true;
        var active_msg_not_view = false;
        var tab_temp_old_data_delete = [];
        var msgsNotView = [];
        var bgCardBody = "";
        var soustraction = 1;
        var compteur = 1;
        var list_temp_msg_next_and_prev = [];
        var isMsgView = true;
        var listeMsgNotView = [];
        var listCloneUser = [];
        var listUiPrev = [];
        var firstElementUi = "";
        var msgView = false;

        var ws = new WebSocket("ws://192.168.9.253:8765");
        //var ws = new WebSocket("ws://192.168.8.6:8765");
        //me
        
        var isKeyDown = false;

        //donnee socket
        var data = {
            "type": "connexion",
            "me": me,
            "data": {
                "listeUser": [],
                "destinataire": 0,
                "text": "",
                "date": moment().startOf('hour').fromNow()//date moment,
            }
        };
        //ouverture de la connexion
            ws.onopen = function (event) {
                ws.send(JSON.stringify(data));
                var destinataire = "";
                var id_textarea = "";

                /**
                 * keyup no apesain fa raha tsizan de azon le panel faharo ilay \n
                 **/
                $('textarea.type_msg').keyup(function (e) {
                    //touche entrer du clavier
                    if (e.keyCode == 13) {
                        isKeyDown = true;
                        if($(this).val().trim() !== ""){
                            $(this).parents('.boiteConversation').find('.send_btn').trigger('click', [$(this).attr("id")]);
                        }
                    }
                });

                $('.send_btn').click(function (e, id_destinataire) {
                    var textarea = $(this).parents('.boiteConversation').find('.type_msg');
                    if(id_destinataire){
                        if($(this).attr("id") == id_destinataire){
                            destinataire = id_destinataire;    
                        }
                    }else{
                        destinataire = textarea.attr("id");
                    }
                    updateAndSendData(data, textarea, destinataire);                        
                   
                });

            };
            ws.onmessage = function (event) {
                var data = JSON.parse(event.data);
                //console.log(data);

                /**
                 * créér une element dans la panel de conversation
                 *
                 * @param isMeRecevingMsg (boolean si message est recu par un utilisateur)
                 * @param containerMsg
                 * @param user (utilisateur qui est clique par jquery lors de renvoye de message)
                */
                 function createElementsNode(isMeRecevingMsg, containerMsg, user){
                    containerMsg.html("");
                    var styleContainer = "d-flex mb-4 justify-content-";
                
                    //-------------------------------------
                    //var dateLast = listDate[listDate.length - soustraction];//date de dernier conversation par defaut
                    
                    if(isMeRecevingMsg){

                        //conversation
                        /**
                        *on compare la date du dernier conversation et la date de liste des *Conversation
                        * puis, on le filtre
                        */
                        for(var i in data["messageData"]){

                            //if(i == dateLast){//condition de filtre
                                var day = i.substring(0,2);
                                var month = i.substring(2,4);
                                var year = i.substring(4, i.length);
                                var dateCurrent = day+"/"+month+"/"+year;

                                for(var j in data["messageData"][i]){
                                    var container = $('<div></div>');
                                    var vue = "";
                                    if(data["messageData"][i][j]["pour"] != me){
                                        if(data["messageData"][i][j]["lu"] == 1){
                                                vue = ".<br/><i>Lu</i>.";
                                            }
                                        container.addClass(styleContainer +"end mb-4");
                                        
                                        var subElement = $('<div class="msg_cotainer_send">' +
                                        '<p>' +
                                            data["messageData"][i][j]["texte"]+
                                        '</p><p style="color:darkslategrey">' + 
                                            dateCurrent+" à "+data["messageData"][i][j]["heure"] + vue +
                                            //data["messageData"][i][j]["heure"] +
                                        '</p>'+
                                        '</div>');
                                        container.append(subElement);
                                        containerMsg.append(container); 
                      
                                    }else{
                                        
                                        if(data["messageData"][i][j]["lu"] == 1){
                                                                        vue = ".<br/><i>Lu</i>.";
                                                                    }
                                        container.addClass(styleContainer +"start mb-4");    
                                        subElement = $('<div class="col-md-12">' +
                                                        '<div class="img_cont_msg">' +
                                                            '<img class="rounded-circle user_img_msg" src="'+containerMsg.parents('.boiteConversation').find('.img_cont').find('img').attr("src")+'">'+
                                                        '</div>'+
                                                        '<div class="msg_cotainer">' +
                                                                '<p>' +
                                                                     data["messageData"][i][j]["texte"]+
                                                                '</p><p style="color:darkslategrey">' +
                                                                    
                                                                   dateCurrent+" à "+ data["messageData"][i][j]["heure"] + vue +
                                                                   //data["messageData"][i][j]["heure"] +
                                                                '</p>'+
                                                        '</div>'+
                                                '</div>');
                                    
                                    container.append(subElement);
                                    containerMsg.append(container);  
                                }     
                            }
                        //}
                    
                        }
                    }
                    else{

                        for(var i in data["messageData"]){
                            //if(i == dateLast){
                                var day = i.substring(0,2);
                                var month = i.substring(2,4);
                                var year = i.substring(4, i.length);
                                var dateCurrent = day+"/"+month+"/"+year;

                                for(var j in data["messageData"][i]){
                                    var container = $('<div></div>');
                                    var msgLu = "";
                                    if(data["messageData"][i][j]["de"] == me){
                                    
                                        if(data["messageData"][i][j]["lu"] == 1){
                                            msgLu = "<br/><i>Lu</i>";
                                        }
                                        container.addClass(styleContainer +"end mb-4");
                                        var subElement = $('<div class="msg_cotainer_send">' +
                                        '<p>' +
                                            data["messageData"][i][j]["texte"]+
                                        '</p><p style="color:darkslategrey">' +
                                            dateCurrent+" à "+data["messageData"][i][j]["heure"] + msgLu +
                                        '</p>'+
                                        '</div>');
                                        
                                        container.append(subElement);
                                        containerMsg.append(container);   
                                    }
                                    /**
                                    if(data["messageData"][i][j]["pour"] == data["data"]["destinataire"]){**/
                                    else{
                                        if(data["messageData"][i][j]["lu"] == 1){
                                            msgLu = "<br/><i>lu</i>";
                                        }
                                        container.addClass(styleContainer +"start mb-4");    
                                    
                                    //container.addClass(styleContainer +"start mb-4");
                                        subElement = $('<div class="col-md-12">' +
                                                        '<div class="img_cont_msg">' +
                                                            '<img class="rounded-circle user_img_msg" src="'+/**user.find('.img_cont').find('img').attr("src")**/containerMsg.parents('.boiteConversation').find('.img_cont').find('img').attr("src")+'">'+
                                                        '</div>'+
                                                        '<div class="msg_cotainer">' +
                                                                '<p>' +
                                                                     data["messageData"][i][j]["texte"]+
                                                                '</p><p style="color:darkslategrey">' +
                                                                    data["messageData"][i][j]["heure"] + msgLu +
                                                                '</p>'+
                                                        '</div>'+
                                                '</div>');
                                        
                                    container.append(subElement);
                                    containerMsg.append(container);
                                    //containerMsg.scrollTop(containerMsg[0].scrollHeight);
                                }     
                            }
                        //}
                        }
                    }
                    containerMsg.scrollTop(containerMsg[0].scrollHeight);
                    //containerMsg.find('.')
                }


                if (data["data"] !== undefined) {
                    switch (data["type"]) {
                        /**
                         * lu des msg
                        */
                        case 'messageLu':
                            /**
                            utilisateur qui recoit le msg vue
                            **/
                            var isBoiteConversationShow = false;
                            if(data["data"]["destinataire"] == me){
                                $('.boiteConversation').each(function(){
                                    if($(this).css("display") == "block" && $(this).attr("id") == parseInt(data["me"])){
                                        isBoiteConversationShow = true;
                                        
                                    }
                                });

                                /**
                                $("#parent_total_message").append('<span class="nombre_de_message" class="notification_liste_message" title="Nouveau message">'+totalMsg+'</span>');
                                
                                nbMsg -= obj.nbMsg;
                                if(nbMsg == 0){
                                    $('#parent_total_message').children().remove();
                                }else{
                                    $('#parent_total_message').children().text(nbMsg);
                                }
                                **/
                            }
                            
                            $('.lienbox').each(function(){
                                
                                /**
                                utilisateur qui recoit le msg
                                */
                                if($(this).attr("id") == data["me"] && data["data"]["destinataire"] == me){
                                    user_clicked = false;
                                    //raha ef msokatra ilay boite de conversation de ze vo triggeren ilay lienbox
                                        if(isBoiteConversationShow){
                                            $(this).trigger('click');  
                                        }
                                }
                                /**
                                utilisateur qui envoye msg
                                */
                                if($(this).attr("id") == data["data"]["destinataire"] && data["me"] == me){
                                    user_clicked = false;


                                    $(this).trigger('click');    
                                   if($('#parent_total_message').children().length > 0){
                                        var totalMsg = 0;
                                        //var totalMsg = parseInt($('#parent_total_message').children().text());   
                                        for(var obj of listeMsgNotView){
                                            
                                            if(obj.id == data["data"]["destinataire"]){
                                                 obj.nbMsg = 0;
                                            }
                                        }
                                        
                                        for(var obj of listeMsgNotView){
                                            totalMsg += obj.nbMsg;
                                        }
                                        /**
                                        if(totalMsg <= 0){
                                            totalMsg = 0;
                                            $('#parent_total_message').children().text();
                                        }else{
                                            $('#parent_total_message').children().text(totalMsg);    
                                        }**/
                                        if(totalMsg != 0){
                                            $('#parent_total_message').children().text(totalMsg);    
                                        }else{
                                            $('#parent_total_message').children().remove();
                                        }
                                    }
                                }
                            });
                            
                        /**
                         * activation de la status du personnel connecté
                         ***/
                         break;
                        case 'connexion':
                            //raha mbl tsy misoktra ny session
                            if(data["me"] == me){
                                console.log(data);
                                if(data["listeNonLu"]){ //ato raha mis msg tonga nef tsy lu
                                    var totalMsg = 0;
                                    var animActive = true;
                                    var list_data_not_view = [];
                                    for(var id in data["listeNonLu"]){
                                        if(id == me){
                                             delete data["listeNonLu"][id];
                                        }
                                    }
                                    console.log("data",data["listeNonLu"]);
                                   
                                    if(Object.keys(data["listeNonLu"]).length === 0 && data["listeNonLu"].constructor === Object) {
                                                animActive = false;
                                    }
                                    if(animActive){
                                        animation();
                                    }
                                    

                                    firstElementUi = $('.chatbox').find('.card-body').find('ui:first');//premier element @le ui
                                    //boucle le isan le message ato
                                    for(var id in data["listeNonLu"]){

                                        totalMsg += data["listeNonLu"][id];
                                        var obj = {
                                            prev:$('#contacts'+id).prev(),
                                            current: $('#contacts'+id),
                                            id: id,
                                            nbMsg: data["listeNonLu"][id]
                                        };

                                        listeMsgNotView.push(obj);
                                        var uiPrev = $('#contacts'+id).prev();

                                        var cloneUser = $('#contacts'+id).clone();

                                        //apetrak ao @ny liste user ny isan message not view tsirai2
                                        cloneUser.addClass('clone').find('.user_info').append('<span  id="notification_liste_message" title="Nouveau message">'+data["listeNonLu"][id]+'</span>');
                                       
                                        /**jose nombre de message
                                        $("#parent_total_message").append('<span class="nombre_de_message" class="notification_liste_message" title="Nouveau message">'+data["listeNonLu"][id]+'</span>');**/

                                           
                                        $('#contacts'+id).remove();
                                        
                                        cloneUser.insertBefore(firstElementUi);   
                                    }

                                    /**jose nombre de message**/
                                    if(totalMsg != 0){
                                        $("#parent_total_message").append('<main title="Total des messages" rel="main" style="margin-bottom: -35px;margin-left: -35px;"><div class="notification"><svg viewbox="-10 0 35 35"><path class="notification--bell" d="M14 12v1H0v-1l0.73-0.58c0.77-0.77 0.81-3.55 1.19-4.42 0.77-3.77 4.08-5 4.08-5 0-0.55 0.45-1 1-1s1 0.45 1 1c0 0 3.39 1.23 4.16 5 0.38 1.88 0.42 3.66 1.19 4.42l0.66 0.58z"></path><path class="notification--bellClapper" d="M7 15.7c1.11 0 2-0.89 2-2H5c0 1.11 0.89 2 2 2z"></path>  </svg><span class="notification--num">' + totalMsg + '</span></div></main>');
                                    }
                                }

                            }
                            /**
                             * chargement des donnees ato
                             */
                            data_socket = data;
                        
                            for (var i = 0; i < data["data"]["listeUser"].length; i++) {
                                $('.lienbox').each(function () {
                                    if ($(this).attr("id") == data["data"]["listeUser"][i]) {
                                        $(this).find('.online_icon').removeClass('offline');
                                    }
                                });
                                /**
                                if(data["listeNonLu"]){
                                    var nbMsgNotView = 0;
                                    animRun = true;
                                    
                                    for(var id in data["listeNonLu"]){
                                        
                                        nbMsgNotView += parseInt(data["listeNonLu"][id]);

                                        $('.lienbox#'+id).find('.user_info').append('<span>'+ data["listeNonLu"][id]+'</span>');
                                            animation(data["listeNonLu"][id]);
                                    }
                                    /**
                                    $('.chatbox').find('.card-header').append('<div class="msgNotLu">'+nbMsgNotView+'</div>').css({
                                        "background":'red'
                                    });
                                    
                                }
                                **/
                            }
                            
                            break;
                        case "message":

                            newMsg = true;
                            /**
                             * remettre le curseur de la souris d'un texteareo au début lors de l'évènement entrer du clavier
                            */
                            if (isKeyDown) {
                                var textarea = document.getElementsByClassName('type_msg');
                                for (var i = 0; i < textarea.length; i++) {
                                    textarea[i].setSelectionRange(1, 2);
                                }
                                isKeyDown = false;
                            }
                            if ((data["data"]["destinataire"] == me) || (data["me"] == me)) {
                                /**
                                 * lors de l'envoyer du message
                                 * on cherche la panel de conversation du destinataire
                                 **/
                                if (data["me"] == me) {
                                    /**
                                    for(var i=0; i<list_id_and_compteur_msg_last.length; i++){
                                            if(list_id_and_compteur_msg_last[i].id == data["me"]){
                                                soustraction = 1;
                                            }
                                        }
                                        **/

                                        var $container_msg = $('.container_message');
                                        var cnt = "";
                                        $container_msg.each(function () {
                                            if (parseInt($(this).attr("id")) == parseInt(data["data"]["destinataire"])) {
                                                cnt = $(this);
                                                //$(this).html("");
                                                
                                                //var dataMsg = data["messageData"];
                                            }
                                        });
                                        if (cnt.length > 0) {
                                            createElementsNode(false, cnt);
                                        }
                                    }

                                /**
                                 * reception du message d'un utilisateur
                                 **/
                                else if (parseInt(data["data"]["destinataire"]) == parseInt(me)) {
                                    //console.log(data);
                                    /**
                                    if(data["type"] == "messageLu"){
                                        //eto za zo refveo chargement msg
                                        
                                        $('.lienbox').each(function(){
                                            if($(this).attr("id") == data["me"] && data["data"]["destinataire"] == me){
                                                user_clicked = false;
                                                $(this).trigger('click');  
                                            }
                                        });
                                    }else{
                                       **/ 
                                        msgReceving = true;
                                        compteurMsgRecev++;
                                        twoMsg ++;
                                        oneMsg ++;
                                        isMsgView = false;

                                        /**
                                        si un nouveau message est venu et qu'il est dans la panel de message not view,
                                        on l'enleve dans ce dernier puis, on le supprime dans la list des message not view
                                        **/
                                        if($('.contacts#contacts'+data["me"]).find('a#'+data["me"]) > 0){
                                            $('.contacts#contacts'+data["me"]).find('a#'+data["me"]).remove();
                                        }
                                        
                                        if(animRun){
                                            animation();
                                        }
                                        /**
                                         * rehefa mahazo focus le window dia mial ilay animation le titre
                                        */
                                        $(window).focus(function () {
                                            clearTimeout(timeOutMsg);
                                            $('title').text(titleInit);
                                            animRun = true;
                                        });
                                        /**
                                         * rehefa mahazo focus le textarea mcorrespondre dia mial ilay soratra oe nouveau message de atw vue le msg
                                         **/
                                        $('textarea.type_msg').each(function (e) {
                                            $(this).click(function (e) {
                                                //console.log(data);
                                                if ($(this).attr("id") == data["me"]) {
                                                    $(this).parents('.boiteConversation').find('.user_info p').last().text("");
                                                    
                                                    $(this).parents('.boiteConversation').children( ".card-header" ).css({ 'background': 'rgba(0,0,0,.03)' });
                                                    
                                                    
                                                    clearTimeout(timeOutMsg);
                                                    $('title').text(titleInit);
                                                    animRun = true;
                                                    
                                                    if(data["data"]["destinataire"] == me && $(this).attr("id") == data["me"]){
                                                        var isAllMsgView = true;
                                                        data["type"] = "messageLu";
                                                        for(var i in data["messageData"]){
                                                            for(var j in data["messageData"][i]){
                                                                        if(data["messageData"][i][j]["lu"] == 0){
                                                                            data["messageData"][i][j]["lu"] = 1;   
                                                                            isAllMsgView = false;
                                                                        }
                                                                    
                                                            }
                                                        }
                                                        if(!isAllMsgView){
                                                            ws.send(JSON.stringify(data));
                                                            //msgReceving = false;//apres click msg vue
                                                            //console.log(msgReceving); 
                                                            isMsgView = true; 
                                                        }
                                                        //
                                                    }
                                                }
                                            });
                                        });

                                        animRun = false;
                                        compteurMsgRecev = 0;

                                        /**
                                         * manokatra ny panel de conversation
                                         */
                                         
                                        list_message_not_view.push(data);
                                        $classUser.each(function () {
                                            if (parseInt($(this).attr("id")) == parseInt(data["me"]) && data["data"]["destinataire"] == me) {
                                                var id_user = $(this).attr("id");
                                                var container_conversation = [];//liste de panel de conversation

                                                user_clicked = false;
                                                if(listeMsgNotView.length > 0){
                                                    for(var obj of listeMsgNotView){
                                                            if(data["me"] == obj.id){
                                                                msgView = true;
                                                                //clearTimeout(timeOutMsg);
                                                                //$('title').text(titleInit);
                                                                if($('#contacts'+obj.id).hasClass('clone')){
                                                                    $('#contacts'+obj.id).remove();
                                                                    obj.current.insertAfter(obj.prev);
                                                                    console.log(obj.current);
                                                            }  
                                                        }
                                                    }
                                                }
                                                $(this).trigger('click');
                                                //user_clicked = true;

                                                
                                                $('.boiteConversation').each(function (index,item) {
                                                var img = $(this).find('.img_cont').children('img').attr("src");
                                                var id_container = $(this).attr("id");
                                                        $(this).find('.container_message').each(function () {
                                                            if ($(this).attr("id") == parseInt(data["me"]) && data["data"]["destinataire"]== me) {
                                                                $(this).html("");
                                                                
                                                                container_conversation.push($(this));
                                                            }
                                                        });
                                                });
                                                   
                                                
                                                if (container_conversation.length > 0) {
                                                    /**
                                                     * infectation de message pour chaque panel de conversation
                                                     **/
                                                    for (var i = 0; i < container_conversation.length; i++) {
                                                        if (container_conversation[i].attr("id") == data["me"]) {
                                                        
                                                            createElementsNode(true, container_conversation[i]);
                                                        }
                                                    }
                                                }
                                            }
                                        });
                                        //});

                                        /**
                                         * notification des panel qui vient de recevoir un message
                                         **/
                                        panel_block = [];
                                        $('.boiteConversation').each(function(){
                                            if($(this).css("display") == "block"){
                                                
                                                var user = {
                                                    "id":$(this).attr("id"),
                                                    "img":$(this).find('.img_cont').find('img').attr("src"),
                                                    "msgNotView":true,
                                                    //"data": data
                                                };
                                                panel_block.push(user);

                                                
                                                if(nbPanelShow > 0){
                                                    animClr(data["me"]);   
                                                } 
                                                
                                            }
                                        });
                                        
                                        function updateMsgNotView(){
                                         
                                            for(var i=0; i<panel_block.length; i++){
                                                var userFound = false;
                                                    for(var j=0; j<list_message_not_view.length; j++){
                                                        if(panel_block[i].id == list_message_not_view[j]["me"]){
                                                            var elemDel = list_message_not_view.splice(j,1);
                                                            tab_temp_old_data_delete.push(elemDel[0]);
                                                        }
                                                }
                                            }  
                                        }
                                        
                                        if(!active_msg_not_view){
                                             
                                            if(panel_block.length == 2 && list_message_not_view.length > 0){
                                                
                                                updateMsgNotView();
                                                
                                                if(list_message_not_view.length > 0){
                                                    showUserInPanelMsgNotView();
                                                }
                        
                                                active_msg_not_view = true;
                                            }
                                        }else{

                                                if(list_message_not_view.length > 0){
                                                  
                                                    var list_temp = list_message_not_view;
                                                    list_message_not_view = [];
                                                    list_message_not_view = list_temp.concat(tab_temp_old_data_delete); 
                                                    tab_temp_old_data_delete = [];
                                                }
                                                updateMsgNotView();
                                                
                                                
                                                //list_message_not_view = new Set(list_message_not_view);
                                                //list_message_not_view = Array.from(list_message_not_view);

                                                if(list_message_not_view.length > 0){
                                                    showUserInPanelMsgNotView();
                                                }
                                            }
                                    }
                                //}
                            }
                            break;
                            
                        case "chargementMessage":
                        
                            /**
                            raha mitovy ny tompon le session sy ilay panel msokatra de ze atw ajour ilay msg
                            */
                            $('body').find('.boiteConversation').each(function(){
                                if($(this).attr("id") == data["data"]["destinataire"] && me == data["me"]){
                                    
                                    //msgView = true;
                                    var container = $(this).find('.container_message');
                                    /**
                                    rehef chargement message de tonga de atw lu dul le msg
                                    aveo
                                    **/
                                    if(isMsgView){
                                        //if(listeMsgNotView.length > 0){
                                            //clearTimeout(timeOutMsg);
                                            //$('title').text(titleInit);
                                            
                                        //}
                                        /**
                                        $("#parent_total_message").append('<span class="nombre_de_message" class="notification_liste_message" title="Nouveau message">'+totalMsg+'</span>');
                                        **/
                                    
                                        //console.log(listeMsgNotView);
                                        //console.log(data["data"]["destinataire"]);
                                        for(var obj of listeMsgNotView){
                                            if(data["data"]["destinataire"] == obj.id){
                                                msgView = true;
                                                clearTimeout(timeOutMsg);
                                                $('title').text(titleInit);

                                                
                                                if($('#contacts'+obj.id).hasClass('clone')){
                                                    $('#contacts'+obj.id).remove();
                                                    obj.current.insertAfter(obj.prev);
                                                }  
                                            }
                                        }

                                        for (var i = 0; i < data["data"]["listeUser"].length; i++) {
                                            $('.lienbox').each(function () {
                                                if ($(this).attr("id") == data["data"]["listeUser"][i]) {
                                                       $(this).find('.online_icon').removeClass('offline');
                                                }
                                            });
                                        }
                                        //if(data["data"]["destinataire"] == me){
                                            
                                        //}
                                        //console.log(isCompteurIncrement);
                                        //if(isCompteurIncrement) {
                                        if(msgView) {
                                            for (var i in data["messageData"]) {
                                                for (var j in data["messageData"][i]) {
                                                    if (data["messageData"][i][j]["lu"] == 0) {
                                                        data["messageData"][i][j]["lu"] = 1;
                                                        data["type"] = "messageLu";
                                                        ws.send(JSON.stringify(data));
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    
                                    createElementsNode(true, container); 

                                    var cssDisabled = {
                                        cursor: "",
                                        pointer: "",
                                        opacity: ""
                                    };
                                    var cssAbled = {
                                        cursor: "",
                                        pointer: "",
                                        opacity: ""
                                    };
                                    
                                    var initHistorique = data["resteHistorique"] + data["compteur"];
                                    var resteHistoriqueInit = initHistorique - 1;
                                    
                                    if(data["resteHistorique"] == 0){
                                        /**
                                        si la bouton ancien msg a été cliked
                                        on desactive cette derniere
                                        */
                                        
                                        if(isCompteurIncrement){

                                            cssDisabled.cursor = "not-allowed";
                                            cssDisabled.pointer = "none";
                                            cssDisabled.opacity = "0.1";

                                            cssAbled.cursor = "";
                                            cssAbled.pointer = ""; 
                                            cssAbled.opacity = "1";

                                            if(data["compteur"] == initHistorique){

                                                cssAbled.cursor = "";
                                                cssAbled.pointer = "";
                                                cssAbled.opacity = "1";

                                                cssDisabled.cursor = "not-allowed";
                                                cssDisabled.pointer = "none";
                                                cssDisabled.opacity = "0.1";
                                            }
                                        }else{

                                            if(data["compteur"] == 0){
                                                cssDisabled.cursor = "";
                                                cssDisabled.pointer ="";
                                                cssDisabled.opacity = "1";

                                                cssAbled.cursor = "not-allowed";
                                                cssAbled.pointer = "none";
                                                cssDisabled.opacity = "0.1";
                                            }
                                            if(data["compteur"] == initHistorique){
                                                cssDisabled.cursor = "";
                                                cssDisabled.pointer ="";
                                                cssDisabled.opacity = "1";

                                                cssAbled.cursor = "not-allowed";
                                                cssAbled.pointer = "none";
                                                cssAbled.opacity = "0.1";
                                            }

                                            if(data["compteur"] == 1){
                                            
                                                cssDisabled.cursor = "not-allowed";
                                                cssDisabled.pointer ="none";
                                                cssDisabled.opacity = "0.1";

                                                cssAbled.cursor = "not-allowed";
                                                cssAbled.pointer = "none";
                                                cssAbled.opacity = "0.1";
                                            }
                                        }
                                        
                                        /**
                                        desactivation du bouton preview msg
                                        
                                        cssDisabled.cursor = "not-allowed";
                                        cssDisabled.pointer = "none";
                                        /**
                                        activation du bouton next msg
                                        
                                        cssAbled.cursor = "";
                                        cssAbled.pointer = "";

                                        for(var i=0; i<list_id_and_compteur_msg_last.length;i++){
                                            if(list_id_and_compteur_msg_last[i].id == data["data"]["destinataire"]){
                                                list_id_and_compteur_msg_last[i].cpt = initHistorique; //
                                                //console.log("compteu",list_id_and_compteur_msg_last[i].cpt);
                                            }
                                        }
                                        //eto za zo
                                         **/
                                    }else{
                                        if(data["resteHistorique"] == 1){
                                            cssAbled.cursor = "not-allowed";
                                            cssAbled.pointer = "none";
                                            cssAbled.opacity = "0.1";

                                            cssDisabled.cursor = "";
                                            cssDisabled.pointer = "";
                                            cssDisabled.opacity = "1"; 

                                        }else{
                                            cssAbled.cursor = "not-allowed";
                                            cssAbled.pointer = "none";
                                            cssAbled.opacity = "0.1";

                                            cssDisabled.cursor = "";
                                            cssDisabled.pointer = "";
                                            cssDisabled.opacity = "1";      
                                        }
                                        if(data["compteur"] != 1){
                                            cssDisabled.cursor = "";
                                            cssDisabled.pointer = "";
                                            cssDisabled.opacity = "1";

                                            cssAbled.cursor = "";
                                            cssAbled.pointer = "";
                                            cssAbled.opacity = "1";
                                        }
                                    }
                                    if(data["resteHistorique"] == undefined){
                                    
                                        cssAbled.cursor = "not-allowed";
                                        cssAbled.pointer = "none";
                                        cssAbled.opacity = "0.1";
                                        cssDisabled.cursor = "not-allowed";
                                        cssDisabled.pointer = "none";
                                        cssDisabled.opacity = "0.1";
                                    }
                                       
                                    $(this).find('.lastMsg#'+data["data"]["destinataire"]).css({
                                            "cursor":cssDisabled.cursor,
                                            "pointer-events":cssDisabled.pointer,
                                            "opacity": cssDisabled.opacity
                                    }); 

                                    $(this).find('.nextMsg#'+data["data"]["destinataire"]).css({
                                            "cursor":cssAbled.cursor,
                                            "pointer-events":cssAbled.pointer,
                                            "opacity":cssAbled.opacity
                                    });
                                    
                                }
                            });
                           break;                 
                }
            }
        };

        /**
         * affichage de liste de panel de conversation
         * @param list_user
         **/
        function showPanelConversation(list_user_to_create_dialog, User, isNbPanelDecrement) {
            
            /**
             * affichage de boite de conversation
             */
            if (list_user_to_create_dialog.length == 1) {
                /**
                 * premier boite de conversation
                 **/
                $('.boiteConversation:last').attr({
                    //"id": list_user_to_create_dialog[0].to
                    "id":User.to
                });
                conversationOneShow = true;
                $('.boiteConversation:last').find('.user_img').attr({
                    "src": list_user_to_create_dialog[0].img
                });
                $('.boiteConversation:last').find('.user_info').children('span').text("Conversation avec " + list_user_to_create_dialog[0].login);
                $('.boiteConversation:last').find('.user_info').children('span').attr({
                    "id": list_user_to_create_dialog[0].to
                });
                $('.boiteConversation:last').find('.img_cont').children('span').removeClass().addClass(list_user_to_create_dialog[0].status);

                $('.type_msg:last').attr({
                    "id": User.to
                });
                $('.msg_content:last').attr({
                    "id": User.to
                });
                //$('.msg_content:last').html("");

                $('.boiteConversation:last').find('.content_msg').attr({
                    "id": User.to
                });
                $('.boiteConversation:last').css({
                    "display":"block"
                });

                $('.lastMsg:last').attr({
                    "id": User.to
                });

                /**
                 * ato ny chargen ny conversation reetra tany alu
                 */
                $('.boiteConversation:last').children('.card-body').show();
                //napina t@ sabotsy
                if(bgCardBody){
                    $('.boiteConversation:last').children('.card-header').css({
                        "background":bgCardBody
                    });
                }
                $('.type_msg:last').click(function(){
                    if(bgCardBody){
                        $('.boiteConversation:last').children('.card-header').css({
                        "background":bgCardBody
                        });
                        bgCardBody = "";
                    }
                });
                
                $('.boiteConversation:last').find('.card-header').css({
                            'background':'rgba(0, 0, 0, 0.03)'
                });
                            
                $('.boiteConversation:last').find('.container_message').html("");
            
                $('.boiteConversation:last').find('.send_btn').attr({
                    "id":User.to
                })
                

            } else {

                if (conversationOneShow) {

                    $('.boiteConversation:first').find('.send_btn').attr({
                        "id":User.to
                    })
                    $('.boiteConversation:first').attr({
                        "id": User.to
                    });
                    /**
                     * deuxième boite de conversation
                     */
                    if (list_user_to_create_dialog[0] != list_user_to_create_dialog[1]) {
                        $('.boiteConversation:first').find('.user_img').attr({
                            "src": list_user_to_create_dialog[1].img
                        });
                        $('.boiteConversation:first').find('.user_info').children('span').text("Conversation avec " + list_user_to_create_dialog[1].login);
                        $('.boiteConversation:first').find('.user_info').children('span').attr({
                            "id": list_user_to_create_dialog[1].to
                        });
                        $('.boiteConversation:first').find('.img_cont').children('span').removeClass().addClass(list_user_to_create_dialog[1].status);
                        $('.boiteConversation:first').css({
                            "display": "block"
                        });
                        $('.msg_content:first').attr({
                            "id": User.to
                        });
                        //$('.msg_content:first').html("");
                        $('.type_msg:first').attr({
                            "id": User.to
                        });
                        
                        $('.lastMsg:first').attr({
                            "id":User.to
                        });
                        
                        /**
                         * ato ny chargen ny conversation reetra tany alu le deuxième panel
                         */
                        $('.boiteConversation:first').children('.card-body').show();

                        
                        $('.boiteConversation:first').find('.card-header').css({
                            'background':'rgba(0, 0, 0, 0.03)'
                        });
                            
                        $('.boiteConversation:first').find('.container_message').html("");   
                    }
                }
            }
            nbPanelShow ++;
            if(nbPanelShow >1){
                nbPanelShow = 1;
            }
            if(isNbPanelDecrement){
                if(nbPanelShow >1){
                    nbPanelShow = 1;
                }
            }
            
            var compteurDefined = false;
            var msgRecevingCount = null;
            
            if(user_clicked){
                
                var datas = {
                    "type": "other",
                    "me": User.to,
                    "data": {
                        
                        "destinataire": User.to,
                        
                    },
                    "messageData":{

                    }
                };
                
                if(list_message_not_view.length == 0){
                    list_message_not_view.push(datas);    
                }else{   
                    
                    for(var i=0; i<list_message_not_view.length; i++){
                        var userFound = false;
                        for(var j=0; j<list_message_not_view.length; j++){
                            if(list_message_not_view[j]["me"] == datas["me"] && list_message_not_view[j]["data"]["destinataire"] == datas["data"]["destinataire"]){
                                userFound = true;
                            }    
                        }
                        
                        if(!userFound){
                            list_message_not_view.push(datas); 
                            
                            //userFound = false;
                        }
                    }       
                }
                /**
                if(list_message_not_view.length > 2){
                    panel_block = [];
                    $('.boiteConversation').each(function(){
                        panel_block.push($(this));
                    });
                    for(var i=0; i<panel_block.length; i++){
                        for(var j=0; j<list_user_show_preview.length; j++){
                            if(panel_block[i].attr("id") == list_user_show_preview[j]){
                                list_user_show_preview.splice(j,1);
                            }
                        }
                    }
                    for(var i=0; i<list_user_show_preview.length; i++){
                        console.log($('.boiteConversation#'+list_user_show_preview[i]));
                    }
                    
                }
                *
                */
                if(list_id_and_compteur_msg_last.length > 0){
                    for(var i=0; i<list_id_and_compteur_msg_last.length; i++){
                        if(list_id_and_compteur_msg_last[i].id == User.to){
                            if(list_id_and_compteur_msg_last[i].cpt > 1){
                                data["compteur"] = list_id_and_compteur_msg_last[i].cpt;
                            }
                        }
                    }    
                }else{
                    data["compteur"] = 1;
                }
            }
            else{
                //console.log(msgReceving);
                user_clicked = true;
                user_closed_after_msg_receving = false;
                if(msgReceving){
                    data["compteur"] = 1;
                    //console.log(msgReceving);
                }else{
                    if(list_id_and_compteur_msg_last.length > 0){
                        compteurDefined = true;
                        for(var i=0; i<list_id_and_compteur_msg_last.length; i++){
                            if(list_id_and_compteur_msg_last[i].id == data["data"]["destinataire"]){
                                    data["compteur"] = list_id_and_compteur_msg_last[i].cpt;
                                 //list_id_and_compteur_msg_last[i].cpt = 1;
                                 //compteurMsgRecev = list_id_and_compteur_msg_last[i].cpt;
                                //data["data"]["destinataire"] = list_id_and_compteur_msg_last[i].id;
                            }
                        }
                    }    
                }
                /**
                * si il recoit une message, on
                reinitialise le compteur
                
                if(list_id_and_compteur_msg_last.length > 0){
                    compteurDefined = true;
                    for(var i=0; i<list_id_and_compteur_msg_last.length; i++){
                        if(list_id_and_compteur_msg_last[i].id == data["data"]["destinataire"]){
                            list_id_and_compteur_msg_last[i].cpt = 1;
                            compteurMsgRecev = list_id_and_compteur_msg_last[i].cpt;
                            //data["data"]["destinataire"] = list_id_and_compteur_msg_last[i].id;
                        }
                    }
                }
                //ws.send(JSON.stringify(data));
                //$('.lastMsg').trigger('click');
                **/
            }
            /**
            if(compteurDefined){
                data["compteur"] = compteurMsgRecev;
            }
            **/
            data["type"] = "chargementMessage";
            data["data"]["destinataire"] = User.to;
            /**
            on verifie si l'utilisateur a dejas cliqué sur lastMsg
            */
            if(user_clicked){
                
            }
            
            //data["compteur"] = 1;
            //console.log(data);
            //compteur = 1;
            //data["compteur"] = compteur;
            //console.log(data);
            ws.send(JSON.stringify(data));   
        }

        var $classUser = $('.lienbox');
        var block = [];
        var isEntryHere = false;
        var conversationOneShow = false;
        var isCompteurIncrement = false;
        /**
        voir les msgs suivants
        */
        $('.nextMsg').click(function(event) {
            isCompteurIncrement = false;
            event.preventDefault();

            for(var i=0; i<list_id_and_compteur_msg_last.length; i++){
                if(list_id_and_compteur_msg_last[i].id == $(this).attr("id")){
                    list_id_and_compteur_msg_last[i].cpt--;
                    data["type"] = "chargementMessage";
                    data["compteur"] = list_id_and_compteur_msg_last[i].cpt;
                    data["data"]["destinataire"] = list_id_and_compteur_msg_last[i].id;
                    data["resteHistorique"] = list_id_and_compteur_msg_last[i].cpt;
                    ws.send(JSON.stringify(data));
                }
            }
        });
        /**
        * voir la liste des message 1 jour avant la dernier message postée
        **/  
        $('.lastMsg').click(function(e){
            isCompteurIncrement = true;
            e.preventDefault();
            /**
            mise à jour
            
            fin
            */
            var lastMsg = $(this);
            
            var compteur = {
                    id: $(this).attr("id"),
                    cpt: 2
            };

            if(list_id_and_compteur_msg_last.length == 0){
                list_id_and_compteur_msg_last.push(compteur);
            }else{
                if(list_id_and_compteur_msg_last.length == 1){
                    if(list_id_and_compteur_msg_last[0].id == compteur.id){
                        list_id_and_compteur_msg_last[0].cpt = list_id_and_compteur_msg_last[0].cpt + 1;
                    }else{
                        list_id_and_compteur_msg_last.push(compteur);
                    }
                }else{
                    var userFound = false;
                    var pos = 0;
                    for(var i=0; i<list_id_and_compteur_msg_last.length; i++){    
                        if(list_id_and_compteur_msg_last[i].id == compteur.id){
                            userFound = true;
                            pos = i;
                        }
                    }
                    if(userFound){
                            list_id_and_compteur_msg_last[pos].cpt = list_id_and_compteur_msg_last[pos].cpt + 1;
                    }else{
                        list_id_and_compteur_msg_last.push(compteur);
                    }
                }
            }
            /**
            $classUser.each(function(){
                if($(this).attr("id") == lastMsg.attr("id")){
                    $(this).trigger('click');
                }
            });
            **/
            for(var i=0; i<list_id_and_compteur_msg_last.length;i++){
                if($(this).attr("id") == list_id_and_compteur_msg_last[i].id){
                    data["type"] = "chargementMessage";
                    data["compteur"] = list_id_and_compteur_msg_last[i].cpt;
                    data["data"]["destinataire"] = list_id_and_compteur_msg_last[i].id;

                    ws.send(JSON.stringify(data));
                }
            }
            //data["type"] = "chargementMessage";
            
        });

        
        //bulle à droite
        //click sur une destinataire de messagerie
        $('body').delegate('.lienbox', 'click', function (e) {
            
            e.preventDefault();
            //console.log("ok");
            nb_panel_show = 2;
            /**
             * si la list_user est vide, on initialise isEntryHere
             */
            if (list_user_to_create_dialog.length == 0) {
                isEntryHere = false;
            }
            
            var User = {
                to: $(this).attr("id"),
                img: $(this).find('img').attr("src"),
                login: $(this).find('.user_info').find('span').text().split(" - ")[1],
                status: $(this).find('.img_cont > span').attr("class"),
            };
            
            //alert($(this).attr("id"));
            
            
            

            info_destinataire = User;
            //2 no maximum ilay discussion instantanée
            if (list_user_to_create_dialog.length < 2) {
                //ato ny faharoa
                if (isEntryHere) {
                    //ka raha tsy mtovy ilay ul de apina
                    if (list_user_to_create_dialog[0].to != User.to) {
                        list_user_to_create_dialog.push(User);
                    }
                }
                //miditra ato alu volo mameno ilay ol volo
                if (!isEntryHere) {
                    //list_user_to_create_dialog.push(user_id);
                    list_user_to_create_dialog.push(User);
                    isEntryHere = true;
                    //Ajouter un id à partir du matricule de la bulle 2 droite 
                    //$(".float").attr('id', $(this).attr("id"));//jose
                    $(".float0").attr('id', $(this).attr("id"));//jose
                }
            }

            //raha ef 2 ilay olona
            if (list_user_to_create_dialog.length == 2) {
                //Ajouter un id à partir du matricule de la bulle 2 droite 
                //$(".float2").attr('id', $(this).attr("id"));//jose
                $(".float3").attr('id', $(this).attr("id"));//jose
                //ka ilay olona vao2 tsy mtovy @ ilay olona teo aloha, ovaina anin ul in ilay faha2
                if (list_user_to_create_dialog[1].to != User.to) {
                    list_user_to_create_dialog[1] = User;
                }
                //raha ef ao ilay olona de videna dul ilay liste de in ul in ian no atao ao anatin ilay liste
                if (list_user_to_create_dialog[0].to == User.to) {
                    list_user_to_create_dialog = [];
                    list_user_to_create_dialog.push(User);
                    //list_user_to_create_dialog[0] = User;
                }
            }
            list_panel_default = list_user_to_create_dialog;

            /**
            si la panel de conversation est tous caché
            et s'il y est dans la list des message not view
            on la retire dans ce dernier
            **/
            panel_block = [];
            $('.boiteConversation').each(function(){
                if($(this).css("display") == "block"){
                    var user = {
                        id: $(this).attr("id")  
                    }
                    panel_block.push(user);
                }
            })
            
            if($('#message_not_view').find('.contact').length > 0){
                if(panel_block.length == 0){
                    //for(var j=0; j<block_panel_after_show.length;j++){
                        var found = false;
                        if(User.to == $('#message_not_view').find('.contact#'+User.to).attr("id")){
                            found = true;
                            $('#message_not_view').find('.contact#'+User.to).remove();   
                        }
                    //}
                }

            }
        
            /**
             * affichage de panel de conversation
             */
            showPanelConversation(list_user_to_create_dialog, User);

            
            //manala ilay couleur sisa no atw ref mis message 
            var block_panel_after_show = [];
            panel_block = [];
            $('.boiteConversation').each(function(){
                if($(this).attr("id") != "element2"){
                    list_user_show_preview.push($(this).attr("id"));    
                    block.push($(this).attr("id"));
                }
                if($(this).css("display") == "block"){
                    block_panel_after_show.push($(this).attr("id"));
                    var user = {
                        id: $(this).attr("id")
                    }
                    panel_block.push(user);
                    
                    
                    //console.log(bgCardBody)
                }
            });

            
            /**
            si on clique un destinataire
            et qu'il est dans la panel des msg not view
            on l'enleve dans ce dernier
            **/
            if($('#message_not_view').find('.contact').length > 0){
                if(block_panel_after_show.length == 1){
                    for(var j=0; j<block_panel_after_show.length;j++){
                        var found = false;
                        if(block_panel_after_show[j] == $('#message_not_view').find('.contact#'+block_panel_after_show[j]).attr("id")){
                            found = true;
                            $('#message_not_view').find('.contact#'+block_panel_after_show[j].id).remove();   
                        }
                    }
                }

            }
            //manw ajout le click eo @ panel not view za zo
            
            if(panel_block.length == 2 && list_message_not_view.length>0){
                if(msgReceving){
                    list_message_not_view = tab_temp_old_data_delete.concat(list_message_not_view);
                    msgReceving = false;
                    return;
                }
                
                for(var i=0; i<list_message_not_view.length;i++){
                    for(var j=0; j<tab_temp_old_data_delete.length;j++){
                        if(list_message_not_view[i]["me"] == tab_temp_old_data_delete[j]["me"]){
                            tab_temp_old_data_delete.splice(j,1);
                        }
                    }
                }
                
                list_message_not_view = list_message_not_view.concat(tab_temp_old_data_delete);
                
                for(var i=0; i<panel_block.length; i++){
                    for(var j=0; j<list_message_not_view.length; j++){
                        if(panel_block[i].id == list_message_not_view[j]["me"]){
                            
                            tab_temp_old_data_delete.push(list_message_not_view[j]);
                            list_message_not_view.splice(j,1); 
                        }
                    }
                }
                //console.log("apres",list_message_not_view);
                showUserInPanelMsgNotView();
                for(var i=0; i<list_message_not_view.length;i++){
                    for(var j=0; j<tab_temp_old_data_delete.length;j++){
                        if(list_message_not_view[i]["me"] == tab_temp_old_data_delete[j]["me"]){
                            tab_temp_old_data_delete.splice(j,1);
                        }
                    }
                }
                list_message_not_view = list_message_not_view.concat(tab_temp_old_data_delete);
                
            }
                 
        });

        /**
         * fermeture messagerie
         */
         var panel_temp = [];
         
         //Permet de changer la valeur de l'identifiant du float lorsqu'on clique sur la boutton fermeture à droite jose
         
        
         $('#close2').click(function () {
            $(".float").removeAttr('id');
            var id_parent=$(".float2").parent().parent().parent().parent().attr("id");
            $(".float0").attr('id', id_parent);
             
            });
        

        //Permet de changer la valeur de l'identifiant du float lorsqu'on clique sur la boutton reduction à droite jose
        $('#boutton_reduction').click(function () {
        $(".float").removeAttr('id');//
        var id_parent=$(".float2").parent().parent().parent().parent().attr("id");
        $(".float0").attr('id', id_parent);
         
        });
            
         
         /**
         $('#close2').click(function () {
            $(".float").removeAttr('id');
            var id_parent=$(".float2").parent().parent().parent().parent().attr("id");
            $(".float").attr('id', id_parent);
             
            });
        

        //Permet de changer la valeur de l'identifiant du float lorsqu'on clique sur la boutton reduction à droite jose
        $('#boutton_reduction').click(function () {
        $(".float").removeAttr('id');//
        var id_parent=$(".float2").parent().parent().parent().parent().attr("id");
        $(".float").attr('id', id_parent);
         
        });
             **/
        $('.close_conversation').click(function () {
            
            if(list_temp_msg_next_and_prev.length > 0){
                list_id_and_compteur_msg_last = list_temp_msg_next_and_prev;
            }
            
            
            var $parent = $(this).parents('.boiteConversation'); 
            var id_user = $(this).parents('.boiteConversation').attr("id");
            
            //eto za zo ny ulan de las bdb le list_message_not_view rehef manw permutation
            panel_block = [];
            
            $('.boiteConversation').each(function(){
                if($(this).css("display") == "block"){
                    var user = {
                        id: $(this).attr("id")
                    }
                    panel_block.push(user);
                }
            });
            panel_temp = panel_block;
            
            
            for(var i=0; i<list_message_not_view.length;i++){
                if(list_message_not_view[i]["me"] == id_user){
                    list_message_not_view.splice(i,1);
                    
                }
            }
            
            for(var i=0; i<list_user_to_create_dialog.length;i++){
                if(list_user_to_create_dialog[i].to == id_user){
                    list_user_to_create_dialog.splice(i,1);
                        
                }
            }
            
            for(var i=0; i<tab_temp_old_data_delete.length;i++){
                if(tab_temp_old_data_delete[i]["me"] == id_user){
                    tab_temp_old_data_delete.splice(i,1);
                    
                } 
            }
            
            $('.boiteConversation').css({
                "display": "none"
            });

            var panel_show = [];

            for(var i=0; i<panel_block.length;i++){
                for(var j=0; j<tab_temp_old_data_delete.length;j++){
                    if(panel_block[i].id == tab_temp_old_data_delete[j]["me"]){
                        tab_temp_old_data_delete.splice(j,1);
                    }    
                }
                if(panel_block[i].id == id_user){
                    panel_block.splice(i,1);
                }
                
            }
            //console.log(panel_block);

            if(panel_block.length > 0){
            
                for(var i=0; i<panel_block.length;i++){
                        var user = {
                            to: panel_block[i].id,
                            img: $('.lienbox#'+panel_block[i].id).find('img').attr("src"),
                            login:$('.lienbox#'+panel_block[i].id).find('.user_info').find('span').text().split(" - ")[1],
                            status:$('.lienbox#'+panel_block[i].id).find('.img_cont > span').attr("class")
                        }
                    panel_show.push(user);
                }
                user_clicked = false;

                showPanelConversation(panel_show, panel_show[0]); 
            }
           

        });
        
         $('#minimize_messagerie').click(function () {
            var $card_body = $(this).parents('.chatbox').children('.card-body');
            $card_body.slideToggle();
         });
        


        /**
        * minimize messagerie
         * 
         */
        var $minimize_messagerie = $('.minimize');
        var user_closed_after_msg_receving = true;
        $minimize_messagerie.click(function () {
            var users_deletes = [];
            var $parent = $(this).parents('.boiteConversation');
            
            var User = {
                to: $parent.find('.user_info').children('span').attr("id"),
                img: $parent.find('.user_img').attr("src")
            };

            panel_block = [];

            var array_temp = [];
            for (var i = 0; i < list_user_to_create_dialog.length; i++) {
                if (list_user_to_create_dialog[i].to != User.to) {
                    array_temp.push(list_user_to_create_dialog[i]);
                }
            }

            list_user_to_create_dialog = array_temp;

            $('.boiteConversation').css({
                "display": "none"
            });

            if (list_user_to_create_dialog.length == 0) {
                
                if(msgReceving){
                   
                    msgReceving = false;
                    panel_block = [];
                }
                if(list_message_not_view.length == 1){
                    
                    for(var i=0; i<list_message_not_view.length; i++){
                        if(list_message_not_view[i]["me"] == $parent.find('.user_info').children('span').attr("id")){
                            //tab_temp_old_data_delete.push(list_message_not_view[i]);
                            list_message_not_view.splice(i,1);
                        }
                    }
                }
                
                //panel_block = [];  
                $('.boiteConversation').each(function(){
                    if($(this).css("display") == "block"){
                        var user = {
                            id:$(this).attr("id")
                        }
                        panel_block.push(user);
                    }
                });
                
                var datas = {
                    "type": "other",
                    "me": $parent.find('.user_info').children('span').attr("id"),
                    "data": {
                        
                        "destinataire": $parent.find('.user_info').children('span').attr("id"),
                    },
                    "messageData":{

                    }
                };
                   
                list_message_not_view.push(datas);  
                //tab_temp_old_data_delete.push(datas);

                
                showUserInPanelMsgNotView($('#message_not_view'), list_message_not_view);

                $('.type_msg').click(function(){
                    $('.boiteConversation:last').children('.card-header').css({
                        "background":'rgba(0, 0, 0, 0.03)'
                    });
                });
                
                return;
            }
            /**
             * affichage de conversation
             */

            showPanelConversation(list_user_to_create_dialog, list_user_to_create_dialog[0], true);
            /**
            if(!msgReceving){
                    console.log("makato");
                    $('.boiteConversation').find('.card-header').css({
                        "background":'crimson'
                })
            }**/
            //eto mbl tsy met
            
              
            $('.boiteConversation').each(function(){
                if($(this).css("display") == "block"){
                    var user = {
                        id:$(this).attr("id")
                    }
                    panel_block.push(user);
                }
            });
            
            if(!user_closed_after_msg_receving){

                var datas = {
                    "type": "other",
                    "me":User.to,
                    "data": {
                        
                        "destinataire": User.to
                    },
                    "messageData":{

                    }
                };
                if(evit_not_doublons){
                    
                    var user_do_appended = false;
                    for(var i=0; i<list_message_not_view.length; i++){
                        if(list_message_not_view[i]["me"] == datas["me"]){
                            //tab_temp_old_data_delete.push(list_message_not_view[i]);
                            list_message_not_view.splice(i,1);
                            list_message_not_view.push(datas);
                            user_do_appended = true;
                        }
                    }
                    if(!user_do_appended){
                        list_message_not_view.push(datas);
                    }
                }else{
                    list_message_not_view.push(datas);
                }
            }
            
            for(var i=0; i<list_message_not_view.length; i++){
                for(var j=0; j<panel_block.length; j++){
                    if(list_message_not_view[i]["me"] == panel_block[j].id){
                        tab_temp_old_data_delete.push(list_message_not_view[i]);
                        user = list_message_not_view[i];
                        list_message_not_view.splice(i,1);
                        //tab_temp.push(list_message_not_view[i]);
                    }    
                }
            }
            //list_message_not_view = tab_temp_old_data_delete.concat()
            //console.log("apres",list_message_not_view);
            //console.log("ato za zo ny ulan las doublons dul le sary de vo clicken de mial dul");
            showUserInPanelMsgNotView($('#message_not_view'), list_message_not_view);
            
        });
    });

    /**
     *recherche d'utilisateurs dans la messagerie
     */
    $(function () {
        $('input.search').keyup(function (e) {
            var value = $(this).val();

            var reg = / /;
            var expression = reg.compile(value,"i");

            $('.contacts').each(function(){
                if(value != ""){
                    $(this).css({"display":"block"});//d'abord on reaffiche cette element
                    if(!expression.test($(this).attr("id")) && !expression.test($(this).attr("data-id"))){
                        $(this).css({
                           "display":'none'
                        });
                    }
                }else{
                    $('ui').css({
                        "display":'block'
                    })
                }
            });
        });
    });