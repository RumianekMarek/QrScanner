import{V as c,r as a,h as i,j as e}from"./app-CYjuILJk.js";import{A as E,N as A}from"./AuthenticatedLayout-A-RPLwLw.js";import{P as k}from"./PrimaryButton-CzUneS36.js";import{S as P}from"./Select-6VhkIqLN.js";import{C as F}from"./Checkbox-BiY4J94L.js";import"./transition-BwY331Il.js";function I({usersList:u}){const{props:l}=c(),{flash:f,message:m,scannerData:n}=c().props;console.log(l);const d=c().props.flash;c().props.auth.user;const[j,h]=a.useState(!1),[N,p]=a.useState(null),[r,y]=a.useState(""),[g,S]=a.useState([]),[v,w]=a.useState(!1),C=async t=>{i.post(route("scanner.send"),{csvData:t})};typeof d=="string"&&d.trim()!==""&&C(d),a.useEffect(()=>{r&&i.post(route("admin.users.list",{id:r}))},[r]),a.useEffect(()=>{if(m){if(l.auth.user.email.length>30){const s=l.auth.user.email.split("@");s[0]+""+s[0]}let t=e.jsxs(e.Fragment,{children:[e.jsx("p",{className:"text-center",children:m}),e.jsx("p",{className:"text-xl",children:l.auth.user.email})]});p(t),h(!0)}setTimeout(()=>{x()},5e3)},[f]),a.useEffect(()=>{if(n){const t=n.split(";;"),s=Object.entries(t).filter(([b,o])=>o.length>=10).map(([b,o])=>({key:b,...JSON.parse(o)}));S(s)}},[n]);const x=()=>{h(null),p(!1)},D=(t,s)=>{i.post(route("admin.users.restore",{id:t,qrCode:s}))};return e.jsxs(E,{children:[e.jsxs("div",{className:"w-1/2 ms-20",children:[e.jsx(P,{name:"userSelector",options:u,onChange:t=>y(t.target.value)}),e.jsx(F,{name:"force_change",onChange:()=>w(t=>!t)})]}),e.jsxs("div",{className:"sm:m-5",children:[e.jsx("div",{className:"float-right me-5",children:e.jsx(A,{href:route("scanner.download",{id:r}),method:"post",className:" bg-green-300 text-xl ps-4 pe-4 pt-1 pb-1 m-5 rounded",children:"Pobierz"})}),e.jsxs("table",{className:"table-auto border-collapse border border-gray-300 mt-4 max-w-full",children:[e.jsx("thead",{children:e.jsxs("tr",{children:[e.jsx("th",{className:"border px-4 py-2 text-center hidden sm:table-cell",children:"ID"}),e.jsx("th",{className:"border px-4 py-2 text-center hidden sm:table-cell",children:"Imię"}),e.jsx("th",{className:"border px-4 py-2 text-center hidden sm:table-cell",children:"Firma"}),e.jsx("th",{className:"border px-4 py-2 text-center hidden sm:table-cell",children:"Email"}),e.jsx("th",{className:"border px-4 py-2 text-center hidden sm:table-cell",children:"Telefon"}),e.jsx("th",{className:"border px-4 py-2 text-center hidden sm:table-cell",children:"Kod QR"}),e.jsx("th",{className:"border px-4 py-2 text-center hidden sm:table-cell",children:"Status"})]})}),e.jsx("tbody",{children:Object.entries(g).map(([t,s])=>e.jsxs("tr",{className:"align-center",children:[e.jsx("td",{className:"border px-4 py-2 text-center hidden sm:table-cell",children:t}),e.jsx("td",{className:"border px-4 py-2 text-center hidden sm:table-cell",children:s.name}),e.jsx("td",{className:"border px-4 py-2 text-center hidden sm:table-cell",children:s.company}),e.jsx("td",{className:"border px-4 py-2 text-center hidden sm:table-cell",children:s.email}),e.jsx("td",{className:"border px-4 py-2 text-center hidden sm:table-cell",children:s.phone}),e.jsxs("td",{className:"border px-4 py-2 text-center ",children:[s.qrCode??"",e.jsxs("span",{className:"sm:hidden",children:[e.jsx("br",{}),s.email,e.jsx("br",{}),s.phone]})]}),e.jsx("td",{className:"border px-4 py-2 text-center ",children:s.status&&s.status!="false"&&v!=!0?s.status:e.jsx("button",{onClick:()=>D(r,s.qrCode),className:" bg-green-300 text-xl ps-4 pe-4 pt-1 pb-1 rounded",children:"Ponów"})})]},t))})]})]}),j&&e.jsx("div",{className:"popup-container fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-20",children:e.jsxs("div",{className:"popup-content bg-white p-6 rounded shadow-lg w-1/3 flex flex-col items-center min-w-80",children:[N,e.jsx(k,{className:"mt-10 bg-red-500 active:bg-red-900",onClick:x,children:"Close"})]})})]})}export{I as default};
