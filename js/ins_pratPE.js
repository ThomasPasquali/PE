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
    dataType: "text",
    error: function(jqXHR, textStatus) {
            alert('Errore: '+textStatus);
           },
   success: function(msg) {
   					msg = JSON.parse(msg)
   					//console.log(msg);
   					$('#risultati-ricerca-edificio').empty();
   					for (let ed of msg) {
   						let div = $('<div></div>');
   						div.addClass('risultato-ricerca-edificio');
   						div.click(function(){
   							$('#ricerca-edificio-field').val(ed.ID);
   						});
   						
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
   						
   						$('#risultati-ricerca-edificio').append(div);
					}
   					
	            }
  });
}

var edID;
function freezeEdificio() {
	edID = $('#ricerca-edificio-field').val();
	if(edID){
		$('#dati-pratica').show();
		$('#dati-edificio').hide();
		$('#info-edificio').html('Edificio N° '+edID);
		$('#edificio').val(edID);
	}else
		alert('Selezionare un edificio');
}

var mappaliEditingEdCount = 1;
function addFieldMappale() {
	if(edID){
		let mappali = getMappaliEdificio(edID);
		if(mappali){
			let select = $('<select></select>');
			select.attr('name', 'mapp'+mappaliEditingEdCount);
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
			mappaliEditingEdCount++;
		}else
			alert('L\'edificio non ha mappali associati');
	}else{
		$('#dati-pratica').hide();
		$('#dati-edificio').show();
		alert('Selezionare un edificio');
	}
}

var res;
function getMappaliEdificio(ed) {
	return JSON.parse($.ajax({
	    url: "../runtime/handler.php",
	    type: "POST",
	    data: {'action' : 'getMappaliEdificio', 'edificio' : ed},
	    dataType: "text",
	    async: false
	}).responseText);
}
