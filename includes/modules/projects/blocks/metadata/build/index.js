!function(){"use strict";var e,n={211:function(e,n,t){var r=window.wp.blocks,a=window.wp.element,o=window.wp.i18n,i=window.wp.data,l=window.wp.coreData,s=window.wp.components,u=window.wp.blockEditor,c=window.wp.apiFetch,p=t.n(c),m=JSON.parse('{"u2":"sim/projectmeta"}');(0,r.registerBlockType)(m.u2,{icon:"calendar",edit:()=>{const e=(0,u.useBlockProps)(),n=(0,i.useSelect)((e=>e("core/editor").getCurrentPostType()),[]),[t,r]=(0,l.useEntityProp)("postType",n,"meta"),[c,m]=(0,a.useState)([]),f=t.number,g=t.url,_=t.ministry,v=null==t.manager||""==t.manager?{}:JSON.parse(t.manager);(0,a.useEffect)((async()=>{let e=(await p()({path:"/sim/v2/projects/ministries?slug=ministry"})).map((e=>({label:e.post_title,value:e.ID})));e.unshift({label:(0,o.__)("Please select a ministry","sim"),value:""}),m(e)}),[]);const d=(e,n)=>{let a={...t};if(n.startsWith("manager")){let t=n.split("-")[1];n="manager";let r={};""!=v&&(r={...v}),r[t]=e,e=JSON.stringify(r)}a[n]=e,r(a)};return(0,a.createElement)("div",e,(0,a.createElement)("h2",null,(0,o.__)("Project Details")),(0,a.createElement)(s.__experimentalInputControl,{isPressEnterToChange:!0,label:(0,o.__)("Project number"),value:f,onChange:e=>d(e,"number")}),(0,a.createElement)(s.__experimentalInputControl,{isPressEnterToChange:!0,label:(0,o.__)("Manager name"),value:v.name,onChange:e=>d(e,"manager-name")}),(0,a.createElement)(s.__experimentalInputControl,{isPressEnterToChange:!0,label:(0,o.__)("Phone number"),value:v.tel,onChange:e=>d(e,"manager-tel")}),(0,a.createElement)(s.__experimentalInputControl,{isPressEnterToChange:!0,label:(0,o.__)("E-mail address"),value:v.email,onChange:e=>d(e,"manager-email")}),(0,a.createElement)(s.__experimentalInputControl,{isPressEnterToChange:!0,label:(0,o.__)("Website url"),value:g,onChange:e=>d(e,"url")}),(0,a.createElement)(s.SelectControl,{label:"Ministry",value:_,options:c,onChange:e=>d(e,"ministry"),__nextHasNoMarginBottom:!0}))},save:()=>null})}},t={};function r(e){var a=t[e];if(void 0!==a)return a.exports;var o=t[e]={exports:{}};return n[e](o,o.exports,r),o.exports}r.m=n,e=[],r.O=function(n,t,a,o){if(!t){var i=1/0;for(c=0;c<e.length;c++){t=e[c][0],a=e[c][1],o=e[c][2];for(var l=!0,s=0;s<t.length;s++)(!1&o||i>=o)&&Object.keys(r.O).every((function(e){return r.O[e](t[s])}))?t.splice(s--,1):(l=!1,o<i&&(i=o));if(l){e.splice(c--,1);var u=a();void 0!==u&&(n=u)}}return n}o=o||0;for(var c=e.length;c>0&&e[c-1][2]>o;c--)e[c]=e[c-1];e[c]=[t,a,o]},r.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return r.d(n,{a:n}),n},r.d=function(e,n){for(var t in n)r.o(n,t)&&!r.o(e,t)&&Object.defineProperty(e,t,{enumerable:!0,get:n[t]})},r.o=function(e,n){return Object.prototype.hasOwnProperty.call(e,n)},function(){var e={826:0,431:0};r.O.j=function(n){return 0===e[n]};var n=function(n,t){var a,o,i=t[0],l=t[1],s=t[2],u=0;if(i.some((function(n){return 0!==e[n]}))){for(a in l)r.o(l,a)&&(r.m[a]=l[a]);if(s)var c=s(r)}for(n&&n(t);u<i.length;u++)o=i[u],r.o(e,o)&&e[o]&&e[o][0](),e[o]=0;return r.O(c)},t=self.webpackChunksim_pendingpages=self.webpackChunksim_pendingpages||[];t.forEach(n.bind(null,0)),t.push=n.bind(null,t.push.bind(t))}();var a=r.O(void 0,[431],(function(){return r(211)}));a=r.O(a)}();