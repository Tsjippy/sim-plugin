(()=>{"use strict";var e,t={452:(e,t,n)=>{const a=window.wp.blocks,l=window.wp.element,r=window.wp.i18n,s=window.wp.blockEditor,o=window.wp.apiFetch;var i=n.n(o);const c=window.wp.components,m=JSON.parse('{"u2":"sim-events/sim-events"}');(0,a.registerBlockType)(m.u2,{edit:e=>{let{attributes:t,setAttributes:n}=e;var{items:a,months:o,categories:m,home:u}=t;null==m&&(m=[]);const v=function(e){let t=Object.assign({},m);t[this]=e,n({categories:t})},[p,h]=(0,l.useState)([]);(0,l.useEffect)((async()=>{const e=await i()({path:"/wp/v2/events"});h(e.map((e=>(0,l.createElement)(c.CheckboxControl,{label:e.name,onChange:v.bind(e.id),checked:m[e.id]}))))}),[]);const[d,w]=(0,l.useState)([]);return(0,l.useEffect)((()=>{(async()=>{let e="";if(null!=a&&(e+="?items"+a),null!=o&&(e+=""==e?"?":"&",e+="months="+o),null!=m){e+=""==e?"?":"&",e+="categories=";for(const t in m)e+=t+","}let t=await i()({path:"/sim/v1/events/upcoming_events"+e});t||(t=[]),w(t)})()}),[a,o,m]),(0,l.createElement)(l.Fragment,null,(0,l.createElement)(s.InspectorControls,null,(0,l.createElement)(c.Panel,null,(0,l.createElement)(c.PanelBody,null,"Select an category you want to exclude from the list",p,(0,l.createElement)(c.__experimentalNumberControl,{label:(0,r.__)("Select the maximum amount of events","sim"),value:a||10,onChange:e=>n({items:parseInt(e)}),min:1,max:20}),(0,l.createElement)(c.__experimentalNumberControl,{label:(0,r.__)("Select the range in months we will retrieve","sim"),value:o||2,onChange:e=>n({months:parseInt(e)}),min:1,max:12}),(0,l.createElement)(c.CheckboxControl,{label:(0,r.__)("Only show on homepage","sim"),onChange:e=>n({home:e}),checked:u})))),(0,l.createElement)("div",(0,s.useBlockProps)(),(0,l.createElement)("aside",{class:"event"},(0,l.createElement)("h4",{class:"title"},"Upcoming events"),(0,l.createElement)("div",{class:"upcomingevents_wrapper"},0===d.length?(0,l.createElement)("p",null,"No events found!"):d.map((e=>(0,l.createElement)("article",{class:"event-article"},(0,l.createElement)("div",{class:"event-wrapper"},(0,l.createElement)("div",{class:"event-date"},(0,l.createElement)("span",null,e.day)," ",e.month),(0,l.createElement)("h4",{class:"event-title"},(0,l.createElement)("a",{href:e.url},e.title)),(0,l.createElement)("div",{class:"event-detail"},e.time)))))),(0,l.createElement)("a",{class:"calendar button sim",href:"./events"},"Calendar"))))},save:()=>null})}},n={};function a(e){var l=n[e];if(void 0!==l)return l.exports;var r=n[e]={exports:{}};return t[e](r,r.exports,a),r.exports}a.m=t,e=[],a.O=(t,n,l,r)=>{if(!n){var s=1/0;for(m=0;m<e.length;m++){for(var[n,l,r]=e[m],o=!0,i=0;i<n.length;i++)(!1&r||s>=r)&&Object.keys(a.O).every((e=>a.O[e](n[i])))?n.splice(i--,1):(o=!1,r<s&&(s=r));if(o){e.splice(m--,1);var c=l();void 0!==c&&(t=c)}}return t}r=r||0;for(var m=e.length;m>0&&e[m-1][2]>r;m--)e[m]=e[m-1];e[m]=[n,l,r]},a.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return a.d(t,{a:t}),t},a.d=(e,t)=>{for(var n in t)a.o(t,n)&&!a.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},a.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e={826:0,431:0};a.O.j=t=>0===e[t];var t=(t,n)=>{var l,r,[s,o,i]=n,c=0;if(s.some((t=>0!==e[t]))){for(l in o)a.o(o,l)&&(a.m[l]=o[l]);if(i)var m=i(a)}for(t&&t(n);c<s.length;c++)r=s[c],a.o(e,r)&&e[r]&&e[r][0](),e[r]=0;return a.O(m)},n=globalThis.webpackChunksim_events=globalThis.webpackChunksim_events||[];n.forEach(t.bind(null,0)),n.push=t.bind(null,n.push.bind(n))})();var l=a.O(void 0,[431],(()=>a(452)));l=a.O(l)})();