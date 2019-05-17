var fieldsFMNewEdCount = 1;
var fieldsFMEditingEdCount = 1;
var subalterniCount = 1;
var b;

/***********HANDLERS*************/
$('#submit-new-edificio').click(function() {
	
	$(".mappalenew").each(function(){
		$(this).keyup();
	});

	let ok = true;
	$('.esitoChecknew').each(function(){
		 if($(this).text() !== '✔') {
			 ok = false;
			 return;
		 }
	});
	
	if(!ok)
		displayMessage('Qualche foglio/mappale è incorretto (✖)', document.getElementById('container-new-ed'));
	else if(!$('#stradarioID-new-ed').val())
		displayMessage('Selezionare uno stradario', document.getElementById('container-new-ed'));
	else
		$('#form-new-ed').submit();
		
});

$('#submit-modifiche-edificio').click(function() {

	$(".mappaleediting").each(function(){
		$(this).keyup();
	});

	let ok = true;
	$('.esitoCheckediting').each(function(){
		 if($(this).text() !== '✔') {
			 ok = false;
			 return;
		 }
	});
	console.log(ok);
	if(!ok)
		displayMessage('Qualche foglio/mappale o subalterno è incorretto (✖)', document.getElementById('container-editing-ed'));
	else if(!$('#stradarioID-editing-ed').val())
		displayMessage('Selezionare uno stradario', document.getElementById('container-editing-ed'));
	else
		$('#form-edit-ed').submit();
});

/***************FUNCTIONS***************/

function changeContent(divID) {
	var contents = document.getElementsByClassName('content');
	for(var i = 0; i < contents.length; i++)
		contents.item(i).className = 'content';
	document.getElementById(divID).classList.add('active');
}

function addFieldFoglioMappale(newORediting = 'editing', foglio = '', mappale = '', isEX = false, idEdToExclude = '') {
	let count = newORediting == 'new' ? fieldsFMNewEdCount++ : fieldsFMEditingEdCount++;
	let fieldFoglio = $('<input>');
	let fieldMappale = $('<input>');
	let fieldEX = $('<input>');
	let delBtn = $('<input>');
	let span = $('<span></span>');
	span.addClass('esitoCheck'+newORediting);
	
	fieldFoglio.attr('name', 'foglio'+newORediting+count);
	fieldFoglio.addClass('foglio'+newORediting);
	fieldFoglio.attr('placeholder', 'Foglio '+count);
	fieldFoglio.attr('id', 'foglio-'+newORediting+'-ed'+count);
	fieldFoglio.attr('type', 'text');
	fieldFoglio.attr('pattern', '\\d{1,4}');
	fieldFoglio.attr('autocomplete', 'off');
	fieldFoglio.attr('value', foglio);
	fieldFoglio.keyup(function() {
		checkIfFoglioMappaleIsFree(fieldFoglio.val(), fieldMappale.val(), span, idEdToExclude);
	});
	
	fieldMappale.attr('name', 'mappale'+newORediting+count);
	fieldMappale.addClass('mappale'+newORediting);
	fieldMappale.attr('placeholder', 'Mappale '+count);
	fieldMappale.attr('id', 'mappale-'+newORediting+'-ed'+count);
	fieldMappale.attr('type', 'text');
	fieldMappale.attr('pattern', '\\w{1,6}');
	fieldMappale.attr('autocomplete', 'off');
	fieldMappale.attr('value', mappale);
	fieldMappale.keyup(function() {
		checkIfFoglioMappaleIsFree(fieldFoglio.val(), fieldMappale.val(), span, idEdToExclude);
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
	checkIfFoglioMappaleIsFree(fieldFoglio.val(), fieldMappale.val(), span, idEdToExclude);
}

function addFieldSubalterno(foglio, mappale, subalterno) {
	let count = subalterniCount++;
	let fieldMapp = $('<input>');
	let fieldFoglio = $('<input>');
	let fieldSub = $('<input>');
	let delBtn = $('<input>');
	let div = $('<div>');
	let span = $('<span>');
	span.addClass('esitoCheckediting');
	
	fieldMapp.attr('name', 'mappaleSubalterno'+count);
	fieldMapp.attr('placeholder', 'Mappale...');
	fieldMapp.attr('type', 'text');
	fieldMapp.attr('pattern', '\\w{1,6}');
	fieldMapp.attr('autocomplete', 'off');
	fieldMapp.attr('value', mappale);
	fieldMapp.keyup(function() {
		checkIfFoglioMappaleIsOnPage(fieldFoglio.val(), fieldMapp.val(), fieldSub.val(), span);
	});
	
	fieldFoglio.attr('name', 'foglioSubalterno'+count);
	fieldFoglio.attr('placeholder', 'Foglio...');
	fieldFoglio.attr('type', 'text');
	fieldFoglio.attr('pattern', '\\d{1,4}');
	fieldFoglio.attr('autocomplete', 'off');
	fieldFoglio.attr('value', foglio);
	fieldFoglio.keyup(function() {
		checkIfFoglioMappaleIsOnPage(fieldFoglio.val(), fieldMapp.val(), fieldSub.val(), span);
	});
	
	fieldSub.attr('name', 'subalterno'+count);
	fieldSub.attr('placeholder', 'Subalterno '+count);
	fieldSub.attr('type', 'text');
	fieldSub.attr('pattern', '\\d{1,4}');
	fieldSub.attr('value', subalterno);
	fieldSub.attr('autocomplete', 'off');
	fieldSub.keyup(function() {
		checkIfFoglioMappaleIsOnPage(fieldFoglio.val(), fieldMapp.val(), fieldSub.val(), span);
	});

	delBtn.attr('type', 'button');
	delBtn.attr('value', 'Elimina');
	delBtn.click(function(){ div.remove(); });

	div.append(fieldFoglio);
	div.append(fieldMapp);
	div.append(fieldSub);
	div.append(span);
	div.append(delBtn);
	
	$('#subalterni-editing-ed').append(div);
	checkIfFoglioMappaleIsOnPage(fieldFoglio.val(), fieldMapp.val(), fieldSub.val(), span);
}

function checkIfFoglioMappaleIsFree(foglio, mappale, span, edificioToExcludeID='') {
	if(!foglio)
		span.text('Specificare il foglio');
	else if(!mappale)
		span.text('Specificare il mappale');
	else{
		//db check TODO local check
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

function checkIfFoglioMappaleIsOnPage(foglio, mappale, subalterno, span){
	if(!foglio)
		span.text('Specificare il foglio');
	else if(!mappale)
		span.text('Specificare il mappale');
	else if(!subalterno)
		span.text('Specificare il subalterno');
	else{
		let exists = false;
		for (var i = 1; i <= subalterniCount; i++) 
			if($('input[name=foglioediting'+i+']').val() == foglio &&
				$('input[name=mappaleediting'+i+']').val() == mappale)
				exists = true;
		span.text((exists&&subalterno)?'✔':'✖');
	}
}

function editEdificio(ID){
	var input = $('<input>').attr("type", "hidden").attr("name", "editingEdificio").val(ID);
	$('#form-search-ed').append(input);
	$('#form-search-ed').submit();
}
