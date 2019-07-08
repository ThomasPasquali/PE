/****************VARIABLES*****************/
var pratica;

/****************HANDLERS*****************/
function selectPratica(el) {
	pratica = el.firstChild.innerHTML;
	$('#selezione-pratica').hide();
	$('#main-div').show();
};

function showOnlyThatDiv(divCommonClasses, divClass) {
	divCommonClasses = '.'+divCommonClasses.replace( /(:|\.|\[|\])/g, "\\$1" ).replace(/ /, '.');
	$(divCommonClasses).each(function() { $(this).hide(); });
	$(divCommonClasses.replace(/(.*)level(\d+)(.*)/, function(fullMatch, a, b, c) { return a + 'level' + (Number(b) + 1) + c; })).each(function() { $(this).hide(); });
	if(divClass) $(divCommonClasses+'.'+divClass.replace( /(:|\.|\[|\])/g, "\\$1" )).show();
}

function setOU1OU2(OU1, OU2) {
	alert(OU1+'   '+OU2);
}