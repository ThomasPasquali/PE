/****************SETUP*****************/
$('input').each(function() {
	$(this).attr('autocomplete', 'off');
})

/****************HANDLERS*****************/
$('input[name=anno]').keyup(function(){
  let val = $(this).val()+'';
  if(val.length == 4 && $('input[name=numero]').val().length == 0){
    $.ajax({
      url: "../runtime/handler.php",
      type: "POST",
      data: {'action' : 'getPraticaNumberForAnno', 'anno' : val},
      success: function(msg) { console.log(msg);$('input[name=numero]').val((msg.length == 0?'1':msg)); }
    });
  }else if(val.length > 4)
    $(this).val(val.substr(0,4))
});


/****************FUNCTIONS*****************/

(function ($) {
    $.fn.serializeFormJSON = function () {

        var o = {};
        var a = this.serializeArray();
        $.each(a, function () {
            if (o[this.name]) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };
})(jQuery);

$( "#ricerca-edificio > input" ).keyup(function() {
  ricercaEdificio($('#ricerca-edificio'));
});

function ricercaEdificio(form){
  var data = form.serializeFormJSON();
  let request = $.ajax({
    url: "../runtime/handler.php",
    type: "POST",
    data: data,
    error: function(jqXHR, textStatus) {
            alert('Errore: '+textStatus);
           },
   success: function(msg) {
			$('#risultati-ricerca-edificio').empty();
			for (let ed of msg) {
				let div = $('<div></div>');
				div.addClass('risultato-ricerca-edificio');
				for(let attr in ed){
					let row = $('<div></div>');
					let p = $('<p></p>');
					p.text(ed[attr]);
					let strong = $('<strong></strong>');
					strong.text(attr + ':');
					row.append(strong);
					row.append(p);
					div.append(row);
				}
				
				let idDiv = $('<div></div>');
				idDiv.css('display', 'none');
				idDiv.html(ed['ID'])
				idDiv.addClass('id-edificio-selezionato');
				div.append(idDiv);
				
				div.click(function(){
					if($(this).attr('class') == 'risultato-ricerca-edificio'){
						$('#edifici-selezionati').append(div);
						$(this).attr('class', 'edificio-selezionato')
					}else{
						$('#risultati-ricerca-edificio').append(div);
						$(this).attr('class', 'risultato-ricerca-edificio')
					}
				});

				$('#risultati-ricerca-edificio').append(div);
			}
    }
  });
}

var edifici = [];
function freezeEdifici() {
	$('.edificio-selezionato').each(function() {
		edifici.push($(this).children('.id-edificio-selezionato').html());
	});
	if(edifici.length > 0){
		$('#dati-pratica').show();
		$('#dati-edificio').hide();
		if(edifici.length > 1) $('#info-edificio').html('Edifici N° '+edifici.join(', '));
		else 						 $('#info-edificio').html('Edificio N° '+edifici[0]);
	}else
		alert('Selezionare almeno un edificio');
}

var mappaliCount = 1;
function addFieldFoglioMappale() {
	if(edifici.length > 0){
		/*TODO let mappali = getFogliMappaliEdifici(edifici);
		console.log(mappali);
		if(mappali.length > 0){
			let select = $('<select></select>');
			select.attr('name', 'mapp'+mappaliCount);
			select.addClass('mappale');

			for (let mapp of mappali) {
				let option = $('<option></option>');
				option.val(mapp['Mappale']);
				option.html(mapp['Mappale']+(mapp['EX']?'(EX)':''));
				select.append(option);
			}

			let div = $('<div></div>');

			let delBtn = $('<button></button>');
			delBtn.click(function() {
				div.remove();
			});
			delBtn.html('-');
			delBtn.css('background-color', 'red');

			div.append(select);
			div.append(delBtn);

			$('#mappali').append(div);
			mappaliCount++;
		}else
			alert('L\'edificio non ha mappali associati');*/
	}else{
		$('#dati-pratica').hide();
		$('#dati-edificio').show();
		alert('Selezionare un edificio');
	}
}

function getFogliMappaliEdifici(edifici) {
	//TODO
	let request = $.ajax({
	    url: "../runtime/handler.php",
	    type: "POST",
	    data: {'action' : 'getFogliMappaliEdifici', 'edifici' : edifici},
	    success: function (response) {
	    	console.log(response);
	    },
	    error: function (jqXHR, exception) {
	        var msg = '';
	        if (jqXHR.status === 0) {
	            msg = 'Not connect.\n Verify Network.';
	        } else if (jqXHR.status == 404) {
	            msg = 'Requested page not found. [404]';
	        } else if (jqXHR.status == 500) {
	            msg = 'Internal Server Error [500].';
	        } else if (exception === 'parsererror') {
	            msg = 'Requested JSON parse failed.';
	        } else if (exception === 'timeout') {
	            msg = 'Time out error.';
	        } else if (exception === 'abort') {
	            msg = 'Ajax request aborted.';
	        } else {
	            msg = 'Uncaught Error.\n' + jqXHR.responseText;
	        }
	        console.log(msg);
	    }
	});
	console.log(request);
}

var subalterniCount = 1;
function addFieldSubalterno() {
	if(edID){
		let subalterni = getSubalterniEdificio(edID);
    console.log(subalterni);
		if(subalterni.length > 0){
			let select = $('<select></select>');
			select.attr('name', 'sub'+subalterniCount);
			select.addClass('subalterno');

			for (let sub of subalterni) {
				let option = $('<option></option>');
				option.val(sub['Subalterno']+'mapp'+sub['Mappale']);
				option.html('Subalterno '+sub['Subalterno']+' del mappale '+sub['Mappale']);
				select.append(option);
			}

			let div = $('<div></div>');

			let delBtn = $('<button></button>');
			delBtn.click(function() {
				div.remove();
			});
			delBtn.html('-');
			delBtn.css('background-color', 'red');

			div.append(select);
			div.append(delBtn);

			$('#subalterni').append(div);
			subalterniCount++;
		}else
			alert('L\'edificio non ha subalterni associati');
	}else{
		$('#dati-pratica').hide();
		$('#dati-edificio').show();
		alert('Selezionare un edificio');
	}
}

function getSubalterniEdificio(ed) {
	return JSON.parse($.ajax({
	    url: "../runtime/handler.php",
	    type: "POST",
	    data: {'action' : 'getSubalterniEdificio', 'edificio' : ed},
	    async: false
	}).responseText);
}

var intestatariPersonaCount = 1;
function addFieldIntestatarioPersona() {
  let searchField = $('<input>');
  searchField.attr('id', 'intestatarioPersona'+intestatariPersonaCount);
  searchField.attr('type', 'text');
  searchField.click(function () {
    $(this).select();
  });
  let tmp = intestatariPersonaCount;
  searchField.keyup(function () {
    updateHints('intestatarioPersona', $(this), '#hintsIntestatarioPersona'+tmp, '#intestatarioPersona'+tmp+'ID');
  });

  let idField = $('<input>');
  idField.attr('id', 'intestatarioPersona'+intestatariPersonaCount+'ID');
  idField.attr('name', 'intestatarioPersona'+intestatariPersonaCount);
  idField.attr('type', 'hidden');

  let hintsDiv = $('<div></div>')
  hintsDiv.addClass('hintBox');
  hintsDiv.attr('id', 'hintsIntestatarioPersona'+intestatariPersonaCount);

  let div = $('<div></div>');

  let delBtn = $('<button></button>');
  delBtn.click(function () {
    div.remove();
  });
  delBtn.html('-');

  div.append(searchField);
  div.append(idField);
  div.append(hintsDiv);
  div.append(delBtn);

  $('#fieldsIntPers').append(div);
  intestatariPersonaCount++;
}

var intestatariSocietaCount = 1;
function addFieldIntestatarioSocieta() {
  let searchField = $('<input>');
  searchField.attr('id', 'intestatarioSocieta'+intestatariSocietaCount);
  searchField.attr('type', 'text');
  searchField.click(function () {
    $(this).select();
  });
  let tmp = intestatariSocietaCount;
  searchField.keyup(function () {
    updateHints('intestatarioSocieta', $(this), '#hintsIntestatarioSocieta'+tmp, '#intestatarioSocieta'+tmp+'ID');
  });

  let idField = $('<input>');
  idField.attr('id', 'intestatarioSocieta'+intestatariSocietaCount+'ID');
  idField.attr('name', 'intestatarioSocieta'+intestatariSocietaCount);
  idField.attr('type', 'hidden');

  let hintsDiv = $('<div></div>')
  hintsDiv.addClass('hintBox');
  hintsDiv.attr('id', 'hintsIntestatarioSocieta'+intestatariSocietaCount);

  let div = $('<div></div>');

  let delBtn = $('<button></button>');
  delBtn.click(function () {
    div.remove();
  });
  delBtn.html('-');

  div.append(searchField);
  div.append(idField);
  div.append(hintsDiv);
  div.append(delBtn);

  $('#fieldsIntSoc').append(div);
  intestatariSocietaCount++;
}