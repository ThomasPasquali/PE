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

$('#ricerca-edificio').submit(function (e) {
    console.log('sssdsd');
    e.preventDefault();
    var data = $(this).serializeFormJSON();
    console.log(data);

    /* Object
        email: "value"
        name: "value"
        password: "value"
     */
});




function submitRicercaEdificio(){
  console.log(document.getElementById('ricerca-edificio'));
  $('#ricerca-edificio').submit();
  $('#ricerca-edificio').remove();
  return;

  let data = $('#ricerca-edificio').serialize();
  let request = $.ajax({
    url: "../runtime/handler.php",
    type: "POST",
    data: data,
    dataType: "text",
    error: function(jqXHR, textStatus) {
            alert('Errore: '+textStatus);
           },
   success: function(msg) {
              console.log(msg);
            }
  });
}
