/****************VARIABLES*****************/
var pratica;

/****************HANDLERS*****************/
function selectPratica(el) {
	pratica = el.firstChild.innerHTML;
	$('#selezione-pratica').hide();
	$('#main-div').show();
};

function showOnlyThatDiv(divClass, divID) {
	$('.'+divClass).each(function() { $(this).hide(); });
	if(divID) $('#'+divID).show();
}

function setOU1OU2(OU1, OU2) {
	alert(OU1+'   '+OU2);
}