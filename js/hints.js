function updateHints(type, field, hintBoxID, targetFieldID) {
	if(field instanceof jQuery)
		field = field.get(0);
	
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
			console.log(hints);
			if(hints){
				hints = JSON.parse(hints);
	      	$(hintBoxID).empty();
	      	$(hintBoxID).css("display", "block");
	      	for (let el of hints) {
	        	let a = document.createElement('a');
	        	a.innerHTML = el.Description;
	        	a.setAttribute('onclick', 'setValue("'+targetFieldID+'", "'+el.ID+'"); setValue("#'+field.id+'", "'+a.innerHTML+'"); $("'+hintBoxID+'").css("display", "none");');
	        	$(hintBoxID).append(a);
				}
			}});
}


function setValue(elID, newValue) {
	$(elID).val(newValue);
}