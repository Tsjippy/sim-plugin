import { addCurrentUserAsHost, removeHost, editTimeSlot, addHostHtml } from './shared.js';

console.log("Mobile-schedule.js loaded");

// Add a new host/ updates an existing entry when the host form is submitted
async function addHost(target){
	var response 	= await FormSubmit.submitForm(target, 'events/add_host');

	if(response){
		addHostHtml(response);
		Main.displayMessage(response.message);
	}

    Main.hideModals();
}

document.addEventListener('click', async function(event){
    let target = event.target;

    if(target.closest('.add-me-as-host') != null){
        target  = target.closest('.add-me-as-host');
        target.closest('.session-wrapper-mobile ').classList.add('active');
		addCurrentUserAsHost(target.closest('.add-me-as-host'), target.dataset.date);
	}else if(target.closest('.remove-host-mobile') != null){
		event.stopPropagation();
        target      = target.closest('.remove-host-mobile');
        let wrapper	= target.closest('.session-wrapper-mobile');

		let result = await removeHost(target, target.dataset.date);

        if(result){
            wrapper.remove();
        }
	}else if (target.name == 'add_host_mobile' || target.name == 'add_timeslot' ){
		event.stopPropagation();
		addHost(target)
	}else if(target.closest('.edit-session-mobile') != null){
        target  = target.closest('.edit-session-mobile');

        target.closest('.day-wrapper-mobile').classList.add('active');

		let result = await editTimeSlot(target, target.dataset.isodate, target.dataset.date);
        if(result){
            target.remove();
        }
	}else if(target.name == 'add_host'){
		event.stopPropagation();
		
		addHost(target);
	}
});

document.addEventListener('change', function(event){

	let target = event.target;

	//Direct table actions
	if(target.name == 'starttime' && target.type == 'radio'){
		if(target.value == '12:00'){
			target.closest('form').querySelector('.lunch.select-wrapper').classList.remove('hidden');
			target.closest('form').querySelector('.diner.select-wrapper').classList.add('hidden');
		}else{
			target.closest('form').querySelector('.diner.select-wrapper').classList.remove('hidden');
			target.closest('form').querySelector('.lunch.select-wrapper').classList.add('hidden');
		}
	}
});