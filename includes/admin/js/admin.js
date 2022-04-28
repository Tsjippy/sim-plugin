console.log('admin.js loaded');

//Load after page load
document.addEventListener("DOMContentLoaded",function() {
	document.querySelector('[name="enable"]').addEventListener('change', function(event){
        if(event.target.checked){
            document.querySelector('.options').style.display    = 'block';
        }else{
            document.querySelector('.options').style.display    = 'none';
        }
    })

	//add niceselects
	document.querySelectorAll('select:not(.nonice,.swal2-select)').forEach(function(select){
		select._niceselect = NiceSelect.bind(select,{searchable: true});
	});
});

window.addEventListener("click", event => {
	var target = event.target;
    if(target.classList.contains('placeholderselect') || target.classList.contains('placeholders')){
        event.preventDefault();

        var value = '';
        if(target.classList.contains('placeholders')){
            value = target.textContent;
        }else if(target.value != ''){
            value = target.value;
            target.selectedIndex = '0';
        }
        
        if(value != ''){
            Swal.fire({
                icon: 'success',
                title: 'Copied '+value,
                showConfirmButton: false,
                timer: 1500
            })
            navigator.clipboard.writeText(value);
        }
    }
});