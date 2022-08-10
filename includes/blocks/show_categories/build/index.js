(()=>{"use strict";var e,t={848:()=>{const e=window.wp.blocks,t=window.wp.element,n=window.wp.i18n,r=window.wp.blockEditor,l=(window.wp.apiFetch,window.wp.components),o=window.wp.data,a=window.wp.coreData,s=(window.wp.htmlEntities,e=>{let{attributes:s,setAttributes:c}=e;const{count:i}=s,{categories:u,hasResolved:m}=(0,o.useSelect)((e=>{const t=["taxonomy","category"];return{categories:e(a.store).getEntityRecords(...t),hasResolved:e(a.store).hasFinishedResolution("getEntityRecords",t)}}),[]);return(0,t.createElement)(t.Fragment,null,(0,t.createElement)(r.InspectorControls,null,(0,t.createElement)(l.Panel,null,(0,t.createElement)(l.PanelBody,null,(0,t.createElement)(l.ToggleControl,{label:(0,n.__)("Show categories count","sim"),checked:!!s.count,onChange:()=>c({count:!s.count})})))),(0,t.createElement)("div",(0,r.useBlockProps)(),(0,t.createElement)("aside",{class:"event"},(0,t.createElement)("h4",{class:"title"},"Categories"),(0,t.createElement)("div",{class:"upcomingevents_wrapper"},m?null!=u&&u.length?(0,t.createElement)("ul",null,null==u?void 0:u.map((e=>{let n="";return i&&(n=(0,t.createElement)(t.Fragment,null,"  (",(0,t.createElement)("span",{class:"cat-count"},e.count),")")),(0,t.createElement)("li",null,(0,t.createElement)("a",{href:e.link},e.name,n))}))):(0,t.createElement)("div",null," ",(0,n.__)("No categories found","sim")):(0,t.createElement)(t.Fragment,null,(0,t.createElement)(l.Spinner,null),(0,t.createElement)("br",null))))))}),c=JSON.parse('{"u2":"sim/show-cats"}');(0,e.registerBlockType)(c.u2,{icon:"category",edit:s,save:()=>null})}},n={};function r(e){var l=n[e];if(void 0!==l)return l.exports;var o=n[e]={exports:{}};return t[e](o,o.exports,r),o.exports}r.m=t,e=[],r.O=(t,n,l,o)=>{if(!n){var a=1/0;for(u=0;u<e.length;u++){n=e[u][0],l=e[u][1],o=e[u][2];for(var s=!0,c=0;c<n.length;c++)(!1&o||a>=o)&&Object.keys(r.O).every((e=>r.O[e](n[c])))?n.splice(c--,1):(s=!1,o<a&&(a=o));if(s){e.splice(u--,1);var i=l();void 0!==i&&(t=i)}}return t}o=o||0;for(var u=e.length;u>0&&e[u-1][2]>o;u--)e[u]=e[u-1];e[u]=[n,l,o]},r.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e={826:0,431:0};r.O.j=t=>0===e[t];var t=(t,n)=>{var l,o,a=n[0],s=n[1],c=n[2],i=0;if(a.some((t=>0!==e[t]))){for(l in s)r.o(s,l)&&(r.m[l]=s[l]);if(c)var u=c(r)}for(t&&t(n);i<a.length;i++)o=a[i],r.o(e,o)&&e[o]&&e[o][0](),e[o]=0;return r.O(u)},n=self.webpackChunksim_categories=self.webpackChunksim_categories||[];n.forEach(t.bind(null,0)),n.push=t.bind(null,n.push.bind(n))})();var l=r.O(void 0,[431],(()=>r(848)));l=r.O(l)})();