(()=>{"use strict";var e,l={294:(e,l,a)=>{const n=window.wp.blocks,r=window.React,t=window.wp.i18n,o=window.wp.blockEditor,i=window.wp.components,s=window.wp.element,c=window.wp.apiFetch;var p=a.n(c);const u=JSON.parse('{"UU":"sim/show-children"}');(0,n.registerBlockType)(u.UU,{icon:"category",edit:({attributes:e,setAttributes:l,context:a})=>{const{title:n,listtype:c,grandchildren:u,parents:d,grantparents:m}=e,{postId:h}=a,[w,g]=(0,s.useState)((0,r.createElement)(i.Spinner,null));return(0,s.useEffect)((()=>{!async function(){g((0,r.createElement)(i.Spinner,null));const e=await p()({path:sim.restApiPrefix+"/show_children",method:"POST",data:{title:n,listtype:c,grandchildren:u,parents:d,grantparents:m,postid:h}});g(e)}()}),[e]),(0,r.createElement)(r.Fragment,null,(0,r.createElement)(o.InspectorControls,null,(0,r.createElement)(i.Panel,null,(0,r.createElement)(i.PanelBody,null,(0,r.createElement)(i.ToggleControl,{label:(0,t.__)("Show title","sim"),checked:!!e.title,onChange:()=>l({title:!e.title})}),(0,r.createElement)(i.SelectControl,{label:"List style",value:e.listtype,options:[{label:"none",value:"none"},{label:"disc",value:"disc"},{label:"circle",value:"circle"},{label:"square",value:"square"},{label:"decimal",value:"decimal"},{label:"decimal-leading-zero",value:"decimal-leading-zero"},{label:"lower-roman",value:"lower-roman"},{label:"upper-roman",value:"upper-roman"},{label:"lower-greek",value:"lower-greek"},{label:"lower-latin",value:"lower-latin"},{label:"upper-latin",value:"upper-latin"},{label:"armenian",value:"armenian"},{label:"georgian",value:"georgian"},{label:"lower-alpha",value:"lower-alpha"},{label:"upper-alpha",value:"upper-alpha"}],onChange:e=>l({listtype:e}),__nextHasNoMarginBottom:!0}),(0,r.createElement)(i.ToggleControl,{label:(0,t.__)("Show grandchildren","sim"),checked:!!e.grandchildren,onChange:()=>l({grandchildren:!e.grandchildren})}),(0,r.createElement)(i.ToggleControl,{label:(0,t.__)("Show parents","sim"),checked:!!e.parents,onChange:()=>l({parents:!e.parents})}),(0,r.createElement)(i.__experimentalNumberControl,{label:(0,t.__)("Show grantparents","sim"),value:e.grantparents,onChange:e=>l({grantparents:parseInt(e)}),min:1,max:12})))),(0,r.createElement)("div",{...(0,o.useBlockProps)()},wp.element.RawHTML({children:w})))},save:()=>null})}},a={};function n(e){var r=a[e];if(void 0!==r)return r.exports;var t=a[e]={exports:{}};return l[e](t,t.exports,n),t.exports}n.m=l,e=[],n.O=(l,a,r,t)=>{if(!a){var o=1/0;for(p=0;p<e.length;p++){a=e[p][0],r=e[p][1],t=e[p][2];for(var i=!0,s=0;s<a.length;s++)(!1&t||o>=t)&&Object.keys(n.O).every((e=>n.O[e](a[s])))?a.splice(s--,1):(i=!1,t<o&&(o=t));if(i){e.splice(p--,1);var c=r();void 0!==c&&(l=c)}}return l}t=t||0;for(var p=e.length;p>0&&e[p-1][2]>t;p--)e[p]=e[p-1];e[p]=[a,r,t]},n.n=e=>{var l=e&&e.__esModule?()=>e.default:()=>e;return n.d(l,{a:l}),l},n.d=(e,l)=>{for(var a in l)n.o(l,a)&&!n.o(e,a)&&Object.defineProperty(e,a,{enumerable:!0,get:l[a]})},n.o=(e,l)=>Object.prototype.hasOwnProperty.call(e,l),(()=>{var e={57:0,350:0};n.O.j=l=>0===e[l];var l=(l,a)=>{var r,t,o=a[0],i=a[1],s=a[2],c=0;if(o.some((l=>0!==e[l]))){for(r in i)n.o(i,r)&&(n.m[r]=i[r]);if(s)var p=s(n)}for(l&&l(a);c<o.length;c++)t=o[c],n.o(e,t)&&e[t]&&e[t][0](),e[t]=0;return n.O(p)},a=self.webpackChunksim_children=self.webpackChunksim_children||[];a.forEach(l.bind(null,0)),a.push=l.bind(null,a.push.bind(a))})();var r=n.O(void 0,[350],(()=>n(294)));r=n.O(r)})();