document.addEventListener('click',function(event) {
	var target = event.target;
    if(target.classList.contains('icon')){
        var parent  = target.closest('.icon_select_wrapper');
        if(target.tagName == 'DIV'){
            var url = target.querySelector('img').src;
        }else{
            var url     = target.src;
        }

        parent.querySelector('.icon_url').value         = url;

        parent.querySelector('.icon_preview').innerHTML = "<img src='"+url+"' class='icon'>";

        parent.querySelector('.dropbtn').textContent    = "Change Icon";
    }
});