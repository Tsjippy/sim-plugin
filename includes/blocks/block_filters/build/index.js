(()=>{"use strict";const e=window.wp.element,t=window.wp.components,n=window.wp.data,l=window.wp.coreData,s=window.wp.htmlEntities,{__}=wp.i18n,{createHigherOrderComponent:o}=wp.compose,{Fragment:i}=wp.element,{InspectorControls:r}=wp.blockEditor,{PanelBody:a,ToggleControl:c,CheckboxControl:d}=wp.components;wp.hooks.addFilter("blocks.registerBlockType","sim/block-filter-attribute",(function(e){return void 0!==e.attributes&&(e.attributes=Object.assign(e.attributes,{hideOnMobile:{type:"boolean"},onlyLoggedIn:{type:"boolean"},onlyOn:{type:"array"},phpFilters:{type:"array"}})),e}));const p=o((o=>p=>{const{attributes:u,setAttributes:m,isSelected:h}=p;if(!h)return(0,e.createElement)(i,null,(0,e.createElement)(o,p));null==u.onlyOn&&(u.onlyOn=[]);const[g,E]=(0,e.useState)(""),{initialSelectedPages:b,selectedPagesResolved:y}=(0,n.useSelect)((e=>{const t=["postType","page",{include:u.onlyOn}];return{initialSelectedPages:e(l.store).getEntityRecords(...t),selectedPagesResolved:e(l.store).hasFinishedResolution("getEntityRecords",t)}}),[]),{pages:w,pagesResolved:f}=(0,n.useSelect)((e=>{if(!g)return{pages:[],pagesResolved:!0};const t=["postType","page",{exclude:u.onlyOn,search:g,per_page:100,orderby:"relevance"}];return{pages:e(l.store).getEntityRecords(...t),pagesResolved:e(l.store).hasFinishedResolution("getEntityRecords",t)}}),[g]),O=function(e){e?(m({onlyOn:[...u.onlyOn,this]}),S([...k,w.find((e=>e.id==this))])):m({onlyOn:u.onlyOn.filter((e=>e!=this))})},C=function(n){let{hasResolved:l,items:o,showNoResults:r=!0}=n;return l?null!=o&&o.length?null==o?void 0:o.map((t=>(0,e.createElement)(d,{label:(0,s.decodeEntities)(t.title.rendered),onChange:O.bind(t.id),checked:u.onlyOn.includes(t.id)}))):r&&g?(0,e.createElement)("div",null," ",__("No search results","sim")):"":(0,e.createElement)(i,null,(0,e.createElement)(t.Spinner,null),(0,e.createElement)("br",null))},R=function(e){let t=this,n=[...u.phpFilters];""!=t||u.phpFilters.includes(e)?""==e?n=u.phpFilters.filter((e=>e!=t)):n[u.phpFilters.findIndex((e=>e==t))]=e:n.push(e),m({phpFilters:n}),L(""),P(v(n))},v=function(n){return n.map((n=>(0,e.createElement)(t.__experimentalInputControl,{isPressEnterToChange:!0,value:n,onChange:R.bind(n)})))};let F="";null!=u.phpFilters?F=v(u.phpFilters):u.phpFilters=[];const[k,S]=(0,e.useState)([]),[I,_]=(0,e.useState)(u.onlyOn.length>0?(0,e.createElement)(i,null,(0,e.createElement)("i",null," ",__("Currently selected pages","sim"),":"),(0,e.createElement)("br",null),(0,e.createElement)(C,{hasResolved:y,items:b,showNoResults:!1})):""),[x,P]=(0,e.useState)(F),[T,L]=(0,e.useState)("");return(0,e.useEffect)((()=>{S(b)}),[y]),(0,e.useEffect)((()=>{S(k.filter((e=>u.onlyOn.includes(e.id))))}),[u.onlyOn]),(0,e.useEffect)((()=>{_(C({hasResolved:y,items:k,showNoResults:!1}))}),[k]),(0,e.createElement)(i,null,(0,e.createElement)(o,p),(0,e.createElement)(r,null,(0,e.createElement)(a,{title:__("Block Visibility","sim"),initialOpen:!1},(0,e.createElement)(c,{label:__("Hide on mobile","sim"),checked:!!u.hideOnMobile,onChange:()=>m({hideOnMobile:!u.hideOnMobile})}),(0,e.createElement)(c,{label:__("Hide if not logged in","sim"),checked:!!u.onlyLoggedIn,onChange:()=>m({onlyLoggedIn:!u.onlyLoggedIn})}),(0,e.createElement)(t.__experimentalInputControl,{isPressEnterToChange:!0,label:"Add php filters by name. I.e 'is_tax'",value:T,onChange:R.bind("")}),x,(0,e.createElement)("strong",null,__("Select pages","sim")),(0,e.createElement)("br",null),__("Select pages you want this widget to show on","sim"),".",(0,e.createElement)("br",null),__("Leave empty for all pages","sim"),(0,e.createElement)("br",null),(0,e.createElement)("br",null),I,(0,e.createElement)("i",null,__("Use searchbox below to search for more pages to include","sim")),(0,e.createElement)(t.SearchControl,{onChange:E,value:g}),(0,e.createElement)(C,{hasResolved:f,items:w}))))}),"blockFilterControls");wp.hooks.addFilter("editor.BlockEdit","sim/block-filter-controls",p)})();