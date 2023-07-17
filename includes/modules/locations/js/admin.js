document.addEventListener('click',function(event) {
	let target = event.target;
    if(target.classList.contains('icon')){
        let parent  = target.closest('.icon_select_wrapper');
        if(target.tagName == 'DIV'){
            target = target.querySelector('img');
        }

        parent.querySelector('.icon_id').value          = target.dataset.id;

        parent.querySelector('.icon_preview').innerHTML = `<img src="${target.src}" class='icon'>`;

        parent.querySelector('.dropbtn').textContent    = "Change Icon";
    }
});