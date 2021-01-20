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

/************GRIDS**************/
var grids = [], gridOptions = [], data = [];

var gridOptionsDefault = {
    defaultColDef: {
        sortable: true,
        resizable: true,
        editable: false,
        filter: 'agTextColumnFilter'
	},
	pagination: true,
	paginationPageSize: 20,
	suppressHorizontalScroll: true,
	domLayout: 'autoHeight',
	
}

keys = {persone: 'persona', societa: 'societa', tecnici: 'tecnico', imprese: 'impresa'};
for (const key in keys) {
	gridOptions[key] = deepClone(gridOptionsDefault);
	gridOptions[key].onCellDoubleClicked = function (e) {
		let win = window.open('reports/anagrafica.php?'+keys[key]+'='+e.data['ID'], '_blank');
		win.focus();
	};
}

gridOptions['persone'].columnDefs = [
	{field:"ID", filter: 'agNumberColumnFilter', comparator: function(id1, id2) { return parseInt(id1)-parseInt(id2); }},
	{field:"Cognome"},
	{field:"Nome"},
	{field:"Codice_fiscale"}
];

gridOptions['societa'].columnDefs = [
	{field:"ID", filter: 'agNumberColumnFilter', comparator: function(id1, id2) { return parseInt(id1)-parseInt(id2); }},
	{field:"Intestazione"},
	{field:"Partita_iva"}
];

gridOptions['tecnici'].columnDefs = [
	{field:"ID", filter: 'agNumberColumnFilter', comparator: function(id1, id2) { return parseInt(id1)-parseInt(id2); }},
	{field:"Cognome"},
	{field:"Nome"},
	{field:"Codice_fiscale"},
	{field:"Partita_iva"}
];

gridOptions['imprese'].columnDefs = [
	{field:"ID", filter: 'agNumberColumnFilter', comparator: function(id1, id2) { return parseInt(id1)-parseInt(id2); }},
	{field:"Intestazione"},
	{field:"Codice_fiscale"},
	{field:"Partita_iva"}
];


$(document).ready(async function () {
	for (const key in gridOptions) {
		grids[key] = new agGrid.Grid($('#grid'+key)[0], gridOptions[key]);
		data[key] = await getData(key);
		gridOptions[key].api.setRowData(data[key]);
		
	}
});


/*****************FUNCTIONS******************/

function getData(which) {
	return $.ajax({
		url: "runtime/handler.php",
		type: "POST",
		data: {action: "data", which: which},
		dataType: "json",
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			console.log(textStatus);
			console.log(errorThrown);
		}
    });
}

function deepClone(obj) {
	if (typeof obj !== "object")
	  return obj;
	else {
	  let newObj = (typeof obj === "object" && obj.length !== undefined) ? [] : {};
	  for (let key in obj)
		if (key)
		  newObj[key] = deepClone(obj[key]);
	  return newObj;
	}
}

/*****************HANDLERS******************/
$('#intAnag .header button').on('click', function() {
	$('#intAnag .grid').each(function () { $(this).hide(); });
	$('#'+$(this).data('target')).show();
	for (const key in gridOptions) 
		gridOptions[key].columnApi.sizeColumnsToFit($('#intAnag').width());
});