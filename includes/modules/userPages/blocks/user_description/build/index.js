(()=>{"use strict";var e,n={211:(e,n,t)=>{const r=window.wp.blocks,l=window.wp.element,o=window.wp.i18n,a=window.wp.apiFetch;var s=t.n(a);const i=window.wp.components,c=window.wp.coreData,u=window.wp.data,p=window.wp.blockEditor,d=JSON.parse('{"u2":"sim/linkeduserdescription"}');(0,r.registerBlockType)(d.u2,{icon:"admin-users",edit:e=>{let{attributes:n,setAttributes:t}=e;const{id:r,picture:a,phone:d,email:m,style:h}=n,[w,v]=(0,l.useState)(""),{users:g,hasResolved:f}=(0,u.useSelect)((e=>{if(!w)return{users:[],hasResolved:!0};const t={exclude:n.onlyOn,search:w,per_page:100,context:"view"};return{users:e(c.store).getUsers(t),hasResolved:e(c.store).hasFinishedResolution("getUsers",[t])}}),[w]),[E,b]=(0,l.useState)((0,l.createElement)(i.Spinner,null));return(0,l.useEffect)((async()=>{b((0,l.createElement)(i.Spinner,null));const e=await s()({path:`${sim.restApiPrefix}/userpage/linked_user_description?id=${r}&picture=${a}&phone=${d}&email=${m}&style=${h}`});b(e)}),[n]),(0,l.createElement)(l.Fragment,null,(0,l.createElement)(p.InspectorControls,null,(0,l.createElement)(i.Panel,null,(0,l.createElement)(i.PanelBody,null,(0,l.createElement)("i",null,(0,o.__)("Use searchbox below to search an user to display","sim")),(0,l.createElement)(i.SearchControl,{onChange:v,value:w}),(0,l.createElement)((function(){if(!f)return(0,l.createElement)(l.Fragment,null,(0,l.createElement)(i.Spinner,null),(0,l.createElement)("br",null));if(null==g||!g.length)return(0,l.createElement)("div",null," ",(0,o.__)("No users found","sim"));let e=g.map((e=>({label:e.name,value:e.id})));return(0,l.createElement)(l.Fragment,null,(0,l.createElement)(i.RadioControl,{selected:parseInt(r),options:e,onChange:e=>{t({id:e})}}))}),null),(0,l.createElement)(i.ToggleControl,{label:(0,o.__)("Show picture","sim"),checked:!!n.picture,onChange:()=>t({picture:!n.picture})}),(0,l.createElement)(i.ToggleControl,{label:(0,o.__)("Show phonenumbers","sim"),checked:!!n.phone,onChange:()=>t({phone:!n.phone})}),(0,l.createElement)(i.ToggleControl,{label:(0,o.__)("Show e-mail address","sim"),checked:!!n.email,onChange:()=>t({email:!n.email})}),(0,l.createElement)(i.__experimentalInputControl,{isPressEnterToChange:!0,label:"Optional extra styling",value:h,onChange:e=>t({style:e})})))),(0,l.createElement)("div",(0,p.useBlockProps)(),wp.element.RawHTML({children:E})))},save:()=>null})}},t={};function r(e){var l=t[e];if(void 0!==l)return l.exports;var o=t[e]={exports:{}};return n[e](o,o.exports,r),o.exports}r.m=n,e=[],r.O=(n,t,l,o)=>{if(!t){var a=1/0;for(u=0;u<e.length;u++){t=e[u][0],l=e[u][1],o=e[u][2];for(var s=!0,i=0;i<t.length;i++)(!1&o||a>=o)&&Object.keys(r.O).every((e=>r.O[e](t[i])))?t.splice(i--,1):(s=!1,o<a&&(a=o));if(s){e.splice(u--,1);var c=l();void 0!==c&&(n=c)}}return n}o=o||0;for(var u=e.length;u>0&&e[u-1][2]>o;u--)e[u]=e[u-1];e[u]=[t,l,o]},r.n=e=>{var n=e&&e.__esModule?()=>e.default:()=>e;return r.d(n,{a:n}),n},r.d=(e,n)=>{for(var t in n)r.o(n,t)&&!r.o(e,t)&&Object.defineProperty(e,t,{enumerable:!0,get:n[t]})},r.o=(e,n)=>Object.prototype.hasOwnProperty.call(e,n),(()=>{var e={826:0,431:0};r.O.j=n=>0===e[n];var n=(n,t)=>{var l,o,a=t[0],s=t[1],i=t[2],c=0;if(a.some((n=>0!==e[n]))){for(l in s)r.o(s,l)&&(r.m[l]=s[l]);if(i)var u=i(r)}for(n&&n(t);c<a.length;c++)o=a[c],r.o(e,o)&&e[o]&&e[o][0](),e[o]=0;return r.O(u)},t=self.webpackChunksim_linkedduserdescription=self.webpackChunksim_linkedduserdescription||[];t.forEach(n.bind(null,0)),t.push=n.bind(null,t.push.bind(t))})();var l=r.O(void 0,[431],(()=>r(211)));l=r.O(l)})();