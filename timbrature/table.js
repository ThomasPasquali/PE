/***********************TABLE FUNCTIONS**************************/
var sumNotEmpty = function(values, data, calcParams) {
    var tot = 0;
    values.forEach(function(value){ if(value.length > 0) tot ++; });
    return tot;
}

var sumOrari = function(values, data, calcParams){
    let totSecs = 0;
    values.forEach(function(value){
    	let matches = value.match(/^(-?)(\d*):(\d*)$/);
    	totSecs += HMToSeconds(matches[1], matches[2], matches[3]);
    });
    return secondsToHM(totSecs);
}

function secondsToHM(seconds) {
	let sign = (seconds >= 0 ? '' : '-');
	seconds = Math.abs(parseInt(seconds));
	let s = parseInt((seconds / 60) % 60);
	return sign+(parseInt(seconds/ 3600))+":"+(s < 10 ? "0"+s : s);//.(int)($seconds % 60).'s';
}

var cmpOrari = function(a, b, aRow, bRow, column, dir, sorterParams) {
	let mA = a.match(/^(-?)(\d*):(\d*)$/);
	let mB = b.match(/^(-?)(\d*):(\d*)$/);
	return HMToSeconds(mA[1], mA[2], mA[3]) - HMToSeconds(mB[1], mB[2], mB[3])
}

function HMToSeconds(segno, h, m) {
	return ((segno == '-' ? -1 : 1)*(parseInt(h) * 3600 + parseInt(m) * 60));
}

