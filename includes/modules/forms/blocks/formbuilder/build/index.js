!function(){"use strict";var e,n={452:function(e,n,r){var t=window.wp.blocks,o=window.wp.element,i=window.wp.i18n,l=window.wp.apiFetch,u=r.n(l),a=window.wp.components,s=window.wp.blockEditor,c=JSON.parse('{"u2":"sim/formbuilder"}');(0,t.registerBlockType)(c.u2,{icon:"forms",edit:e=>{let{attributes:n,setAttributes:r,context:t}=e;const{formname:l}=n,{postId:c}=t,[f,m]=(0,o.useState)("");return(0,o.useEffect)((()=>{!async function(){if(null!=l){let e=await u()({path:`${sim.restApiPrefix}/forms/form_builder?formname=${l}&post=${c}`});m(e)}}()}),[l]),(0,o.createElement)(o.Fragment,null,(0,o.createElement)(s.InspectorControls,null,(0,o.createElement)(a.Panel,null,(0,o.createElement)(a.PanelBody,null,(0,o.createElement)(a.__experimentalInputControl,{label:(0,i.__)("Form name","sim"),isPressEnterToChange:!0,value:l,onChange:e=>r({formname:e})})))),(0,o.createElement)("div",(0,s.useBlockProps)(),""!=f&&f?wp.element.RawHTML({children:f.html}):(0,o.createElement)(a.__experimentalInputControl,{label:(0,i.__)("Form name","sim"),isPressEnterToChange:!0,value:l,onChange:e=>r({formname:e})})))},save:()=>null})}},r={};function t(e){var o=r[e];if(void 0!==o)return o.exports;var i=r[e]={exports:{}};return n[e](i,i.exports,t),i.exports}t.m=n,e=[],t.O=function(n,r,o,i){if(!r){var l=1/0;for(c=0;c<e.length;c++){r=e[c][0],o=e[c][1],i=e[c][2];for(var u=!0,a=0;a<r.length;a++)(!1&i||l>=i)&&Object.keys(t.O).every((function(e){return t.O[e](r[a])}))?r.splice(a--,1):(u=!1,i<l&&(l=i));if(u){e.splice(c--,1);var s=o();void 0!==s&&(n=s)}}return n}i=i||0;for(var c=e.length;c>0&&e[c-1][2]>i;c--)e[c]=e[c-1];e[c]=[r,o,i]},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,{a:n}),n},t.d=function(e,n){for(var r in n)t.o(n,r)&&!t.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:n[r]})},t.o=function(e,n){return Object.prototype.hasOwnProperty.call(e,n)},function(){var e={826:0,431:0};t.O.j=function(n){return 0===e[n]};var n=function(n,r){var o,i,l=r[0],u=r[1],a=r[2],s=0;if(l.some((function(n){return 0!==e[n]}))){for(o in u)t.o(u,o)&&(t.m[o]=u[o]);if(a)var c=a(t)}for(n&&n(r);s<l.length;s++)i=l[s],t.o(e,i)&&e[i]&&e[i][0](),e[i]=0;return t.O(c)},r=self.webpackChunksim_formbuilder=self.webpackChunksim_formbuilder||[];r.forEach(n.bind(null,0)),r.push=n.bind(null,r.push.bind(r))}();var o=t.O(void 0,[431],(function(){return t(452)}));o=t.O(o)}();