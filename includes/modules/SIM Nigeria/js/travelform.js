document.addEventListener('change', async ev=>{
    var target  = ev.target;

    if(target.name == 'departuredate1'){
        var form    = target.closest('form');

        //check if there is already an entry on this date for this user
        var formData	= new FormData();
        formData.append('userid', form.querySelector('[name="user_id"]').value);
        formData.append('departuredate', target.value);
        
        var response    = await FormSubmit.fetchRestApi('sim_nigeria/verify_traveldate', formData);
        if(response){
            main.displayMessage(response, 'warning');
        }
    }
});