/****************VARIABLES*****************/
var pratica, ou1, ou2, um, formOneri;

/****************FUNCTIONS*****************/
function selectPratica(el) {
	pratica = el.firstChild.innerHTML;
	$('#selezione-pratica').hide();
	$('#container').css('display', 'grid');
	$('#calcola').show();
	$('#container').show();
};

var countAlloggi = 0;
function addFieldAlloggio() {
	let field = $('<input>');
	field.attr('type', 'number');
	field.attr('name', 'alloggio'+(countAlloggi++));
	field.attr('min', '1');
	field.attr('placeholder', 'Superficie in mq...');
	field.addClass('fieldAlloggio');
	$('#fields-alloggi').append(field);
}
addFieldAlloggio();

function checkANDsubmit() {
	let alloggi = [];
	//OU
	if(!ou1 || !ou2){
		alert('Inserire i dati inerenti agli oneri di urbanizzazione');
		return;
	}
	if($('input[name=imponibileOU]').val()<=0){
		alert('L\'imponibile dev\'essere una quantità possitiva è maggiore di 0');
		return;
	}
	//CC
	if($('.branch0.level0 select').val() == 'Residenza'){
		for (let alloggio of document.getElementsByClassName('fieldAlloggio'))
			if(alloggio.value && alloggio.value > 1)
				alloggi.push(alloggio.value);
		if(alloggi.length <= 0){
			alert('Inserire almeno la superficie di un alloggio');
			return;
		}
		
		if(!$('input[name=snr]').val()){
			alert('La superficie totale servizi e accessore dev\'essere una quantità possitiva è maggiore di 0');
			return;
		}
		if(!$('select[name=Caratteristiche_edificio]').val()){
			alert('Selezionare tra le caratteristiche edificio');
			return;
		}
		if(!$('select[name=Tipologia_edificio]').val()){
			alert('Selezionare la tipologia edificio');
			return;
		}
	}else if($('.branch0.level0 select').val() != 'Attività_produttiva'){
		if(!$('input[name=sn]').val()){
			alert('La superficie calpestabile dev\'essere una quantità possitiva è maggiore di 0');
			return;
		}
		if(!$('input[name=sa]').val()){
			alert('La superficie accessori dev\'essere una quantità possitiva è maggiore di 0');
			return;
		}
	}
	$('#form').append($('<input>').attr('name', 'alloggi').val(alloggi.join(',')));
	$('#form').append($('<input>').attr('name', 'OU1').val(ou1));
	$('#form').append($('<input>').attr('name', 'pratica').val(pratica));
	$('#form').append($('<input>').attr('name', 'OU2').val(ou2));
	$('#form').append($('<input>').attr('name', 'UM').val(um));
	$('#form').append($('<textarea></textarea>').attr('name', 'formOneri').val(JSON.stringify(formOneri).replace(/\s/, '_')));
	$('#form').submit();
}

function showOnlyThatDiv(divCommonClasses, divClass) {
	divCommonClasses = '.'+divCommonClasses.replace( /(:|\.|\[|\])/g, "\\$1" ).replace(/ /, '.');
	$(divCommonClasses).each(function() { 
		$(this).hide();
		$(this).removeClass('selezionato');
	});
	$(divCommonClasses.replace(/(.*)level(\d+)(.*)/, function(fullMatch, a, b, c) { 
		return a + 'level' + (Number(b) + 1) + c; 
	})).each(function() { 
		$(this).hide();
		$(this).removeClass('selezionato');
	});
	if(divClass) {
		$(divCommonClasses+'.'+divClass.replace( /(:|\.|\[|\])/g, "\\$1" )).show();
		$(divCommonClasses+'.'+divClass.replace( /(:|\.|\[|\])/g, "\\$1" )).addClass('selezionato');
	}
}

function setCoefficenti(OU1, OU2, UM) {
	ou1=OU1;
	ou2=OU2;
	um=UM;
	formOneri = serializeByClass('.selezionato');
	$('#coefficienti').hide();
	$('input[name=imponibileOU]').attr('placeholder', 'Imponibile in '+um);
	let div = $('<div></div>');
	for (let k in formOneri)
		div.append($('<p></p>').text(k+': ').append($('<span></span>').text(formOneri[k].replace( /_/, " "))));
	div.insertAfter($('#titolo-ou'));
	$('#inserimento-imponibile').show();
}

function serializeByClass(selector) {
	arr = {};
	$(selector).each(function() {
			arr[$(this).parent().children('h2').html().replace( /\s/, "_")] = $(this).attr('class').split(' ')[2];
	});
	return arr;
}