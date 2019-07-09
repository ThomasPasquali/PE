/****************VARIABLES*****************/
var pratica, ou1, ou2, um, formOneri, imponibile;

/****************HANDLERS*****************/
$('#btnBloccaOneri').click(function() {
	if($('#imponibile').val()<=0)
		alert('L\'imponibile dev\'essere una quantità possitiva è maggiore di 0');
	else{
		imponibile = $('#imponibile').val();
		$('#inserimento-imponibile').show();
		$('#cc').show();
	}
});

/****************FUNCTIONS*****************/
function selectPratica(el) {
	pratica = el.firstChild.innerHTML;
	$('#selezione-pratica').hide();
	$('#main-div').show();
};

function addFieldAlloggio() {
	$('#fields-alloggi').append($('<input>').attr('type', 'number').attr('placeholder', 'Superficie in mq...').addClass('fieldAlloggio'));
}
addFieldAlloggio();

function fineInserimentoAlloggi() {
	
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
	$('#main-div').hide();
	$('#imponibile').attr('placeholder', 'Imponibile in '+um);
	let div = $('<div></div>');
	for (let k in formOneri)
		div.append($('<p></p>').text(k+': ').append($('<span></span>').text(formOneri[k].replace( /_/, " " ))));
	div.insertAfter($('#titolo-imponibile'));
	$('#inserimento-imponibile').show();
}

function serializeByClass(selector) {
	arr = [];
	$(selector).each(function() {
			arr[$(this).parent().children('h2').html()] = $(this).attr('class').split(' ')[2];
	});
	return arr;
}