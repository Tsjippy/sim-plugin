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