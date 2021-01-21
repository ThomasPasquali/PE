function bloccaTesto(el) {
    let p = el.parent();
    let txt = p.find('textarea').val();
    p.empty();
    p.text(txt);
}

$(document).ready(function () {
    $('#logo').width($('#logo').height()*(230/219));
});