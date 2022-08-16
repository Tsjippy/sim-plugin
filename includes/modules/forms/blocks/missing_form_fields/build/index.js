(()=>{"use strict";var e,r={452:(e,r,t)=>{const n=window.wp.blocks,l=window.wp.element,o=(window.wp.i18n,window.wp.blockEditor),a=window.wp.apiFetch;var i=t.n(a);const s=window.wp.components,c=JSON.parse('{"u2":"sim/missingformfields"}');(0,n.registerBlockType)(c.u2,{icon:"form",edit:e=>{let{attributes:r,setAttributes:t}=e;const{type:n}=r,[a,c]=(0,l.useState)((0,l.createElement)(s.Spinner,null));return(0,l.useEffect)((async()=>{const e=await i()({path:`${sim.restApiPrefix}/forms/missing_form_fields?type=${n}`});c(e)}),[n]),(0,l.createElement)(l.Fragment,null,(0,l.createElement)(o.InspectorControls,null,(0,l.createElement)(s.Panel,null,(0,l.createElement)(s.PanelBody,null,(0,l.createElement)(s.RadioControl,{label:"Type of fields",selected:n,options:[{label:"Recommended",value:"recommended"},{label:"Mandatory",value:"mandatory"},{label:"Both",value:"all"}],onChange:e=>t({type:e})})))),(0,l.createElement)("div",(0,o.useBlockProps)(),wp.element.RawHTML({children:a})))},save:()=>null})}},t={};function n(e){var l=t[e];if(void 0!==l)return l.exports;var o=t[e]={exports:{}};return r[e](o,o.exports,n),o.exports}n.m=r,e=[],n.O=(r,t,l,o)=>{if(!t){var a=1/0;for(d=0;d<e.length;d++){t=e[d][0],l=e[d][1],o=e[d][2];for(var i=!0,s=0;s<t.length;s++)(!1&o||a>=o)&&Object.keys(n.O).every((e=>n.O[e](t[s])))?t.splice(s--,1):(i=!1,o<a&&(a=o));if(i){e.splice(d--,1);var c=l();void 0!==c&&(r=c)}}return r}o=o||0;for(var d=e.length;d>0&&e[d-1][2]>o;d--)e[d]=e[d-1];e[d]=[t,l,o]},n.n=e=>{var r=e&&e.__esModule?()=>e.default:()=>e;return n.d(r,{a:r}),r},n.d=(e,r)=>{for(var t in r)n.o(r,t)&&!n.o(e,t)&&Object.defineProperty(e,t,{enumerable:!0,get:r[t]})},n.o=(e,r)=>Object.prototype.hasOwnProperty.call(e,r),(()=>{var e={826:0,431:0};n.O.j=r=>0===e[r];var r=(r,t)=>{var l,o,a=t[0],i=t[1],s=t[2],c=0;if(a.some((r=>0!==e[r]))){for(l in i)n.o(i,l)&&(n.m[l]=i[l]);if(s)var d=s(n)}for(r&&r(t);c<a.length;c++)o=a[c],n.o(e,o)&&e[o]&&e[o][0](),e[o]=0;return n.O(d)},t=self.webpackChunksim_missing_form_fields=self.webpackChunksim_missing_form_fields||[];t.forEach(r.bind(null,0)),t.push=r.bind(null,t.push.bind(t))})();var l=n.O(void 0,[431],(()=>n(452)));l=n.O(l)})();