$(document).ready(() => {
	$('body').on('click', '.btnAttivaDisattiva', function() {
		let e = $(this);
		let wasAttivo = e.data('attivo');
		$.ajax({
			url: "/runtime/handler.php",
			type: "POST",
			data: {
				"action" : (wasAttivo?'deactivatePagamenti':'activatePagamenti'),
				"calcolo" : e.data('idcalcolo'),
				"pe_o_tec" : e.data('peotec')
			},
			dataType: "text",
			success : function(res) {
			  if(res)
				  alert(res);
			  else {
				  if(wasAttivo) e.parent().addClass('inattivo');
				  else 			e.parent().removeClass('inattivo');
				  e.data('attivo', !wasAttivo);
				  e.text(wasAttivo?'Attiva pagamenti':'Disattiva pagamenti');
			  }
		  }
		});
	});

	$('body').on('click', '.btnElimina', function() {
		let e = $(this);
		if(confirm('Sei sicuro di voler eliminare definitivamente il calcolo e tutti i pagamenti associati?'))
			$.ajax({
				url: "/runtime/handler.php",
				type: "POST",
				data: {
					"action" : 'deleteCalcolo',
					"calcolo" : e.data('idcalcolo'),
					"pe_o_tec" : e.data('peotec')
				},
				dataType: "text",
				success : function(res) {
					if(res) alert(res);
					else	window.location.reload();
			}
			});
	});
	
});

function eliminaPagamentoCC(pagamento, btn, pe_o_tec) {
	$.ajax({
        url: "/runtime/handler.php",
        type: "POST",
        data: { "action" : "eliminaPagamentoCC", "pagamento" : pagamento, "pe_o_tec" : pe_o_tec },
        dataType: "text",
        success : function(res) {
    	  if(res) alert(res);
    	  else btn.parent().remove();
      }
	});
}

function eliminaPagamentoOU(pagamento, btn, pe_o_tec) {
	$.ajax({
        url: "/runtime/handler.php",
        type: "POST",
        data: { "action" : "eliminaPagamentoOU", "pagamento" : pagamento, "pe_o_tec" : pe_o_tec },
        dataType: "text",
        success : function(res) {
    	  if(res) alert(res);
    	  else btn.parent().remove();
      }
	});
}

function aggiungiPagamentoOU(calcolo, pe_o_tec) {
	importo = $('#importoOU'+calcolo).val();
	if(!/^[1-9]\d*(((,\d{3}){1})?(\.\d{0,2})?)$/.test(importo)) { alert('Fornire un importo valido'); return; }
	data = $('#dataOU'+calcolo).val();
	$.ajax({
        url: "/runtime/handler.php",
        type: "POST",
        data: { "action" : "aggiungiPagamentoOU", "importo" : importo, "data" : data, "calcolo" : calcolo, "pe_o_tec" : pe_o_tec },
        dataType: "text",
        success : function(res) {
    	  if(res) alert(res);
    	  else location.reload();
      }
	});
}

function aggiungiPagamentoCC(calcolo, pe_o_tec) {
	importo = $('#importoCC'+calcolo).val();
	if(!/^[0-9]\d*(((,\d{3}){1})?(\.\d{0,2})?)$/.test(importo)) { alert('Fornire un importo valido'); return; }
	data = $('#dataCC'+calcolo).val();
	$.ajax({
        url: "/runtime/handler.php",
        type: "POST",
        data: { "action" : "aggiungiPagamentoCC", "importo" : importo, "data" : data, "calcolo" : calcolo, "pe_o_tec" : pe_o_tec },
        dataType: "text",
        success : function(res) {
    	  if(res) alert(res);
    	  else location.reload();
      }
	});
}

function addFieldsPagamentoCC(importo, data, pagamento, calcolo, pe_o_tec) {
	$('#pagamentiCC'+calcolo)
		.append($('<div></div>').addClass('pagamento')
			.append($('<p></p>').text('€'+importo+' '+data))
			.append($('<button></button>').text('Elimina pagamento')
			.click(function() { eliminaPagamentoCC(pagamento, $(this), pe_o_tec) })));
}

function addFieldsPagamentoOU(importo, data, pagamento, calcolo, pe_o_tec) {
	$('#pagamentiOU'+calcolo)
		.append($('<div></div>').addClass('pagamento')
			.append($('<p></p>').text('€'+importo+' '+data))
			.append($('<button></button>').text('Elimina pagamento')
			.click(function() { eliminaPagamentoOU(pagamento, $(this), pe_o_tec) })));
}