var cmpData = function(a, b, aRow, bRow, column, dir, sorterParams) {
	let mA = a.match(/^(.*)\(.*$/);
	let mB = b.match(/^(.*)\(.*$/);
	return getDate(mA[1]) - getDate(mB[1]); 
}

function getDate(date){
   var parts = date.split("/");
   return new Date(parts[2], parts[1] - 1, parts[0]);
}

/***************DATA*****************/
$("#form").submit(function(e) {
	e.preventDefault();
	refreshData();
})
function refreshData() {
	$.ajax({
		url: "data.php",
		type: "POST",
		data: getFormAsJSON($("#form")),
		dataType: "json",
		error: function(XMLHttpRequest, textStatus, errorThrown) { alert(textStatus); }
	}).done(function(result) {
		 console.log(result);
		 downloadName = result.misc.title;
		 table.setData(result.datiTabella);
		 tableStatsOre.setData(result.statsOre);
		 tableStatsConteggi.setData(result.statsConteggi);
	});
}

function getFormAsJSON($form){
    var unindexed_array = $form.serializeArray();
    var indexed_array = {};

    $.map(unindexed_array, function(n, i){
        indexed_array[n['name']] = n['value'];
    });

    return indexed_array;
}

/***************TABLE INIT*****************/
var downloadName = "Report timbrature";
var widthOrari = "60";//"7%";
var alignment = "center";

var table = new Tabulator("#table", {
	layout:"fitColumns",
    printAsHtml:true,
    printVisibleRows:true,
    printHeader:downloadName,
    movableColumns:true,
    columnHeaderVertAlign:"bottom",
    columns:[
        {title:"Data", field:"data", tooltip:"Data", align:alignment, topCalc:"count", bottomCalc:"count", sorter:cmpData},
        {title:"Timbrature", field:"timbrature", tooltip:"Timbrature", align:alignment, bottomCalc:sumNotEmpty, topCalc:sumNotEmpty},
        {title:"Lavorate", field:"lavorate", tooltip:"Ore e minuti lavorati", width:widthOrari, align:alignment, bottomCalc:sumOrari, topCalc:sumOrari, sorter:cmpOrari},
        {title:"Diurne feriali", field:"diu_fer", tooltip:"Ore e minuti lavorati Diurni Feriali", width:widthOrari, align:alignment, bottomCalc:sumOrari, topCalc:sumOrari, sorter:cmpOrari},
        {title:"S. diurni feriali", field:"s_diu_fer", tooltip:"Straordinari ore e minuti lavorati Diurni Feriali", width:widthOrari, align:alignment, bottomCalc:sumOrari, topCalc:sumOrari, sorter:cmpOrari},
        {title:"S. notturni feriali", field:"s_not_fer", tooltip:"Straordinari ore e minuti lavorati Notturni Feriali", width:widthOrari, align:alignment, bottomCalc:sumOrari, topCalc:sumOrari, sorter:cmpOrari},
        {title:"S. diurni festivi", field:"s_diu_fes", tooltip:"Straordinari ore e minuti lavorati Diurni Festivi", width:widthOrari, align:alignment, bottomCalc:sumOrari, topCalc:sumOrari, sorter:cmpOrari},
        {title:"S. notturni festivi", field:"s_not_fes", tooltip:"Straordinari ore e minuti lavorati Notturni Festivi", width:widthOrari, align:alignment, bottomCalc:sumOrari, topCalc:sumOrari, sorter:cmpOrari},
        {title:"Saldo", field:"saldo", tooltip:"Saldo ore e minuti", width:widthOrari, align:alignment, bottomCalc:sumOrari, topCalc:sumOrari, sorter:cmpOrari},
        {title:"Da orario", field:"orario", tooltip:"Ore e minuti da orario", width:widthOrari, align:alignment, bottomCalc:sumOrari, topCalc:sumOrari, sorter:cmpOrari},
        {title:"Assenza giustificata", field:"assenza", tooltip:"Ore e minuti assenza giustificata", width:widthOrari, align:alignment, bottomCalc:sumOrari, topCalc:sumOrari, sorter:cmpOrari},
        {title:"Giustificazione assenza", field:"giust_ass", tooltip:"Giustificazione assenza", align:alignment, bottomCalc:sumNotEmpty, topCalc:sumNotEmpty}
    ],
});

var tableStatsOre = new Tabulator("#table-stats-ore", {
	layout:"fitColumns",
    printAsHtml:true,
    printVisibleRows:true,
    printHeader:downloadName,
    movableColumns:true,
    columns:[
        {title:"Tipo assenza", field:"tipo", tooltip:"Tipo assenza", align:alignment, sorter:"string"},
        {title:"Durata totale", field:"durata", tooltip:"Durata totale", align:alignment, sorter:cmpOrari}
    ],
});

var tableStatsConteggi = new Tabulator("#table-stats-conteggi", {
	layout:"fitColumns",
    printAsHtml:true,
    printVisibleRows:true,
    printHeader:downloadName,
    movableColumns:true,
    columns:[
        {title:"Tipo assenza", field:"tipo", tooltip:"Tipo assenza", align:alignment, sorter:"string"},
        {title:"Ricorrenze", field:"ricorrenze", tooltip:"Ricorrenze", align:alignment, sorter:"number"}
    ],
});

/***************BUTTONS HANDLERS*****************/
//trigger download of data.csv file
$("#download-csv").click(function(){
    //table.download("csv", downloadName+".csv", {delimiter:";"});
	Download.save(
			"Statistiche timbrature"+JSONtoCSV(table.getData("active"))+"\r\n\r\n"+
			"Statistiche assenze\r\n"+JSONtoCSV(tableStatsConteggi.getData("active"))+"\r\n\r\n"+
			JSONtoCSV(tableStatsOre.getData("active")), 
			downloadName+".csv");
});

window.onbeforeprint = function(){
	$("#menu").css("display", "none");
	//for (let column of table.getColumns())
		//column.updateDefinition({headerVertical:"flip"});
}

window.onafterprint = function(){
	$("#menu").css("display", "block");
	//for (let column of table.getColumns())
		//column.updateDefinition({headerVertical:false});
}

function JSONtoCSV(json) {
	if(json.length <= 0) return "";
		
	var fields = Object.keys(json[0]);
	var replacer = function(key, value) { return value === null ? '' : value } ;
	var csv = json.map(function(row){
	  return fields.map(function(fieldName){
	    return JSON.stringify(row[fieldName], replacer)
	  }).join(';')
	});
	csv.unshift(fields.join(';')); // add header column
	csv = csv.join('\r\n');
	return csv;
}

var Download = {
    click : function(node) {
        var ev = document.createEvent("MouseEvents");
        ev.initMouseEvent("click", true, false, self, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
        return node.dispatchEvent(ev);
    },
    encode : function(data) {
            return 'data:application/octet-stream;base64,' + btoa( data );
    },
    link : function(data, name){
        var a = document.createElement('a');
        a.download = name || self.location.pathname.slice(self.location.pathname.lastIndexOf('/')+1);
        a.href = data || self.location.href;
        return a;
    }
};
Download.save = function(data, name) {
    this.click(this.link(this.encode( data ), name));
};