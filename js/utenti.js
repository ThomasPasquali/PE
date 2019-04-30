if(getParameter("flag") == 'gestione')
	changeContent('gestione');
else
	changeContent('richieste');


function activate(email, element) {
	var request = $.ajax({
      url: "/runtime/handler.php",
      type: "POST",
      data: {"action":"accountActivation", "email" : email},
      dataType: "text"
    });
    request.fail(function(jqXHR, textStatus) {
        	displayMessage(textStatus, element.parentNode.parentNode.parentNode);
    });
    request.done(function(msg) {
    	if(msg == 'DONE'){
    		displayMessage('Utente attivato', element.parentNode.parentNode.parentNode, 'info');
    		element.parentNode.remove();
    	}else
    		displayMessage(msg, element.parentNode.parentNode.parentNode);
    });
}

function deactivate(email, element) {
	var request = $.ajax({
      url: "/runtime/handler.php",
      type: "POST",
      data: {"action":"accountDeactivation", "email" : email},
      dataType: "text"
    });
    request.fail(function(jqXHR, textStatus) {
        	displayMessage(textStatus, element.parentNode.parentNode.parentNode);
    });
    request.done(function(msg) {
    	if(msg == 'DONE'){
    		displayMessage('Utente disattivato', element.parentNode.parentNode.parentNode, 'info');
    		element.parentNode.remove();
    	}else
    		displayMessage(msg, element.parentNode.parentNode.parentNode);
    });
}

function changeType(email, type, element) {
	var request = $.ajax({
      url: "/runtime/handler.php",
      type: "POST",
      data: {"action":"userPermissionsChange", "email" : email, "type" : type},
      dataType: "text"
    });
    request.fail(function(jqXHR, textStatus) {
        	displayMessage(textStatus, element.parentNode.parentNode.parentNode);
    });
    request.done(function(msg) {
    	if(msg == 'DONE'){
    		reloadPageWithFlag('gestione');
    	}else
    		displayMessage(msg, element.parentNode.parentNode.parentNode);
    });
}

function deleteAccount(email, element) {
	console.log("diosc");
	var result = confirm("Sei sicuro di voler eliminare l'account?");
	if (result) {
		var request = $.ajax({
          url: "/runtime/handler.php",
          type: "POST",
          data: {"action":"accountDelete", "email" : email},
          dataType: "text"
        });
	    request.fail(function(jqXHR, textStatus) {
	        	displayMessage(textStatus, element.parentNode.parentNode.parentNode);
        });
        request.done(function(msg) {
        	if(msg == 'DONE'){
        		displayMessage('Utente eliminato', element.parentNode.parentNode.parentNode, 'info');
        		element.parentNode.remove();
        	}else
        		displayMessage(msg, element.parentNode.parentNode.parentNode);
        });
	}
}

function reloadPageWithFlag(flag){
	window.location.replace("/gestione/utenti.php?flag="+flag);
}