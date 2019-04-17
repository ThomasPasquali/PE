function changeContent(divID) {
	var contents = document.getElementsByClassName('content');
	for(var i = 0; i < contents.length; i++)
		hide(contents.item(i));
	show(document.getElementById(divID));
}

function hide(element) {
	element.style.display = 'none';
}

function show(element) {
	element.style.display = 'block';
}

function getParameter(name){
   if(name=(new RegExp('[?&]'+encodeURIComponent(name)+'=([^&]*)')).exec(location.search))
      return decodeURIComponent(name[1]);
}

function displayMessage(msg, element, type='') {
    let div = document.createElement('div');
    div.classList.add('alert');
    if(type !== '') div.classList.add(type);
    
    let span = document.createElement('span');
    span.classList.add('closebtn');
    span.innerHTML = '&times';
    span.onclick = function(){
	    var div = this.parentElement;
	    div.style.opacity = "0";
	    setTimeout(function(){ div.style.display = "none"; }, 600);
	  }
    
    div.innerHTML = msg;
    div.appendChild(span);
    
	element.insertBefore(div, element.children[1]);
};