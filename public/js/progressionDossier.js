$(document).ready(function(){   

  for (const [key, value] of Object.entries(data)) {
    console.log("MON DATA",data);
    if(key=="SUBDIVISION"){
      jQuery('#sub').text(Object.keys(data)[0]);jQuery('#td_sub').text(value.volume);
      jQuery('#td_sub_temps').text(Math.round((value.temps_realisation) * 100) / 100);
    }
   
}
      $("#form_dossier").on("input", function(e){
    function verif(){
      e.preventDefault(); //empêcher une action par défaut
              var form_url =  $("#myform").attr('action'); //récupérer l'URL du formulaire
              var form_method = $("#myform").attr("method"); //récupérer la méthode GET/POST du formulaire
              var form_data = $("#myform").serialize(); //Encoder les éléments du formulaire pour la soumission
              $.ajax({
                url : form_url,
                type: form_method,
                data : form_data,
                beforeSend: function() {
                  $("#imgSpinner1").css("display","block");
                    $("#imgSpinner1").show();         
                  }
              }).done(function(response){ 
                $("#imgSpinner1").hide();
                //$("#res").html(response);
                jQuery("body").html(response);
                    //document.body.html(response); //Insertion du donnée
                    jQuery('#form_sub').removeClass("sousDossier");
                    jQuery('#form_sub').addClass("form-control");
                    jQuery("#form_sub").prepend("#form_sub");
                    jQuery('.required').text("Sous dossier");
                    jQuery(".required").detach().appendTo("#Form_insertion");
                    jQuery("#form_sub").detach().appendTo("#Form_insertion");
               
              });
    }
    
    function ma_fonction(){
        if(document.getElementById('form_dossier').value != ''){
          verif();
        return false;
        }else return true;
        }
        ma_fonction();
              
      });
     
    });
    
    
    
    
    /***
    $(document).ready(function(){
      $("#myform").on("input", function(e){
              e.preventDefault(); //empêcher une action par défaut
            var $form = $('#myform');
            // When sport gets selected ...
            $form.change(function() {
              // ... retrieve the corresponding form.
              var $form = $(this).closest('form');
              // Simulate form data, but only include the selected sport value.
              var data = {};
              data[$form.attr('name')] = $form.val();
              // Submit data via AJAX to the form's action path.
              $.ajax({
                url : $form.attr('action'),
                type: $form.attr('method'),
                data : data,
                complete: function(html) {
                  // Replace current position field ...
                  $('#form_dossier').replaceWith(
                    // ... with the returned one from the AJAX response.
                    $(html.responseText).find('#form_dossier')
                  );
                  // Position field now displays the appropriate positions.
                }
              });
            });
      });
    });
    
    **/
    
    
    
    $('select#form_dossier').select2({matcher: select2matchCustomUser});
    /*On va utiliser le système de recherche classique du plugin select2*/
    var defaultMatcher = $.fn.select2.defaults.defaults.matcher;
    function select2matchCustomUser(params, data) {
     
        /* Si l'option est "Autre" on l'affiche toujours */
        if (params.term && data.id == "autre") {
            return data;
        }
        /* Sinon utilisation de la recherche classique */
        return defaultMatcher(params, data);
    }
    
    /**
    
    var data = {{datas|json_encode()|raw}};
    var datasets = [];
    var dataChart = {
        labels: {{labels|json_encode()|raw}},
        datasets: null
    };
                for(var dossier in data){
                    var clr = "rgba("+Math.round(Math.random() * (255 - 1) + 1) +", "+Math.round(Math.random() * (255 - 1) + 1) +", "+Math.round(Math.random() * (255 - 1) + 1) +", 1)";
                    var obj = {
                        label: dossier,
                        data: data[dossier]['taux'],
                        borderWidth: 3,
                        fill: false,
                        backgroundColor: clr,
                        borderColor: clr
                    };
                    datasets.push(obj);
                
            }
                
                var ctx = document.getElementById("canvas").getContext('2d');
                dataChart.datasets = datasets;
                
                var myChart = new Chart(ctx, {
                    type: 'line',
                    data: dataChart,
                    options: {
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true
                                },
                                scaleLabel: {
                                display: true,
                                labelString: "",
                                fontSize: 20 
                              }
                            }]
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    color: 'rgb(255, 99, 132)',
                                    fontColor: "#000080",
                                }
                            }
            }
                    }
                });
    **/
    
    $("#imgSpinner1").hide();
    jQuery('#form_sub').removeClass("sousDossier");
    jQuery('#form_sub').addClass("form-control");
    jQuery("#form_sub").prepend("#form_sub");
    jQuery('.required').text("Sous dossier");
    jQuery(".required").detach().appendTo("#Form_insertion");
    jQuery("#form_sub").detach().appendTo("#Form_insertion");