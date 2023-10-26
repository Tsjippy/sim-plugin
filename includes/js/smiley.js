import { createPopup } from '@picmo/popup-picker';

console.log('Smiley.js loaded');

document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.trigger').forEach(trigger => {

        const picker = createPopup({}, {
            referenceElement: trigger,
            triggerElement: trigger,
            position: 'right-end'
        });

        trigger.addEventListener('click', () => {
            picker.toggle();
        });

        picker.addEventListener('emoji:select', (selection) => {
            let target  = document.querySelector(trigger.dataset.target);

            if(target == null){
                return;
            }

            if(target.type == 'textarea'){
                addToTextArea(selection.emoji, target);
            }else{
                target.value    = selection.emoji;
            }

            if(trigger.dataset.replace != null){
                trigger.outerHTML   = selection.emoji;
            }

            const ev = new Event('emoji_selected', {
                bubbles: true,
                cancelable: true
            });
	        target.dispatchEvent(ev);
        });
    });
});


const addToTextArea = function (text_to_add, textarea) {
    let start_position = textarea.selectionStart;
    let end_position = textarea.selectionEnd;

    textarea.value = `${textarea.value.substring(
        0,
        start_position
    )}${text_to_add}${textarea.value.substring(
        end_position,
        textarea.value.length
    )}`;
};