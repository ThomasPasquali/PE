function changeContent(divID) {
	var contents = document.getElementsByClassName('content');
	for(var i = 0; i < contents.length; i++)
		contents.item(i).className = 'content';
	document.getElementById(divID).classList.add('active');
    document.getElementById(divID).classList.add('flipInX');
    document.getElementById(divID).classList.add('animated');
}

function hide(id) {
	document.getElementById(id).style.display = 'none';
}

function show(id) {
	document.getElementById(id).style.display = 'block';
}

/*
class FieldsGenIntPers{

	constructor(fieldsIntPers, fieldOptions){
		this.count = 0;
		this.fieldsIntPers = fieldsIntPers;
		this.fieldOptions = fieldOptions;
	}

	addField() {
		var el = document.createElement('select');
		el.name = 'intestatario_persona_'+this.count;
		el.className = 'js-example-basic-single';
		el.style = 'width: 100%;';
		el.innerHTML = this.fieldOptions
		var div = document.createElement('div');
		div.appendChild(el);
		this.fieldsIntPers.appendChild(div);
		this.count++;
		$(document).ready(function() {$('.js-example-basic-single').select2();});
	}

	removeField() {
		if(this.count > 1){
			this.fieldsIntPers.removeChild(this.fieldsIntPers.lastChild);
			this.count--;
		}
	}
}

class FieldsGenIntSoc{

	constructor(fieldsIntSoc, fieldOptions){
		this.count = 0;
		this.fieldsIntSoc = fieldsIntSoc;
		this.fieldOptions = fieldOptions;
	}

	addField() {
		var el = document.createElement('select');
		el.name = 'intestatario_societa_'+this.count;
		el.className = 'js-example-basic-single';
		el.style = 'width: 100%;';
		el.innerHTML = this.fieldOptions
		var div = document.createElement('div');
		div.appendChild(el);
		this.fieldsIntSoc.appendChild(div);
		this.count++;
		$(document).ready(function() {$('.js-example-basic-single').select2();});
	}

	removeField() {
		if(this.count > 1){
			this.fieldsIntSoc.removeChild(this.fieldsIntSoc.lastChild);
			this.count--;
		}
	}
}*/
