(()=>{"use strict";const e=window.React,t=window.wp.i18n,n=window.wc.wcBlocksRegistry,o=window.wp.htmlEntities,a=(0,window.wc.wcSettings.getPaymentMethodData)("postofficebank",{}),c=(0,t.__)("Postal transfer","woocommerce-for-japan"),i=(0,o.decodeEntities)(a?.title||"")||c,s=()=>(0,o.decodeEntities)(a.description||""),r={name:"postofficebank",label:(0,e.createElement)((t=>{const{PaymentMethodLabel:n}=t.components;return(0,e.createElement)(n,{text:i})}),null),content:(0,e.createElement)(s,null),edit:(0,e.createElement)(s,null),canMakePayment:()=>!0,ariaLabel:i,supports:{features:a.supports}};(0,n.registerPaymentMethod)(r)})();