// https://www.codingnepalweb.com/build-image-editor-html-javascript/
// https://jamesooi.design/Croppr.js/#install

import Croppr from 'croppr';

console.log('Image edit js loaded');

export async function addCropper(file){    
    // constants
    const modal	    = document.getElementById('edit-image-modal'),
    fileInput       = modal.querySelector(".file-input"),
    filterOptions   = modal.querySelectorAll(".filter button"),
    filterName      = modal.querySelector(".filter-info .name"),
    filterValue     = modal.querySelector(".filter-info .value"),
    filterSlider    = modal.querySelector(".slider input"),
    rotateOptions   = modal.querySelectorAll(".rotate button"),
    resetFilterBtn  = modal.querySelector(".reset-filter"),
    chooseImgBtn    = modal.querySelector(".choose-img"),
    saveImgBtn      = modal.querySelector(".save-img"),
    zoomSlider      = modal.querySelector(".image-zoom");

    // variables
    let brightness  = "100", 
    saturation      = "100", 
    inversion       = "0", 
    grayscale       = "0",
    rotate          = 0, 
    flipHorizontal  = 1, 
    flipVertical    = 1,
    orgImage        = modal.querySelector('.preview-img img'),
    previewImg;

    modal.currentFileName   = file.name;

    // attach the cropper
    function onImageLoad() {
        modal._cropper  = new Croppr(
            '.preview-img img',
            {
                startSize: [50, 50, '%'],
            }
        );
        
        previewImg      = modal._cropper.imageClippedEl;
    };
    orgImage.onload = onImageLoad;

    // add the picture
    modal.querySelector('.preview-img img').src	= URL.createObjectURL(file);

    // functions
    const loadImage = () => {
        modal.classList.add("disable");

        let file = fileInput.files[0];
        if(!file) return;
        
        // remove the old cropper instance
        clearCropper();

        // refresh the orgImage var
        orgImage        = modal.querySelector('.preview-img img');

        orgImage.onload = onImageLoad;

        orgImage.src = URL.createObjectURL(file);

        previewImg.addEventListener("load", () => {
            resetFilterBtn.click();
            modal.classList.remove("disable");
        });
    }
    
    const applyFilter = () => {
        previewImg.style.transform  = `rotate(${rotate}deg) scale(${flipHorizontal}, ${flipVertical})`;
    
        previewImg.style.filter     = `brightness(${brightness}%) saturate(${saturation}%) invert(${inversion}%) grayscale(${grayscale}%)`;
    }
    
    filterOptions.forEach(option => {
        option.addEventListener("click", () => {
            modal.querySelector(".active").classList.remove("active");
            option.classList.add("active");
            filterName.innerText = option.innerText;
    
            if(option.id === "brightness") {
                filterSlider.max        = "200";
                filterSlider.value      = brightness;
                filterValue.innerText   = `${brightness}%`;
            } else if(option.id === "saturation") {
                filterSlider.max        = "200";
                filterSlider.value      = saturation;
                filterValue.innerText   = `${saturation}%`
            } else if(option.id === "inversion") {
                filterSlider.max        = "100";
                filterSlider.value      = inversion;
                filterValue.innerText   = `${inversion}%`;
            } else {
                filterSlider.max        = "100";
                filterSlider.value      = grayscale;
                filterValue.innerText   = `${grayscale}%`;
            }
        });
    });
    
    const updateFilter = () => {
        filterValue.innerText   = `${filterSlider.value}%`;
        const selectedFilter    = modal.querySelector(".filter .active");
    
        if(selectedFilter.id === "brightness") {
            brightness  = filterSlider.value;
        } else if(selectedFilter.id === "saturation") {
            saturation  = filterSlider.value;
        } else if(selectedFilter.id === "inversion") {
            inversion   = filterSlider.value;
        } else {
            grayscale   = filterSlider.value;
        }
        applyFilter();
    }
    
    rotateOptions.forEach(option => {
        option.addEventListener("click", () => {
            if(option.id === "left") {
                rotate -= 90;
            } else if(option.id === "right") {
                rotate += 90;
            } else if(option.id === "horizontal") {
                flipHorizontal = flipHorizontal === 1 ? -1 : 1;
            } else {
                flipVertical = flipVertical === 1 ? -1 : 1;
            }
            applyFilter();
        });
    });
    
    const resetFilter = () => {
        brightness      = "100"; 
        saturation      = "100"; 
        inversion       = "0"; 
        grayscale       = "0";
        rotate          = 0; 
        flipHorizontal  = 1; 
        flipVertical    = 1;

        filterOptions[0].click();
        applyFilter();
    }
    
    const saveImage = async (event) => {
    
        const points        = modal._cropper.getValue(),
        sx                  = points.x,
        sy                  = points.y,
        width               = points.width,
        height              = points.height,
        tempCanvas          = document.createElement("canvas"),
        ctx                 = tempCanvas.getContext("2d");

        let dx  = 0,
        dy      = 0;

        tempCanvas.width    = width;
        tempCanvas.height   = height;


        if(rotate !== 0) {
            // rotate with 90 degrees
            if(rotate == 90){
                // swap canvas size
                tempCanvas.width    = height;
                tempCanvas.height   = width;
                
                // set rotation center to the center of height.
                ctx.translate((height/2), (height/2));

                //rotate the canvas, 
                ctx.rotate((rotate) * Math.PI / 180); 

                // restore center               
                ctx.translate((-height/2), (-height/2));
            }else if(rotate == -90){
                // swap canvas size
                tempCanvas.width    = height;
                tempCanvas.height   = width;

                ctx.translate((height/2), (width/2));
                ctx.rotate((rotate) * Math.PI / 180); // x and y are now swapped
                ctx.translate(-(width/2), -(height/2));
            }else{
                // rotate 180 degrees
                ctx.translate(tempCanvas.width / 2, tempCanvas.height / 2);
                ctx.rotate(rotate * Math.PI / 180);
                
                ctx.translate(-tempCanvas.width / 2, -tempCanvas.height / 2);
            }
        } 

        if(flipHorizontal == -1){
            dx  = -width;
        }

        if(flipVertical == -1){
            dy  = -height;
        }
        ctx.scale(flipHorizontal, flipVertical);

        ctx.filter = `brightness(${brightness}%) saturate(${saturation}%) invert(${inversion}%) grayscale(${grayscale}%)`;

        ctx.drawImage(orgImage, sx, sy, width, height, dx, dy,  width, height);

        let ext         = file.name.split('.').pop();
        let filename    = file.name.replace(`.${ext}`, '.webp');
    
        /* const link = document.createElement("a");
        link.download = "image.jpg";
        link.href = tempCanvas.toDataURL();
        link.click(); */

        tempCanvas.toBlob(
            function(blob){
                document.querySelector("#edit-image-modal .save-img").file = new File([blob], filename);

                Main.hideModals();
            },
            'image/webp'
        );
    } 

    const clearCropper  = function(){
        // clear the cropper
        modal._cropper.destroy();

        modal.querySelector('.preview-img').innerHTML   = `<img src="" alt="preview-img" class='hidden'>`;

        resetFilter();
    }

    const zoomImage = () => {
        modal._cropper.reset();

        let zoomPercentage  = zoomSlider.value/100
        let zoomWidth   = modal._cropper.imageClippedEl.width * zoomPercentage;
        let zoomHeight  = modal._cropper.imageClippedEl.height * zoomPercentage;

        modal._cropper.resizeTo(zoomWidth, zoomHeight);

        zoomSlider.parentNode.querySelector('output').value = zoomSlider.value;
    }

    //events
    filterSlider.addEventListener("input", updateFilter);
    resetFilterBtn.addEventListener("click", resetFilter);
    saveImgBtn.addEventListener("click", saveImage);
    fileInput.addEventListener("change", loadImage);
    chooseImgBtn.addEventListener("click", () => fileInput.click());
    zoomSlider.addEventListener("input", zoomImage);

    Main.showModal(modal);

    return new Promise(async function(resolve, reject) {
        let i   = 0;
        while(!modal.matches('.hidden')){
            i++;

            if(i > 600 ){
                reject('Took too long');
            }

            // sleep for 1 second
            await new Promise(r => setTimeout(r, 1000));
        }

        clearCropper();

        // return the edited image
        console.log(modal);
        if(modal.querySelector(".save-img").file != undefined){
            resolve(modal.querySelector(".save-img").file);
        }else{
            // return the original image
            resolve(file);
        }
    });
}