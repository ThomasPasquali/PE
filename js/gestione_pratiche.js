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
