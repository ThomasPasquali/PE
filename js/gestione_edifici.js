var mappaliNewEdCount = 1;
var b;

function changeContent(divID) {
	var contents = document.getElementsByClassName('content');
	for(var i = 0; i < contents.length; i++)
		contents.item(i).className = 'content';
	document.getElementById(divID).classList.add('active');
}

function addFiledMappale() {
	let field = document.createElement('input');
	field.name = 'mappNewEd'+mappaliNewEdCount;
	field.className = 'mappale';
	field.placeholder = mappaliNewEdCount+'° mappale';
	field.type = 'text';
	field.pattern = '\\d{1,4}';
	field.setAttribute('onkeyup', 'checkIfMappaleIsFree(this);');
	field.setAttribute('autocomplete', 'off');
	let div = document.createElement('div');
	div.appendChild(field);
	let span = document.createElement('span');
	span.className = 'esitiCheckMappali';
	div.appendChild(span);
	$('#mappali-new-ed').append(div);
	checkIfMappaleIsFree(field);
	mappaliNewEdCount++;
}

function checkIfMappaleIsFree(el) {
	let foglio = $('#foglio-new-ed').val();
	if(foglio){
		//local check TODO NON VAAAAAAA
		b = true;
		$(".mappale").each(function(){
			//console.log('TAsssss:'+$(this).val());
			if($(this).val() == foglio)
				return false;
		});
		if(!b) return false;
		//console.log('db check');

		//db check
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
			let span = el.parentNode.lastChild;
			span.innerHTML = (msg=='OK'?'✔':'✖');
			return msg=='OK';
    });
	}else{
		el.parentNode.lastChild.innerHTML = 'Specificare il foglio';
		return false;
	}
}

function checkAllMappali() {
	$(".mappale").each(function(){
		$(this).keyup();
	});
}

function areAllMappaliOk(){
	b = true;
	$(".esitiCheckMappali").each(function(){
		 if($(this).html() !== '✔') b = false;
	});
	return b;
}

function submitNewEdificio() {
	if(areAllMappaliOk()&&$('#stradarioID-new-ed').val())
		$('#form-new-ed').submit();
	else
		displayMessage('Completare tutti i campi e riprovare', document.getElementById('container-new-ed'));
}


function editEdificio(ID){
	var input = $('<input>').attr("type", "hidden").attr("name", "editingEdificio").val(ID);
	$('#form-editing-ed').append(input);
	$('#form-editing-ed').submit();
}
