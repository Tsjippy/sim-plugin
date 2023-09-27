import { createPopup } from '@picmo/popup-picker';

console.log('Smiley.js loaded');

document.addEventListener("DOMContentLoaded", function() {
    const trigger   = document.querySelector('#trigger');

    const picker = createPopup({}, {
        referenceElement: trigger,
        triggerElement: trigger,
        position: 'right-end'
    });

    trigger.addEventListener('click', () => {
        picker.toggle();
    });

    picker.addEventListener('emoji:select', (selection) => {
        //console.log(selection);

        addToTextArea(selection.emoji);
    });
});


const addToTextArea = function (text_to_add) {
    const textarea  = document.querySelector('[name="message"]');
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