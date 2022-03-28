document.addEventListener('change', ev=>{
    var target  = ev.target;

    if(target.name == 'departuredate1'){
        var form    = target.closest('form');
        //check if there is already an entry on this date for this user
        var formData	= new FormData();
        formData.append('userid', form.querySelector('[name="user_id"]').value);
        formData.append('departuredate', target.value);
        formData.append('_wpnonce', sim.restnonce);
        
        fetch(
            sim.base_url+'/wp-json/sim/v1/verify_traveldate', 
            {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            }
        ).then(response => response.json())
        .then(response => {
            console.log(response);
            if(response){
                display_message(response, 'warning');
            }
        })
        .catch(err => console.error(err));
    }
})
    