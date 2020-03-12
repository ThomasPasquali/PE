var count = 0;
function addManyTOManyField(div, options, name='', initValue = null) {
	let select = $('<select></select>')
	if(name) select.attr('name', name+(count++));
	for (let option of options) {
		let op = $('<option></option>').text(option.Description).val(option.Value);
		if(option.Value == initValue) op.attr('selected', 'selected');
		select.append(op);
	}
	div.append($('<div></div>').append(select).append($('<button></button>').text('Elimina').click(function() { $(this).parent().remove(); })));
	div.css('display', 'inline-flex');
}

var countIntPers = 0;
var countIntSoc = 0;

$(document).ready(function() {
	$('#intestatari-persone .add').click(function() { addFieldIntestatarioPersona(); });
	$('#intestatari-societa .add').click(function() { addFieldIntestatarioSocieta(); });
	$('input[type="button"][name="delete"]').click((e) => {
		e.preventDefault();
		if(confirm('Sei sicuro di voler eliminare la pratica e tutti i suoi collegamenti?'))
			$('#formModifica').append('<input type="hidden" name="delete" value="delete">').submit();
	})
});

function addFieldIntestatarioPersona(id = '', descr = '') {
	let n = countIntPers++;
	let field = $('<input id="intestatario_persona_'+n+'" name="intestatario_persona_'+n+'" type="hidden" value="'+id+'">');
	let hintBox = $('<div id="hintsPersone'+n+'" class="hintBox"></div>');
	let input = $('<input id="input_intestatario_persona_'+n+'" type="text" value="'+descr+'">').keyup(function() { 
		updateHints('intestatarioPersona', $(this), '#hintsPersone'+n, '#intestatario_persona_'+n);
	}).click(function() { this.select(); });
	let wrapper = $('<div></div>').append(field, input, hintBox);
	wrapper.append($('<button>Elimina</button>').click(function() { wrapper.remove(); }));

	$('#intestatari-persone').prepend(wrapper);
}

function addFieldIntestatarioSocieta(id = '', descr = '') {
	let n = countIntSoc++;
	let field = $('<input id="intestatario_societa_'+n+'" name="intestatario_societa_'+n+'" type="hidden" value="'+id+'">');
	let hintBox = $('<div id="hintsSocieta'+n+'" class="hintBox"></div>');
	let input = $('<input id="input_intestatario_societa_'+n+'" type="text" value="'+descr+'">').keyup(function() { 
		updateHints('intestatarioSocieta', $(this), '#hintsSocieta'+n, '#intestatario_societa_'+n);
	}).click(function() { this.select(); });
	let wrapper = $('<div></div>').append(field, input, hintBox);
	wrapper.append($('<button>Elimina</button>').click(function() { wrapper.remove(); }));

	$('#intestatari-societa').prepend(wrapper);
}