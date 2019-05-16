var fieldsFMNewEdCount = 1;
var fieldsFMEditingEdCount = 1;
var subalterniEditingEdCount = 1;
var b;

function changeContent(divID) {
	var contents = document.getElementsByClassName('content');
	for(var i = 0; i < contents.length; i++)
		contents.item(i).className = 'content';
	document.getElementById(divID).classList.add('active');
}

function addFieldFoglioMappale(newORediting = 'editing', isEX = false) {
	let count = newORediting == 'new' ? fieldsFMNewEdCount++ : fieldsFMEditingEdCount++;
	let fieldFoglio = $('<input>');
	let fieldMappale = $('<input>');
	let fieldEX = $('<input>');
	let delBtn = $('<input>');
	let span = $('<span></span>');
	span.className = 'esitiCheckMappaliNewEd';
	
	fieldFoglio.attr('name', 'foglio'+newORediting+count);
	fieldFoglio.addClass('foglio'+newORediting);
	fieldFoglio.attr('placeholder', 'Foglio '+count);
	fieldFoglio.attr('id', 'foglio-'+newORediting+'-ed'+count);
	fieldFoglio.attr('type', 'text');
	fieldFoglio.attr('pattern', '\\d{1,4}');
	fieldFoglio.attr('autocomplete', 'off');
	fieldFoglio.keyup(function() {
		checkIfFoglioMappaleIsFree(fieldFoglio.val(), fieldMappale.val(), span);
	});
	
	fieldMappale.attr('name', 'mappale'+newORediting+count);
	fieldMappale.addClass('mappale'+newORediting);
	fieldMappale.attr('placeholder', 'Mappale '+count);
	fieldMappale.attr('id', 'mappale-'+newORediting+'-ed'+count);
	fieldMappale.attr('type', 'text');
	fieldMappale.attr('pattern', '\\d{1,4}');
	fieldMappale.attr('autocomplete', 'off');
	fieldMappale.keyup(function() {
		checkIfFoglioMappaleIsFree(fieldFoglio.val(), fieldMappale.val(), span);
	});
	
	fieldEX.attr('name', 'ex'+newORediting+count);
	fieldEX.attr('type', 'checkbox');
	fieldEX.attr('value', 'EX');
	if(isEX) fieldEX.attr('checked', 'checked');
	
	delBtn.attr('type', 'button');
	delBtn.attr('value', 'Elimina');
	delBtn.click(function(){ div.remove(); });
	
	let div = $('<div></div>');
	div.append(fieldFoglio);
	div.append(fieldMappale);
	div.append($('<span></span>').text('EX'));
	div.append(fieldEX);
	div.append(span);
	div.append(delBtn);
	$('#fogli-mappali-'+newORediting+'-ed').append(div);
	checkIfFoglioMappaleIsFree(fieldFoglio.val(), fieldMappale.val(), span);
}

function addFieldSubalternoEditingEd(subalterno, mappale) {
	let fieldMapp = document.createElement('input');
	fieldMapp.name = 'mappSubEditingEd'+subalterniEditingEdCount;
	fieldMapp.className = 'mappaleSubEditingEd';
	fieldMapp.placeholder = 'Mappale...';
	fieldMapp.type = 'text';
	fieldMapp.pattern = '\\d{1,4}';
	fieldMapp.value = mappale;
	fieldMapp.setAttribute('onkeyup', 'checkIfMappaleIsOnPage(this);');
	fieldMapp.setAttribute('autocomplete', 'off');

	let fieldSub = document.createElement('input');
	fieldSub.name = 'subEditingEd'+subalterniEditingEdCount;
	fieldSub.className = 'subalternoEditingEd';
	fieldSub.placeholder = subalterniEditingEdCount+'° subalterno';
	fieldSub.type = 'text';
	fieldSub.pattern = '\\d{1,4}';
	fieldSub.value = subalterno;
	fieldSub.setAttribute('autocomplete', 'off');

	let div = document.createElement('div');

	let delBtn = document.createElement('input');
	delBtn.type = 'button';
	delBtn.value = 'Elimina subalterno';
	delBtn.addEventListener('click', function(){ div.remove(); });

	let span = document.createElement('span');
	span.className = 'esitiCheckMappaliEditingEd';

	div.appendChild(fieldMapp);
	div.appendChild(fieldSub);
	div.appendChild(delBtn);
	div.appendChild(span);

	$('#subalterni-editing-ed').append(div);
	checkIfMappaleIsOnPage(fieldMapp);
	subalterniEditingEdCount++;
}

function checkIfFoglioMappaleIsFree(foglio, mappale, span, edificioToExcludeID='') {
	if(!foglio)
		span.text('Specificare il foglio');
	else if(!mappale)
		span.text('Specificare il mappale');
	else{
		//TODO local check
		//db check
		var request = $.ajax({
	      url: "/runtime/handler.php",
	      type: "POST",
	      data: {"action":"checkMappale", 
	    	  		"foglio" : foglio, 
	    	  		"mappale" : mappale, 
	    	  		"edificioToExclude" : edificioToExcludeID},
	      dataType: "text"
	    });
	    request.done(function(msg) {
	    	//console.log(msg);
			span.text(msg=='OK'?'✔':'✖');
	    });
	}
}

function checkIfMappaleIsOnPage(field){
	let mappOnPage = [];
	$('.mappaleEditingEd').each(function(){
		mappOnPage.push($(this).val());
	});
	field.parentNode.lastChild.innerHTML = (mappOnPage.indexOf(field.value) >= 0)?'✔':'✖';
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
		displayMessage('Correggere i dati e riprovare', document.getElementById('container-new-ed'));
}

function submitModificheEdificio() {
	if(areAllMappaliOk('esitiCheckMappaliEditingEd'))
		$('#form-edit-ed').submit();
	else
		displayMessage('Correggi i dati e riprova', document.getElementById('container-editing-ed'));
}

function editEdificio(ID){
	var input = $('<input>').attr("type", "hidden").attr("name", "editingEdificio").val(ID);
	$('#form-search-ed').append(input);
	$('#form-search-ed').submit();
}
