$('#tabellona').on('mouseover', 'td[title]', function() {
    var target = $(this);
    if (target.data('qtip')) { return false; }

    target.qtip({
        overwrite: false, // Make sure another tooltip can't overwrite this one without it being explicitly destroyed
        show: {
            ready: true // Needed to make it show on first mouseover event
        },
        content : {url :$(this).attr('title')},
        position : {
            corner : {
                tooltip : 'leftBottom',
                target : 'rightBottom'
            }
        },
        style : {
            border : {
            width : 5,
            radius : 10
        },
        padding : 10,
        textAlign : 'center',
        tip : true, 
        name : 'cream' 
    }});

    target.trigger('mouseover');
});
window.onbeforeprint = function(){ $("#menu").css("display", "none"); }
window.onafterprint = function(){ $("#menu").css("display", "block"); }

var c = 0;
function selezionaDipendente(name) {
    if($(`#dipendentiSelezionati input[value="${name}"]`).length <= 0)
        $('#dipendentiSelezionati').append($(`<input name="dipendente_${c++}" value="${name}" readonly>`).on('click', function() { $(this).remove(); }));
}