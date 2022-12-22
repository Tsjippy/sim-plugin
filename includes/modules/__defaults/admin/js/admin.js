console.log('admin.js loaded');

//Load after page load
document.addEventListener("DOMContentLoaded",function() {
	document.querySelector('[name="enable"]').addEventListener('change', function(event){
        if(event.target.checked){
            document.querySelectorAll('.options, .tablink-wrapper').forEach(el=>el.style.display    = 'block');
        }else{
            document.querySelectorAll('.options, .tablink-wrapper').forEach(el=>el.style.display    = 'none');
        }
    })

	//add niceselects
	document.querySelectorAll('select:not(.nonice,.swal2-select)').forEach(function(select){
        if(select._niceselect  == undefined){
		    select._niceselect = NiceSelect.bind(select,{searchable: true});
        }
	});
});

window.addEventListener("click", event => {
	let target = event.target;
    if(target.classList.contains('placeholderselect') || target.classList.contains('placeholders')){
        event.preventDefault();

        let value = '';
        if(target.classList.contains('placeholders')){
            value = target.textContent;
        }else if(target.value != ''){
            value = target.value;
            target.selectedIndex = '0';
        }
        
        if(value != ''){
            let options = {
                icon: 'success',
                title: 'Copied '+value,
                showConfirmButton: false,
                timer: 1500
            };

            if(document.fullscreenElement != null){
                options['target']	= document.fullscreenElement;
            }

            Swal.fire(options);
            navigator.clipboard.writeText(value);
        }
    }

    if(target.matches('.tablink:not(.active)')){
        const url 		= new URL(window.location);
        url.searchParams.set('tab', target.dataset.target);
	    window.history.pushState({}, '', url);

        let curActive   = target.closest('.tablink-wrapper').querySelector('.active');
        curActive.classList.remove('active');
        document.getElementById(curActive.dataset.target).classList.add('hidden');

        target.classList.add('active');
        console.log(target);
        document.getElementById(target.dataset.target).classList.remove('hidden');

    }
});