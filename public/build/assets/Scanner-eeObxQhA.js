import{V as n,j as e,M as F,a as M,r as a,h as v}from"./app-Bkg2rRel.js";import{A,N as b}from"./AuthenticatedLayout-JZOzTeb_.js";import{P as u}from"./PrimaryButton-BuULth61.js";import E from"./CameraAcces-Bhe-ajzJ.js";import L from"./DeviceScannerAcces-DWsTZN5g.js";import{T as Q}from"./TextInput-C4nmt_oX.js";import"./transition-DGAQShj_.js";import"./CameraScan-CzIIW5Zw.js";function O({children:t}){n();const l=n().props.auth.user;return e.jsxs(A,{header:e.jsx("h2",{className:"text-xl font-semibold leading-tight text-gray-800",children:"Qr Skaner"}),children:[e.jsx(F,{title:"Qr Skaner"}),l.admin?e.jsxs("div",{className:"py-12 flex",children:[e.jsx("div",{className:"w-1/2 text-center",children:e.jsx(b,{href:route("scanner.create",{mode:"device"}),active:route().current("scanner.create"),children:e.jsx(u,{children:"Czytnik"})})}),e.jsx("div",{className:"w-1/2 text-center",children:e.jsx(b,{href:route("scanner.create"),active:route().current("scanner.create"),children:e.jsx(u,{className:"justify-center",children:"Kamera"})})})]}):null,e.jsx("main",{className:"mt-10",children:t})]})}function W({mode:t}){const{data:l,setData:N,post:i,processing:z,reset:h}=M({qrCode:""}),{props:d}=n(),{flash:r}=n().props,c=n().props.flash,[C,x]=a.useState(!1),[w,p]=a.useState(null),[f,j]=a.useState(!0),g=e.jsxs(e.Fragment,{children:[e.jsx("h3",{className:"text-2xl font-bold",children:"Ostatnie Skanowania"}),Object.entries(d.lastScans).map(([s,o])=>{if(o.length<5)return;const m=JSON.parse(o);return e.jsx("p",{children:m.email?m.email:m.qrCode},s)})]}),S=()=>{x(null),p(!1)},y=s=>{s.preventDefault(),i(route("logout"))};a.useEffect(()=>{if(r&&j(!0),r&&r.message){let s=e.jsxs("p",{className:"text-center",children:[r.message," ",e.jsx("br",{}),e.jsx("span",{children:d.auth.user.email})]});p(s),x(!0)}},[r]);const k=s=>{s.preventDefault(),i(route("scanner.download",{id:d.auth.user.id}))};a.useEffect(()=>{const s=async o=>{v.post(route("scanner.send"),{csvData:o})};typeof c=="string"&&c.trim()!==""&&s(c)},[c]);const q=s=>{v.post(route("scanner.store"),{qrCode:s},{onFinish:()=>{j(!1),h("qrCode")}})},D=s=>{s.preventDefault(),i(route("scanner.store"),{onFinish:()=>{h("qrCode")}})};return e.jsxs(e.Fragment,{children:[e.jsx("form",{onSubmit:D,className:"qr-form h-0",children:e.jsx(Q,{id:"qrCode",name:"qrCode",placeholder:"QrCode",value:l.qrCode,className:"mt-1 block w-full w-4/6 pointer-events-none",autoComplete:"off",isFocused:!0,onChange:s=>N("qrCode",s.target.value),disabled:t==="camera",required:!0})}),t==="camera"&&f&&e.jsx(O,{children:e.jsxs("div",{className:"text-center",children:[g,e.jsx(E,{scanned:q})]})}),t==="device"&&f&&e.jsxs(e.Fragment,{children:[e.jsx("button",{onClick:k,className:"absolute top-0 left-0 bg-green-500 text-white text-2xl color-white ps-4 pe-4 pt-1 pb-1 m-5 rounded",children:"Wyślij Dane"}),e.jsx("button",{onClick:y,className:"absolute top-0 right-0 bg-red-500 text-white text-2xl color-white ps-4 pe-4 pt-1 pb-1 m-5 rounded",children:"Logout"}),e.jsx("div",{className:"text-center mt-20",children:g}),e.jsx(L,{})]}),C&&e.jsx("div",{className:"popup-container fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-20",children:e.jsxs("div",{className:"popup-content bg-white p-6 rounded shadow-lg w-1/3 flex flex-col items-center min-w-80",children:[w,e.jsx(u,{className:"mt-10 bg-red-500 active:bg-red-900",onClick:S,children:"Close"})]})})]})}export{W as default};
