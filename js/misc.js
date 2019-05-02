function changeContent(divID) {
	var contents = document.getElementsByClassName('content');
	for(var i = 0; i < contents.length; i++)
		hide(contents.item(i));
	show(document.getElementById(divID));
}

function hide(element) {
	element.style.display = 'none';
}

function show(element) {
	element.style.display = 'block';
}

function getParameter(name){
   if(name=(new RegExp('[?&]'+encodeURIComponent(name)+'=([^&]*)')).exec(location.search))
      return decodeURIComponent(name[1]);
}

function displayMessage(msg, element, type='') {
    let div = document.createElement('div');
    div.classList.add('alert');
    if(type !== '') div.classList.add(type);

    let span = document.createElement('span');
    span.classList.add('closebtn');
    span.innerHTML = '&times';
    span.onclick = function(){
	    var div = this.parentElement;
	    div.style.opacity = "0";
	    setTimeout(function(){ div.style.display = "none"; }, 600);
	  }

    div.innerHTML = msg;
    div.appendChild(span);

	element.insertBefore(div, element.children[1]);
};

/***********HINTS*************/

function updateHints(type, field, hintBoxID, targetFieldID) {
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
