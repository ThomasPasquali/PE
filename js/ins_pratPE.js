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
   					console.log(msg);
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

function freezeEdificio() {
	let edID = $('#ricerca-edificio-field').val();
	if(edID){
		$('#dati-pratica').show();
		$('#dati-edificio').hide();
		$('#info-edificio').html('Edificio N° '+edID);
		$('#edificio').val(edID);
	}else
		alert('Selezionare un edificio');
}
