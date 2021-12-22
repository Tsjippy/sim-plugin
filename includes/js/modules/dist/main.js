/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ 914:
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "clickListener": () => (/* binding */ clickListener)
/* harmony export */ });
var clickListener	= [];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {

// NAMESPACE OBJECT: ./nice-select2.js
var nice_select2_namespaceObject = {};
__webpack_require__.r(nice_select2_namespaceObject);

// EXTERNAL MODULE: ./globals.js
var globals = __webpack_require__(914);
;// CONCATENATED MODULE: ./tabs.js
var clickListener = __webpack_require__(914);
function change_hash(target){
	if(target.dataset.hash != null){
		var new_hash	= target.dataset.hash;
	}else{
		var new_hash	= target.textContent;
	}
	//Change the hash
	if(target.closest('.tabcontent') == null || target.parentNode.classList.contains('modal-content') == true){
		//Add anchor if it is a main tab
		location.hash = new_hash;
	}else if(location.hash.replace('#','').split('#').length>1){
		var hash = location.hash.replace('#','').split('#');
		//Add anchor if it is a secondary tab
		location.hash = '#'+hash[0]+'#'+new_hash;
	}else{
		location.hash = location.hash+'#'+new_hash;
	}

	if(target.dataset.hash != null){
		process_hash();
	}
}

function process_hash(event=null){
	var content = decodeURIComponent(window.location.hash.replace('#',''));
	
	//split the hash on #
	content = content.split('#');
	
	//loop over all the tab buttons
	document.querySelectorAll('.tablink').forEach(function(tab_button){
		//Add the dataset if it does not exist yet.
		if(tab_button.dataset.text == undefined){
			tab_button.dataset.text = tab_button.textContent;
		}
	})
	
	tab_button = '';
	//find the tab and display it
	document.querySelectorAll('[data-text="'+content[0]+'"]').forEach(tabbutton=>{
		//only process non-modal tabs
		if(tabbutton.closest('.modal') == null){
			tab_button = tabbutton;
			display_tab(tabbutton);
		}
	})
	
	//if there is a second tab
	if(content.length>1){
		//get the content of the main tab
		var maincontent = document.getElementById(tab_button.dataset.target);
		
		//look in the main content for the second tabbutton
		var secondtab = maincontent.querySelector('[data-text="'+content[1]+'"]');
		display_tab(secondtab);
	}
}

function display_tab(tab_button){
	//reset hash if something goes wrong
	if(tab_button == null){
		console.log("resetting hash");
		window.location = window.location.href.split('#')[0];
	}
	
	//Show the  tab
	var tab = tab_button.closest('div').querySelector('#'+tab_button.dataset.target);
	
	if(tab != null){
		tab.classList.remove('hidden');
		//Mark the other tabbuttons as inactive
		tab_button.parentNode.childNodes.forEach(child=>{
			if(child.classList != null && child.classList.contains('active') && child != tab_button){
				//Make inactive
				child.classList.remove("active");
					
				//Hide the tab
				child.closest('div').querySelector('#'+child.dataset.target).classList.add('hidden');
			}
		});
		
		//Mark the tabbutton as active
		tab_button.classList.add("active");
	}
}

function click_listener(target){
    //Process the click on tab button
	if(target.matches(".tablink")){
		//show the tab
		display_tab(target);
		//change the hash in browser
		change_hash(target);
		//send statistics
		send_statistics();
	}	
}

clickListener.push( click_listener);
;// CONCATENATED MODULE: ./statistics.js
function statistics_send_statistics(){
	var request = new XMLHttpRequest();

	request.open('POST', simnigeria.ajax_url, true);
	
	var formData = new FormData();
	formData.append('action','add_page_view');
	formData.append('url',window.location.href);
	request.send(formData);
}
;// CONCATENATED MODULE: ./nice-select2.js
//import "../scss/nice-select2.scss";

// utility functions
function triggerClick(el) {
  var event = document.createEvent("MouseEvents");
  event.initEvent("click", true, false);
  el.dispatchEvent(event);
}

function triggerChange(el) {
  var event = document.createEvent("HTMLEvents");
  event.initEvent("change", true, false);
  el.dispatchEvent(event);
}

function triggerFocusIn(el) {
  var event = document.createEvent("FocusEvent");
  event.initEvent("focusin", true, false);
  el.dispatchEvent(event);
}

function triggerFocusOut(el) {
  var event = document.createEvent("FocusEvent");
  event.initEvent("focusout", true, false);
  el.dispatchEvent(event);
}

function attr(el, key) {
  return el.getAttribute(key);
}

function data(el, key) {
  return el.getAttribute("data-" + key);
}

function hasClass(el, className) {
  if (el) return el.classList.contains(className);
  else return false;
}

function addClass(el, className) {
  if (el) return el.classList.add(className);
}

function removeClass(el, className) {
  if (el) return el.classList.remove(className);
}

var defaultOptions = {
  data: null,
  searchable: false
};
function NiceSelect(element, options) {
  this.el = element;
  this.config = Object.assign({}, defaultOptions, options || {});

  this.data = this.config.data;
  this.selectedOptions = [];

  this.placeholder =
    attr(this.el, "placeholder") ||
    this.config.placeholder ||
    "Select an option";

  this.dropdown = null;
  this.multiple = attr(this.el, "multiple");
  this.disabled = attr(this.el, "disabled");

  this.create();
}

NiceSelect.prototype.create = function() {
  this.el.style.display = "none";

  if (this.data) {
    this.processData(this.data);
  } else {
    this.extractData();
  }

  this.renderDropdown();
  this.bindEvent();
};

NiceSelect.prototype.processData = function(data) {
  var options = [];
  data.forEach(function(item) {
    options.push({
      data: item,
      attributes: {
        selected: false,
        disabled: false
      }
    });
  });
  this.options = options;
};

NiceSelect.prototype.extractData = function() {
  var options = this.el.querySelectorAll("option");
  var data = [];
  var allOptions = [];
  var selectedOptions = [];

  options.forEach(item => {
    var itemData = {
      text: item.innerText,
      value: item.value
    };

    var attributes = {
      selected: item.getAttribute("selected") != null,
      disabled: item.getAttribute("disabled") != null
    };

    data.push(itemData);
    allOptions.push({ data: itemData, attributes: attributes });
  });

  this.data = data;
  this.options = allOptions;
  this.options.forEach(function(item) {
    if (item.attributes.selected) selectedOptions.push(item);
  });

  this.selectedOptions = selectedOptions;
};

NiceSelect.prototype.renderDropdown = function() {
  var classes = [
    "nice-select",
    attr(this.el, "class") || "",
    this.disabled ? "disabled" : "",
    this.multiple ? "has-multiple" : ""
  ];

  let searchHtml = `<div class="nice-select-search-box">
<input type="text" class="nice-select-search" placeholder="Search..."/>
</div>`;

  var html = `<div class="${classes.join(" ")}" tabindex="${
    this.disabled ? null : 0
  }">
  <span class="${this.multiple ? "multiple-options" : "current"}"></span>
  <div class="nice-select-dropdown">
  ${this.config.searchable ? searchHtml : ""}
  <ul class="list"></ul>
  </div></div>
`;

  this.el.insertAdjacentHTML("afterend", html);

  this.dropdown = this.el.nextElementSibling;
  this._renderSelectedItems();
  this._renderItems();
};

NiceSelect.prototype._renderSelectedItems = function() {
  if (this.multiple) {
    var selectedHtml = "";
    
	console.log(this)
	
    this.selectedOptions.forEach(function(item) {
      selectedHtml += `<span class="current">${item.data.text}</span>`;
    });
    selectedHtml = selectedHtml == "" ? this.placeholder : selectedHtml;

    this.dropdown.querySelector(".multiple-options").innerHTML = selectedHtml;
  } else {
    var html =
      this.selectedOptions.length > 0
        ? this.selectedOptions[0].data.text
        : this.placeholder;

    this.dropdown.querySelector(".current").innerHTML = html;
  }
};

NiceSelect.prototype._renderItems = function() {
  var ul = this.dropdown.querySelector("ul");
  this.options.forEach(item => {
    ul.appendChild(this._renderItem(item));
  });
};

NiceSelect.prototype._renderItem = function(option) {
  var el = document.createElement("li");
  el.setAttribute("data-value", option.data.value);

  var classList = [
    "option",
    option.attributes.selected ? "selected" : null,
    option.attributes.disabled ? "disabled" : null
  ];

  el.classList.add(...classList);
  el.innerHTML = option.data.text;
  el.addEventListener("click", this._onItemClicked.bind(this, option));
  option.element = el;
  return el;
};

NiceSelect.prototype.update = function() {
  this.extractData();
  if (this.dropdown) {
    var open = hasClass(this.dropdown, "open");
    this.dropdown.parentNode.removeChild(this.dropdown);
    this.create();

    if (open) {
      triggerClick(this.dropdown);
    }
  }
};

NiceSelect.prototype.disable = function() {
  if (!this.disabled) {
    this.disabled = true;
    addClass(this.dropdown, "disabled");
  }
};

NiceSelect.prototype.enable = function() {
  if (this.disabled) {
    this.disabled = false;
    removeClass(this.dropdown, "disabled");
  }
};

NiceSelect.prototype.clear = function() {
  this.selectedOptions = [];
  this._renderSelectedItems();
  this.updateSelectValue();
  triggerChange(this.el);
};

NiceSelect.prototype.destroy = function() {
  if (this.dropdown) {
    this.dropdown.parentNode.removeChild(this.dropdown);
    this.el.style.display = "";
  }
};

NiceSelect.prototype.bindEvent = function() {
  var $this = this;
  this.dropdown.addEventListener("click", this._onClicked.bind(this));
  this.dropdown.addEventListener("keydown", this._onKeyPressed.bind(this));
  this.dropdown.addEventListener("focusin", triggerFocusIn.bind(this, this.el));
  this.dropdown.addEventListener("focusout", triggerFocusOut.bind(this, this.el));
  window.addEventListener("click", this._onClickedOutside.bind(this));

  if (this.config.searchable) {
    this._bindSearchEvent();
  }
};

NiceSelect.prototype._bindSearchEvent = function() {
  var searchBox = this.dropdown.querySelector(".nice-select-search");
  if (searchBox)
    searchBox.addEventListener("click", function(e) {
      e.stopPropagation();
      return false;
    });

  searchBox.addEventListener("input", this._onSearchChanged.bind(this));
};

NiceSelect.prototype._onClicked = function(e) {
  this.dropdown.classList.toggle("open");

  if (this.dropdown.classList.contains("open")) {
    var search = this.dropdown.querySelector(".nice-select-search");
    if (search) {
      search.value = "";
      search.focus();
    }

    var t = this.dropdown.querySelector(".focus");
    removeClass(t, "focus");
    t = this.dropdown.querySelector(".selected");
    addClass(t, "focus");
    this.dropdown.querySelectorAll("ul li").forEach(function(item) {
      item.style.display = "";
    });
  } else {
    this.dropdown.focus();
  }
};

NiceSelect.prototype._onItemClicked = function(option, e) {
  var optionEl = e.target;

  if (!hasClass(optionEl, "disabled")) {
    if (this.multiple) {
      if (hasClass(optionEl, "selected")) {
		removeClass(optionEl, "selected");
		this.selectedOptions.splice(this.selectedOptions.indexOf(option),1)
	  }else{
        addClass(optionEl, "selected");
        this.selectedOptions.push(option);
      }
    } else {
      this.selectedOptions.forEach(function(item) {
        removeClass(item.element, "selected");
      });

      addClass(optionEl, "selected");
      this.selectedOptions = [option];
    }

    this._renderSelectedItems();
    this.updateSelectValue();
  }
};

NiceSelect.prototype.updateSelectValue = function() {
  if (this.multiple) {
	  console.log(this)
    var select = this.el;
    this.selectedOptions.forEach(function(item) {
      var el = select.querySelector('option[value="' + item.data.value + '"]');
      if (el) el.setAttribute("selected", true);
    });
  } else if (this.selectedOptions.length > 0) {
    this.el.value = this.selectedOptions[0].data.value;
  }
  triggerChange(this.el);
};

NiceSelect.prototype._onClickedOutside = function(e) {
  if (!this.dropdown.contains(e.target)) {
    this.dropdown.classList.remove("open");
  }
};

NiceSelect.prototype._onKeyPressed = function(e) {
  // Keyboard events

  var focusedOption = this.dropdown.querySelector(".focus");

  var open = this.dropdown.classList.contains("open");

  // Space or Enter
  if (e.keyCode == 32 || e.keyCode == 13) {
    if (open) {
      triggerClick(focusedOption);
    } else {
      triggerClick(this.dropdown);
    }
  } else if (e.keyCode == 40) {
    // Down
    if (!open) {
      triggerClick(this.dropdown);
    } else {
      var next = this._findNext(focusedOption);
      if (next) {
        var t = this.dropdown.querySelector(".focus");
        removeClass(t, "focus");
        addClass(next, "focus");
      }
    }
    e.preventDefault();
  } else if (e.keyCode == 38) {
    // Up
    if (!open) {
      triggerClick(this.dropdown);
    } else {
      var prev = this._findPrev(focusedOption);
      if (prev) {
        var t = this.dropdown.querySelector(".focus");
        removeClass(t, "focus");
        addClass(prev, "focus");
      }
    }
    e.preventDefault();
  } else if (e.keyCode == 27 && open) {
    // Esc
    triggerClick(this.dropdown);
  }
  return false;
};

NiceSelect.prototype._findNext = function(el) {
  if (el) {
    el = el.nextElementSibling;
  } else {
    el = this.dropdown.querySelector(".list .option");
  }

  while (el) {
    if (!hasClass(el, "disabled") && el.style.display != "none") {
      return el;
    }
    el = el.nextElementSibling;
  }

  return null;
};

NiceSelect.prototype._findPrev = function(el) {
  if (el) {
    el = el.previousElementSibling;
  } else {
    el = this.dropdown.querySelector(".list .option:last-child");
  }

  while (el) {
    if (!hasClass(el, "disabled") && el.style.display != "none") {
      return el;
    }
    el = el.previousElementSibling;
  }

  return null;
};

NiceSelect.prototype._onSearchChanged = function(e) {
  var open = this.dropdown.classList.contains("open");
  var text = e.target.value;
  text = text.toLowerCase();

  if (text == "") {
    this.options.forEach(function(item) {
      item.element.style.display = "";
    });
  } else if (open) {
    var matchReg = new RegExp(text);
    this.options.forEach(function(item) {
      var optionText = item.data.text.toLowerCase();
      var matched = matchReg.test(optionText);
      item.element.style.display = matched ? "" : "none";
    });
  }

  this.dropdown.querySelectorAll(".focus").forEach(function(item) {
    removeClass(item, "focus");
  });

  var firstEl = this._findNext(null);
  addClass(firstEl, "focus");
};

function bind(el, options) {
  return new NiceSelect(el, options);
}

;// CONCATENATED MODULE: ./modals.js
function modals_click_listener(target){
    //close modal on close click
    if(target.matches(".close")){
        target.closest('.modal').classList.add('hidden');
    }
}

var modals_clickListener = __webpack_require__(914);
modals_clickListener.push( modals_click_listener);

function hide_modals(){

}
;// CONCATENATED MODULE: ./main.js
console.log("Main.js loaded");







hide_modals();
//Load after page load
document.addEventListener("DOMContentLoaded",function() {
	//check for tab actions
	if(window.location.hash != ""){
		process_hash();
	}else{
		//send statistics
		statistics_send_statistics();
	}

	//add niceselects
	document.querySelectorAll('select:not(.nonice,.swal2-select)').forEach(function(select){
		select._niceselect = nice_select2_namespaceObject["default"].bind(select,{searchable: true});
	});
});

//Hide or show the clicked tab
window.addEventListener("click", function(event) {
	var target = event.target;

	for(var x=0; x++; x<globals.clickListener.length){
		globals.clickListener[x](target);
	}
});
})();

/******/ })()
;