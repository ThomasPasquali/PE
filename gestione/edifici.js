let f = getParameter("flag");
if(f) changeContent(f);

var mappaliNewEdCount = 1;
var validNewEdSubmit = false;

function reloadPageWithFlag(flag){
	window.location.replace("/gestione/edifici.php?flag="+flag);
}

function addFiledMappale() {
	let field = document.createElement('input');
	field.name = 'mappNewEd'+mappaliNewEdCount;
	field.placeholder = mappaliNewEdCount+'° mappale';
	field.type = 'text';
	field.pattern = '\\d{1,4}';
	field.onKeyUp = function(){
		console.log('stoca');
		//TODO NON VA
	}
	let div = document.createElement('div');
	div.appendChild(field);
	$('#mappali-new-ed').append(div);
	mappaliNewEdCount++;
}

function submitNewEdificio() {
	//TODO
}



