/****************SETUP*****************/

$('input').each(function() {
	$(this).attr('autocomplete', 'off');
})

/****************HANDLERS*****************/

$( "#ricerca-edificio > input" ).keyup(function() {
  ricercaEdificio($('#ricerca-edificio'));
});

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


$('#form-pratica').submit(function(e) {
	console.log(edifici);
	let i = 1;
	for (let edificio of edifici){
		let field = $('<input>');
		field.attr('type', 'hidden');
		field.attr('name', 'edificio'+(i++));
		field.attr('value', edificio);
		$(this).append(field);
	}
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

function ricercaEdificio(form){
  var data = form.serializeFormJSON();
  let request = $.ajax({
    url: "../runtime/handler.php",
    type: "POST",
    data: data,
    error: function(jqXHR, textStatus) {
            alert('Errore: '+textStatus);
						console.log(jqXHR);
           },
   success: function(msg) {
			$('#risultati-ricerca-edificio').empty();
			for (let ed of msg) {
				//controllo presenza in edifici selezionati
				var giaPresente = false
				$('.edificio-selezionato .id-edificio-selezionato').each(function() {
					if($(this).html() == ed['ID'])
						giaPresente = true;
				});
				if(!giaPresente){
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
    }
  });
}

var edifici = [], mappali, subalterni;
function freezeEdifici() {
	edifici = [];
	$('.edificio-selezionato').each(function() {
		edifici.push($(this).children('.id-edificio-selezionato').html());
	});
	if(edifici.length > 0){
		refreshMappaliESubalterni();
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
		if(mappali.length > 0){
			let select = $('<select></select>');
			select.attr('name', 'foglio-mappale'+mappaliCount);
			select.addClass('mappale');

			for (let mapp of mappali) {
				let option = $('<option></option>');
				option.val(mapp['Foglio']+'-'+mapp['Mappale']);
				option.html('F.'+mapp['Foglio']+' m.'+mapp['Mappale']+(mapp['EX']?' (EX)':''));
				select.append(option);
			}

			let div = $('<div></div>');

			let delBtn = $('<button></button>');
			delBtn.click(function() {
				div.remove();
			});
			delBtn.html('Elimina foglio-mappale');
			delBtn.addClass('delete-button');

			div.append(select);
			div.append(delBtn);

			$('#mappali').append(div);
			mappaliCount++;
		}else
			alert('L\'edificio non ha mappali associati');
	}else{
		$('#dati-pratica').hide();
		$('#dati-edificio').show();
		alert('Selezionare un edificio');
	}
}

var subalterniCount = 1;
function addFieldSubalterno() {
	if(edifici.length > 0){
		if(subalterni.length > 0){
			let select = $('<select></select>');
			select.attr('name', 'foglio-mappale-subalterno'+subalterniCount);
			select.addClass('subalterno');

			for (let sub of subalterni) {
				let option = $('<option></option>');
				option.val(sub['Foglio']+'-'+sub['Mappale']+'-'+sub['Subalterno']);
				option.html('Subalterno '+sub['Subalterno']+' del F.'+sub['Foglio']+' m.'+sub['Mappale']);
				select.append(option);
			}

			let div = $('<div></div>');

			let delBtn = $('<button></button>');
			delBtn.click(function() {
				div.remove();
			});
			delBtn.html('Elimina subalterno');
			delBtn.addClass('delete-button');

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
  searchField.attr('autocomplete', 'off');
  searchField.addClass('intestatarioPersona');
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
  delBtn.addClass('delete-button');
  delBtn.html('Elimina intestatario persona');

  div.append(searchField);
  div.append(delBtn);
  div.append(idField);
  div.append(hintsDiv);
  
  $('#fieldsIntPers').append(div);
  intestatariPersonaCount++;
}

var intestatariSocietaCount = 1;
function addFieldIntestatarioSocieta() {
  let searchField = $('<input>');
  searchField.attr('id', 'intestatarioSocieta'+intestatariSocietaCount);
  searchField.attr('type', 'text');
  searchField.attr('autocomplete', 'off');
  searchField.addClass('intestatarioSocieta');
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
  delBtn.html('Elimina intestatario societ&aacute;');
  delBtn.addClass('delete-button');

  div.append(searchField);
  div.append(delBtn);
  div.append(idField);
  div.append(hintsDiv);
  
  $('#fieldsIntSoc').append(div);
  intestatariSocietaCount++;
}

function refreshMappaliESubalterni() {
	$.ajax({
	    url: "../runtime/handler.php",
	    type: "POST",
	    data: {'action' : 'getFogliMappaliEdifici', 'edifici' : edifici},
	    success: function (response) {
	    	mappali = response;
	    },
	    error: function (jqXHR, exception) {
	        console.log('Errore nella richiesta dei fogli mappali');
	    }
	});
	$.ajax({
	    url: "../runtime/handler.php",
	    type: "POST",
	    data: {'action' : 'getSubalterniEdifici', 'edifici' : edifici},
	    success: function (response) {
	    	subalterni = response;
	    },
	    error: function (jqXHR, exception) {
	        console.log('Errore nella richiesta dei subalterni');
	    }
	});
}

function backToEdificiSelection() {
	if (confirm('Se ritorni alla selezione edifici i mappali ed i subalterni saranno resettati, proseguire?')) {
	    $('#mappali').empty();
	    $('#subalterni').empty();
	    $('#dati-pratica').hide();
		$('#dati-edificio').show();
		$('#info-edificio').html("");
	}
}
