(()=>{"use strict";var e,n={452:(e,n,t)=>{const r=window.wp.blocks,l=window.wp.element,o=(window.wp.i18n,window.wp.blockEditor),a=window.wp.apiFetch;var i=t.n(a);const s=window.wp.components,c=JSON.parse('{"u2":"sim/missingformfields"}');(0,r.registerBlockType)(c.u2,{icon:"form",edit:e=>{let{attributes:n,setAttributes:t}=e;const{type:r}=n,[a,c]=(0,l.useState)((0,l.createElement)(s.Spinner,null));return(0,l.useEffect)((async()=>{const e=await i()({path:`/sim/v1/forms/missing_form_fields?type=${r}`});c(e)}),[r]),(0,l.createElement)(l.Fragment,null,(0,l.createElement)(o.InspectorControls,null,(0,l.createElement)(s.Panel,null,(0,l.createElement)(s.PanelBody,null,(0,l.createElement)(s.RadioControl,{label:"Type of fields",selected:r,options:[{label:"Recommended",value:"recommended"},{label:"Mandatory",value:"mandatory"},{label:"Both",value:"all"}],onChange:e=>t({type:e})})))),(0,l.createElement)("div",(0,o.useBlockProps)(),wp.element.RawHTML({children:a})))},save:()=>null})}},t={};function r(e){var l=t[e];if(void 0!==l)return l.exports;var o=t[e]={exports:{}};return n[e](o,o.exports,r),o.exports}r.m=n,e=[],r.O=(n,t,l,o)=>{if(!t){var a=1/0;for(d=0;d<e.length;d++){t=e[d][0],l=e[d][1],o=e[d][2];for(var i=!0,s=0;s<t.length;s++)(!1&o||a>=o)&&Object.keys(r.O).every((e=>r.O[e](t[s])))?t.splice(s--,1):(i=!1,o<a&&(a=o));if(i){e.splice(d--,1);var c=l();void 0!==c&&(n=c)}}return n}o=o||0;for(var d=e.length;d>0&&e[d-1][2]>o;d--)e[d]=e[d-1];e[d]=[t,l,o]},r.n=e=>{var n=e&&e.__esModule?()=>e.default:()=>e;return r.d(n,{a:n}),n},r.d=(e,n)=>{for(var t in n)r.o(n,t)&&!r.o(e,t)&&Object.defineProperty(e,t,{enumerable:!0,get:n[t]})},r.o=(e,n)=>Object.prototype.hasOwnProperty.call(e,n),(()=>{var e={826:0,431:0};r.O.j=n=>0===e[n];var n=(n,t)=>{var l,o,a=t[0],i=t[1],s=t[2],c=0;if(a.some((n=>0!==e[n]))){for(l in i)r.o(i,l)&&(r.m[l]=i[l]);if(s)var d=s(r)}for(n&&n(t);c<a.length;c++)o=a[c],r.o(e,o)&&e[o]&&e[o][0](),e[o]=0;return r.O(d)},t=self.webpackChunksim_missing_form_fields=self.webpackChunksim_missing_form_fields||[];t.forEach(n.bind(null,0)),t.push=n.bind(null,t.push.bind(t))})();var l=r.O(void 0,[431],(()=>r(452)));l=r.O(l)})();