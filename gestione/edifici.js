let f = getParameter("flag");
if(f) changeContent(f);

var mappaliNewEdCount = 1;
var validNewEdSubmit = false;

function reloadPageWithFlag(flag){
	window.location.replace("/gestione/edifici.php?flag="+flag);
}

function addFiledMappale() {
	let field = document.createElement('input');
	field.name = 'mappNewEd'+mappaliNewEdCount;
	field.className = 'mappale';
	field.placeholder = mappaliNewEdCount+'° mappale';
	field.type = 'text';
	field.pattern = '\\d{1,4}';
	field.setAttribute('onkeyup', 'checkIfMappaleIsFree(this);')
	let div = document.createElement('div');
	div.appendChild(field);
	div.appendChild(document.createElement('span'));
	$('#mappali-new-ed').append(div);
	mappaliNewEdCount++;
}

function checkIfMappaleIsFree(el) {
	let foglio = $('#foglio-new-ed').val();
	console.log(foglio);
	if(foglio){
		var request = $.ajax({
      url: "/runtime/handler.php",
      type: "POST",
      data: {"action":"checkMappale", "foglio" : foglio, "mappale" : el.value},
      dataType: "text"
    });
    request.fail(function(jqXHR, textStatus) {
        	alert('Errore: '+textStatus);
    });
    request.done(function(msg) {
			console.log(msg);
			let span = el.parentNode.lastChild;
			span.innerHTML = (msg=='OK'?'✔':'✖');
    });
	}else{
		el.parentNode.lastChild.innerHTML = 'Specificare il foglio';
	}
}

function checkAllMappali() {
	$(".mappale").each(function(){
		$(this).keyup();
	});
}

function submitNewEdificio() {
	//TODO
}
