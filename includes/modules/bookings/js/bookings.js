function reset(modal, onlyEnd=false){
    if(!onlyEnd){
        modal.querySelector('.booking-startdate').value     = '';
        modal.querySelector('.calendar.day.startdate').classList.remove('startdate');
        modal.querySelectorAll('.available.selected').forEach(dt=>dt.classList.remove('selected'));

        modal.querySelector('.booking-date-label-wrapper.enddate').classList.add('disabled')
    }
    modal.querySelector('.booking-enddate').value       = '';
    
    modal.querySelector('.calendar.day.enddate').classList.remove('enddate');

    modal.querySelectorAll('.inbetween').forEach(dt=>dt.classList.remove('inbetween'));    
}

async function getMonth(target){
    let wrapper         = target.closest('.bookings-wrap');
    let monthContainer  = wrapper.querySelector(`.month-container[data-month="${target.dataset.month}"][data-year="${target.dataset.year}"]`);

    // hide the first month
    wrapper.querySelector('.calendar.table .month-container:first-of-type').classList.add('hidden');

    // month does not exist yet
    if(monthContainer == null){
        let formData    = new FormData();
        formData.append('month', target.dataset.month);
        formData.append('year', target.dataset.year);
        formData.append('subject', target.closest('.bookings-wrap').dataset.subject);

        let loader  = Main.showLoader(wrapper.querySelector('.calendar.table .month-container:not(.hidden)'), false);
            
        let response = await FormSubmit.fetchRestApi('bookings/get_next_month', formData);

        if(response){
            loader.outerHTML                                = response.month;
            wrapper.querySelector('.navigator').outerHTML   = response.navigator;
        }
    }else{
        monthContainer.classList.remove('hidden');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    console.log("Bookings.js loaded");

    document.querySelector(`[name="accomodation"]`).addEventListener(`change`, (ev)=>{
        document.querySelector(`[name="${ev.target.value}-modal"]`).classList.remove('hidden');
    });
});

document.addEventListener('click', (ev) => {
    let target  = ev.target;
    let modal   = target.closest('.modal');

    if(target.closest('dt') != null){
        target  = target.closest('dt');
    }

    if(target.matches('.action.reset')){
        reset(modal);
    }

    if(target.matches('.prevnext')){
        getMonth(target)
    }

    if(target.matches('.change-booking-date')){
        document.querySelector(`[name="${document.querySelector(`[name="accomodation"]`).value}-modal"]`).classList.remove('hidden');
    }

    if(target.matches('.action.confirm')){
        let startEl     = target.closest('form').querySelector('[name="booking[startdate]"]');
        let endEl       = target.closest('form').querySelector('[name="booking[enddate]"]');

        startEl.value   = modal.querySelector('.booking-startdate').dataset.isodate;
        endEl.value     = modal.querySelector('.booking-enddate').dataset.isodate;

        startEl.closest('.selected-booking-dates').classList.remove('hidden');

        modal.classList.add('hidden');

        reset(modal);
    }

    if(target.matches('.bookings-wrap .available:not(.selected)')){
        // we already have an selection
        if(modal.querySelector('.calendar.day.startdate') != null && modal.querySelector('.calendar.day.enddate') != null){
            let onlyEnd = false;
            if(target.matches('.inbetween')){
                onlyEnd = true;

            }
            
            reset(modal, onlyEnd);
        }

        if(modal.querySelector('.calendar.day.startdate') == null){
            modal.querySelector('.booking-startdate').value             = target.dataset.date;
            modal.querySelector('.booking-startdate').dataset.isodate   = target.dataset.isodate;

            target.classList.add('startdate');
            modal.querySelectorAll('.booking-date-label-wrapper.disabled').forEach(el=>el.classList.remove('disabled'));

            // do not allow any date before the startdate to be the enddate
            let dts     = target.closest('.calendar.table').querySelectorAll('dt.calendar.day:not(.head, .unavailable)');
            let skip    = false;
            for (i = 0; i < dts.length; ++i) {
                // all dates after the selected date are available
                if(dts[i] == target){
                    skip = true;
                // until we encounter another selected date
                }else if(skip && dts[i].matches('.selected')){
                    skip = false;
                }

                if(!skip){
                    dts[i].classList.add('selected');
                }
            }
        }else{
            // store enddate
            modal.querySelector('.booking-enddate').value               = target.dataset.date;
            modal.querySelector('.booking-enddate').dataset.isodate     = target.dataset.isodate;
            target.classList.add('enddate');

            // make other dates available again
            target.closest('.calendar.table').querySelectorAll('.available.selected').forEach(dt=>dt.classList.remove('selected'));

            // color the dates between start and end
            let dts     = target.closest('.calendar.table').querySelectorAll('dt.calendar.day:not(.head)');
            let skip    = true;
            for (i = 0; i < dts.length; ++i) {
                
                // until we encounter another selected date
                if(dts[i] == target){
                    break;
                }

                if(!skip){
                    dts[i].classList.add('inbetween');
                }

                // all dates after the selected date are available
                if(dts[i].matches('.startdate')){
                    skip = false;
                }
            }

            modal.querySelectorAll('.actions .action.disabled').forEach(el=>el.classList.remove('disabled'));
        }
    }
});