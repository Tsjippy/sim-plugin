(()=>{"use strict";var e,t={847:()=>{const e=window.wp.blocks,t=window.React,n=window.wp.i18n,r=window.wp.blockEditor,o=window.wp.components,a=window.wp.data,l=window.wp.coreData,c=JSON.parse('{"UU":"sim/show-cats"}');(0,e.registerBlockType)(c.UU,{icon:"category",edit:({attributes:e,setAttributes:c})=>{const{count:s}=e,{categories:i,hasResolved:u}=(0,a.useSelect)((e=>{const t=["taxonomy","category"];return{categories:e(l.store).getEntityRecords(...t),hasResolved:e(l.store).hasFinishedResolution("getEntityRecords",t)}}),[]);return(0,t.createElement)(t.Fragment,null,(0,t.createElement)(r.InspectorControls,null,(0,t.createElement)(o.Panel,null,(0,t.createElement)(o.PanelBody,null,(0,t.createElement)(o.ToggleControl,{label:(0,n.__)("Show categories count","sim"),checked:!!e.count,onChange:()=>c({count:!e.count})})))),(0,t.createElement)("div",{...(0,r.useBlockProps)()},(0,t.createElement)("aside",{className:"event"},(0,t.createElement)("h4",{className:"title"},"Categories"),(0,t.createElement)("div",{className:"upcomingevents_wrapper"},u?i?.length?(0,t.createElement)("ul",null,i?.map((e=>{let n="";return s&&(n=(0,t.createElement)(t.Fragment,null,"  (",(0,t.createElement)("span",{className:"cat-count"},e.count),")")),(0,t.createElement)("li",{key:e.id},(0,t.createElement)("a",{href:e.link},e.name,n))}))):(0,t.createElement)("div",null," ",(0,n.__)("No categories found","sim")):(0,t.createElement)(t.Fragment,null,(0,t.createElement)(o.Spinner,null),(0,t.createElement)("br",null))))))},save:()=>null})}},n={};function r(e){var o=n[e];if(void 0!==o)return o.exports;var a=n[e]={exports:{}};return t[e](a,a.exports,r),a.exports}r.m=t,e=[],r.O=(t,n,o,a)=>{if(!n){var l=1/0;for(u=0;u<e.length;u++){n=e[u][0],o=e[u][1],a=e[u][2];for(var c=!0,s=0;s<n.length;s++)(!1&a||l>=a)&&Object.keys(r.O).every((e=>r.O[e](n[s])))?n.splice(s--,1):(c=!1,a<l&&(l=a));if(c){e.splice(u--,1);var i=o();void 0!==i&&(t=i)}}return t}a=a||0;for(var u=e.length;u>0&&e[u-1][2]>a;u--)e[u]=e[u-1];e[u]=[n,o,a]},r.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e={57:0,350:0};r.O.j=t=>0===e[t];var t=(t,n)=>{var o,a,l=n[0],c=n[1],s=n[2],i=0;if(l.some((t=>0!==e[t]))){for(o in c)r.o(c,o)&&(r.m[o]=c[o]);if(s)var u=s(r)}for(t&&t(n);i<l.length;i++)a=l[i],r.o(e,a)&&e[a]&&e[a][0](),e[a]=0;return r.O(u)},n=self.webpackChunksim_categories=self.webpackChunksim_categories||[];n.forEach(t.bind(null,0)),n.push=t.bind(null,n.push.bind(n))})();var o=r.O(void 0,[350],(()=>r(847)));o=r.O(o)})();