var mappaliNewEdCount = 1;
var mappaliEditingEdCount = 1;
var subalterniEditingEdCount = 1;
var b;

function changeContent(divID) {
	var contents = document.getElementsByClassName('content');
	for(var i = 0; i < contents.length; i++)
		contents.item(i).className = 'content';
	document.getElementById(divID).classList.add('active');
}

function addFiledMappaleNewEd() {
	let field = document.createElement('input');
	field.name = 'mappNewEd'+mappaliNewEdCount;
	field.className = 'mappaleNewEd';
	field.placeholder = mappaliNewEdCount+'° mappale';
	field.type = 'text';
	field.pattern = '\\d{1,4}';
	field.setAttribute('onkeyup', 'checkIfMappaleIsFree(this, "foglio-new-ed", "mappaleNewEd");');
	field.setAttribute('autocomplete', 'off');
	let div = document.createElement('div');
	div.appendChild(field);
	let span = document.createElement('span');
	span.className = 'esitiCheckMappaliNewEd';
	div.appendChild(span);
	$('#mappali-new-ed').append(div);
	checkIfMappaleIsFree(field, "foglio-new-ed", "mappaleNewEd");
	mappaliNewEdCount++;
}

function addFiledMappaleEditingEd(mappale, isEX, edificio) {
	let field = document.createElement('input');
	field.name = 'mappEditingEd'+mappaliEditingEdCount;
	field.className = 'mappaleEditingEd';
	field.placeholder = mappaliEditingEdCount+'° mappale';
	field.type = 'text';
	field.pattern = '\\d{1,4}';
	field.value = mappale;
	field.setAttribute('onkeyup', 'checkIfMappaleIsFree(this, "foglio-editing-ed", "mappaleEditingEd", '+edificio+');');
	field.setAttribute('autocomplete', 'off');
	
	let p = document.createElement('p');
	p.innerHTML = 'EX';
	
	let fieldEX = document.createElement('input');
	fieldEX.name = 'isExMappEditingEd'+mappaliEditingEdCount;
	fieldEX.type = 'checkbox';
	fieldEX.value = 'EX'
	if(isEX) fieldEX.checked = 'checked';
	
	let span = document.createElement('span');
	span.className = 'esitiCheckMappaliEditingEd';
	
	let div = document.createElement('div');
	
	let delBtn = document.createElement('input');
	delBtn.type = 'button';
	delBtn.value = 'Elimina mappale';
	delBtn.addEventListener('click', function(){ div.remove(); });
	
	div.appendChild(field);
	div.appendChild(p);
	div.appendChild(fieldEX);
	div.appendChild(delBtn);
	div.appendChild(span);
	
	$('#mappali-editing-ed').append(div);
	checkIfMappaleIsFree(field, "foglio-editing-ed", "mappaleEditingEd", edificio)
	mappaliEditingEdCount++;
}

function addFiledSubalternoEditingEd(subalterno, edificio) {
	let field = document.createElement('input');
	field.name = 'subEditingEd'+subalterniEditingEdCount;
	field.className = 'subalternoEditingEd';
	field.placeholder = subalterniEditingEdCount+'° subalterno';
	field.type = 'text';
	field.pattern = '\\d{1,4}';
	field.value = subalterno;
	field.setAttribute('autocomplete', 'off');
	
	let div = document.createElement('div');
	
	let delBtn = document.createElement('input');
	delBtn.type = 'button';
	delBtn.value = 'Elimina subalterno';
	delBtn.addEventListener('click', function(){ div.remove(); });
	
	div.appendChild(field);
	div.appendChild(delBtn);
	
	$('#subalterni-editing-ed').append(div);
	subalterniEditingEdCount++;
}

function checkIfMappaleIsFree(el, foglioFieldID, mappaliClass, edificioToExcludeID = '') {
	let foglio = $('#'+foglioFieldID).val();
	if(foglio){
		//local check TODO NON VAAAAAAA
		b = true;
		$('.'+mappaliClass).each(function(){
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
      data: {"action":"checkMappale", "foglio" : foglio, "mappale" : el.value, "edificioToExclude" : edificioToExcludeID},
      dataType: "text"
    });
    request.fail(function(jqXHR, textStatus) {
    	alert('Errore: '+textStatus);
    });
    request.done(function(msg) {
    	//console.log(msg);
		let span = el.parentNode.lastChild;
		span.innerHTML = (msg=='OK'?'✔':'✖');
		return msg=='OK';
    });
	}else{
		el.parentNode.lastChild.innerHTML = 'Specificare il foglio';
		return false;
	}
}

function checkAllMappaliNewEd() {
	$(".mappaleNewEd").each(function(){
		$(this).keyup();
	});
}

function checkAllMappaliEditingEd() {
	$(".mappaleEditingEd").each(function(){
		$(this).keyup();
	});
}

function areAllMappaliOk(classCheckMappali){
	b = true;
	$("."+classCheckMappali).each(function(){
		 if($(this).html() !== '✔') b = false;
	});
	return b;
}

function submitNewEdificio() {
	if(areAllMappaliOk('esitiCheckMappaliNewEd')&&$('#stradarioID-new-ed').val())
		$('#form-new-ed').submit();
	else
		displayMessage('Completare tutti i campi e riprovare', document.getElementById('container-new-ed'));
}

function submitModificheEdificio() {
	if(areAllMappaliOk('esitiCheckMappaliEditingEd'))
		$('#form-edit-ed').submit();
	else
		displayMessage('Correggi i dati e riprovare', document.getElementById('container-editing-ed'));
}

function editEdificio(ID){
	var input = $('<input>').attr("type", "hidden").attr("name", "editingEdificio").val(ID);
	$('#form-search-ed').append(input);
	$('#form-search-ed').submit();
}
