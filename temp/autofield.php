<?php
    include_once '..\controls.php';
    $c = new Controls();
    
    if(!$c->logged()){
        header('Location: /index.php?err=Utente non loggato');
        exit();
    }
?>
<html>
<head>
	<link rel="stylesheet" href="/lib/mini-default.min.css">
	<script src="/lib/jquery-3.3.1.min.js"></script>
	<style type="text/css">
	   .hintBox{
	       background-color: #272727;
	       max-height: 200px;
	       overflow-y: scroll;
	   }
	   .hintBox a{
	       background-color: #272727;
	       display: block;
	       color: white;
	   }
	</style>
</head>
<body>
	<input id="field" type="text" onkeyup="updateHints(this, '#hintBox', 'tecnico', '#fieldValue');" onclick="this.select();">
	<input id="fieldValue" type="hidden">
	<div id="hintBox" class="hintBox"></div>
	<script type="text/javascript">
		function updateHints(field, hintBoxID, type, targetFieldID) {
			
			var request = $.ajax({
  	          url: "/runtime/handler.php",
  	          type: "POST",
  	          data: {
          	  	          	"action" : "hint",
                  			"type" : type,
                  			"search" : field.value
              			},
  	          dataType: "text"
  	        });

      		request.error = function(msg) {
    	          alert( "Errore: " + msg);
	        };
  		
      	    request.fail(function(jqXHR, textStatus) {
      	         request.error(textStatus);
  	        });
  	        
  	        request.done(function(hints) {
  	  	        hints = JSON.parse(hints);
  	        	$(hintBox).empty();
  	        	$(hintBoxID).css("display", "block");
  	        	for (let el of hints) {
  	  	        	let a = document.createElement('a');
  	  	        	a.innerHTML = el.Cognome+' '+el.Nome;
  	  	        	a.setAttribute('onclick', 'setValue("'+targetFieldID+'", "'+el.ID+'"); setValue("#'+field.id+'", "'+a.innerHTML+'"); $("'+hintBoxID+'").css("display", "none");');
					hintBox.append(a);
				}
  	        });
		}

		function setValue(elID, newValue) {
			$(elID).val(newValue);
		}
	</script>
</body>
</html>