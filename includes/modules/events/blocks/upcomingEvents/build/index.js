!function(){"use strict";var e,t={452:function(e,t,n){var a=window.wp.blocks,r=window.wp.element,l=window.wp.i18n,i=window.wp.blockEditor,o=window.wp.apiFetch,c=n.n(o),s=window.wp.components,u=JSON.parse('{"u2":"sim/upcomingevents"}');(0,a.registerBlockType)(u.u2,{icon:"calendar",edit:e=>{let{attributes:t,setAttributes:n}=e;const{items:a,months:o,categories:u,title:m}=t,v=function(e){let t={...u};t[this]=e,n({categories:t})},[p,f]=(0,r.useState)((0,r.createElement)(s.Spinner,null));(0,r.useEffect)((()=>{!async function(){f((0,r.createElement)(s.Spinner,null));const e=await c()({path:"/wp/v2/events"});f(e.map((e=>(0,r.createElement)(s.CheckboxControl,{key:e.id,label:e.name,onChange:v.bind(e.id),checked:u[e.id]}))))}()}),[t.categories]);const[d,h]=(0,r.useState)([]);return(0,r.useEffect)((()=>{(async()=>{let e="";if(null!=a&&(e+="?items"+a),null!=o&&(e+=""==e?"?":"&",e+="months="+o),null!=u){e+=""==e?"?":"&",e+="categories=";for(const t in u)e+=t+","}let t=await c()({path:`sim/v2/events/upcoming_events${e}`});t||(t=[]),h(t)})()}),[a,o,u]),(0,r.createElement)(r.Fragment,null,(0,r.createElement)(i.InspectorControls,null,(0,r.createElement)(s.Panel,null,(0,r.createElement)(s.PanelBody,null,(0,r.createElement)(s.TextControl,{label:"Block title",value:m,onChange:e=>n({title:e})}),"Select an category you want to exclude from the list",p,(0,r.createElement)(s.__experimentalNumberControl,{label:(0,l.__)("Select the maximum amount of events","sim"),value:a||10,onChange:e=>n({items:parseInt(e)}),min:1,max:20}),(0,r.createElement)(s.__experimentalNumberControl,{label:(0,l.__)("Select the range in months we will retrieve","sim"),value:o||2,onChange:e=>n({months:parseInt(e)}),min:1,max:12})))),(0,r.createElement)("div",(0,i.useBlockProps)(),(0,r.createElement)("aside",{className:"event"},(0,r.createElement)("h4",{className:"title"},m),(0,r.createElement)("div",{className:"upcomingevents_wrapper"},0===d.length?(0,r.createElement)("p",null,"No events found!"):d.map((e=>(0,r.createElement)("article",{className:"event-article",key:e.id},(0,r.createElement)("div",{className:"event-wrapper"},(0,r.createElement)("div",{className:"event-date"},(0,r.createElement)("span",null,e.day)," ",e.month),(0,r.createElement)("div",null,(0,r.createElement)("h4",{className:"event-title"},(0,r.createElement)("a",{href:e.url},e.title)),(0,r.createElement)("div",{className:"event-detail"},e.time))))))),(0,r.createElement)("a",{className:"calendar button sim",href:"./events"},"Calendar"))))},save:()=>null})}},n={};function a(e){var r=n[e];if(void 0!==r)return r.exports;var l=n[e]={exports:{}};return t[e](l,l.exports,a),l.exports}a.m=t,e=[],a.O=function(t,n,r,l){if(!n){var i=1/0;for(u=0;u<e.length;u++){n=e[u][0],r=e[u][1],l=e[u][2];for(var o=!0,c=0;c<n.length;c++)(!1&l||i>=l)&&Object.keys(a.O).every((function(e){return a.O[e](n[c])}))?n.splice(c--,1):(o=!1,l<i&&(i=l));if(o){e.splice(u--,1);var s=r();void 0!==s&&(t=s)}}return t}l=l||0;for(var u=e.length;u>0&&e[u-1][2]>l;u--)e[u]=e[u-1];e[u]=[n,r,l]},a.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return a.d(t,{a:t}),t},a.d=function(e,t){for(var n in t)a.o(t,n)&&!a.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},a.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},function(){var e={826:0,431:0};a.O.j=function(t){return 0===e[t]};var t=function(t,n){var r,l,i=n[0],o=n[1],c=n[2],s=0;if(i.some((function(t){return 0!==e[t]}))){for(r in o)a.o(o,r)&&(a.m[r]=o[r]);if(c)var u=c(a)}for(t&&t(n);s<i.length;s++)l=i[s],a.o(e,l)&&e[l]&&e[l][0](),e[l]=0;return a.O(u)},n=self.webpackChunksim_events=self.webpackChunksim_events||[];n.forEach(t.bind(null,0)),n.push=t.bind(null,n.push.bind(n))}();var r=a.O(void 0,[431],(function(){return a(452)}));r=a.O(r)}();