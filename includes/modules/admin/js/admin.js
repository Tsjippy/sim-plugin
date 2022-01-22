console.log('admin.js loaded');

document.querySelector('[name="enable"]').addEventListener('change', function(event){
    if(event.target.checked){
        document.querySelector('.options').style.display    = 'block';
    }else{
        document.querySelector('.options').style.display    = 'none';
    }
})