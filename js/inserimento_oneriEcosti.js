/****************VARIABLES*****************/
var pratica, ou1, ou2, um, formOneri, imponibile;

/****************HANDLERS*****************/
$('#btnBloccaOneri').click(function() {
	formOneri = serializeByClass('.selezionato');
	if($('#imponibile').val()<=0)
		alert('L\'imponibile dev\'essere una quantità possitiva è maggiore di 0');
	else
		imponibile = $('#imponibile').val();
});

/****************FUNCTIONS*****************/
function selectPratica(el) {
	pratica = el.firstChild.innerHTML;
	$('#selezione-pratica').hide();
	$('#main-div').show();
};

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
	$('#main-div').hide();
	$('#titolo-imponibile').html('Imponibile in '+um);
	$('#inserimento-imponibile').show();
}

function serializeByClass(selector) {
	arr = [];
	$(selector).each(function() {
			arr[$(this).parent().children('h2').html()] = $(this).attr('class').split(' ')[2];
	});
	return arr;
}