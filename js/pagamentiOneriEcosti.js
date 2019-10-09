function attivaPagamenti(calcolo, btn) {
	$.ajax({
        url: "/runtime/handler.php",
        type: "POST",
        data: { "action" : "activatePagamenti", "calcolo" : calcolo },
        dataType: "text",
        success : function(res) {
    	  if(res)
    		  alert(res);
    	  else {
    		  $(btn).html('Disattiva pagamenti');
    		  $(btn).click(function() {
				disattivaPagamenti(calcolo, btn);
    		  });
    		  $(btn).parent().removeClass('inattivo');
    	  }
      }
	});
}

function disattivaPagamenti(calcolo, btn) {
	$.ajax({
        url: "/runtime/handler.php",
        type: "POST",
        data: { "action" : "deactivatePagamenti", "calcolo" : calcolo },
        dataType: "text",
        success : function(res) {
    	  if(res)
    		  alert(res);
    	  else {
    		  $(btn).html('Attiva pagamenti');
    		  $(btn).click(function() {
				attivaPagamenti(calcolo, btn);
    		  });
    		  $(btn).parent().addClass('inattivo');
    	  }
      }
	});
}

function eliminaPagamentoCC(pagamento, btn) {
	$.ajax({
        url: "/runtime/handler.php",
        type: "POST",
        data: { "action" : "eliminaPagamentoCC", "pagamento" : pagamento },
        dataType: "text",
        success : function(res) {
    	  if(res) alert(res);
    	  else btn.parent().remove();
      }
	});
}

function eliminaPagamentoOU(pagamento, btn) {
	$.ajax({
        url: "/runtime/handler.php",
        type: "POST",
        data: { "action" : "eliminaPagamentoOU", "pagamento" : pagamento },
        dataType: "text",
        success : function(res) {
    	  if(res) alert(res);
    	  else btn.parent().remove();
      }
	});
}

function aggiungiPagamentoOU(calcolo) {
	importo = $('#importoOU'+calcolo).val();
	if(!/^[1-9]\d*(((,\d{3}){1})?(\.\d{0,2})?)$/.test(importo)) { alert('Fornire un importo valido'); return; }
	data = $('#dataOU'+calcolo).val();
	$.ajax({
        url: "/runtime/handler.php",
        type: "POST",
        data: { "action" : "aggiungiPagamentoOU", "importo" : importo, "data" : data, "calcolo" : calcolo},
        dataType: "text",
        success : function(res) {
    	  if(res) alert(res);
    	  else location.reload();
      }
	});
}

function aggiungiPagamentoCC(calcolo) {
	importo = $('#importoCC'+calcolo).val();
	if(!/^[0-9]\d*(((,\d{3}){1})?(\.\d{0,2})?)$/.test(importo)) { alert('Fornire un importo valido'); return; }
	data = $('#dataCC'+calcolo).val();
	$.ajax({
        url: "/runtime/handler.php",
        type: "POST",
        data: { "action" : "aggiungiPagamentoCC", "importo" : importo, "data" : data, "calcolo" : calcolo},
        dataType: "text",
        success : function(res) {
    	  if(res) alert(res);
    	  else location.reload();
      }
	});
}

function addFieldsPagamentoCC(importo, data, pagamento, calcolo) {
	$('#pagamentiCC'+calcolo)
		.append($('<div></div>').addClass('pagamento')
			.append($('<p></p>').text('€'+importo+' '+data))
			.append($('<button></button>').text('Elimina pagamento')
			.click(function() { eliminaPagamentoCC(pagamento, $(this)) })));
}

function addFieldsPagamentoOU(importo, data, pagamento, calcolo) {
	$('#pagamentiOU'+calcolo)
		.append($('<div></div>').addClass('pagamento')
			.append($('<p></p>').text('€'+importo+' '+data))
			.append($('<button></button>').text('Elimina pagamento')
			.click(function() { eliminaPagamentoOU(pagamento, $(this)) })));
}