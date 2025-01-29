import{a as k,r,j as e,M as C,U as L}from"./app-CJ6Xl-39.js";import{C as F}from"./Checkbox-B7MgSOFX.js";import{I as x}from"./InputError-C-lmnXss.js";import{I as p}from"./InputLabel-Cioyb5hC.js";import{P as l}from"./PrimaryButton-D8LsSF9I.js";import{T as n}from"./TextInput-B70UBh8I.js";import{G as U}from"./GuestLayout-Cg5FezQ4.js";function q({status:m,canResetPassword:h}){const{data:t,setData:a,post:i,processing:g,errors:c,reset:d}=k({email:"",password:"",remember:!1,targetUrl:""}),[j,u]=r.useState(!1),[b,f]=r.useState(null),o=r.useRef(null),w=()=>{const s=e.jsx(e.Fragment,{children:e.jsx("h4",{className:"text-4xl font-bold m-20 text-center",children:"Zeskanuj Kod Logowania"},"key")});f(s),u(!0)},v=()=>{u(!1),f(null)};r.useEffect(()=>{const s=()=>{o.current&&o.current.focus()};return document.addEventListener("click",s),()=>{document.removeEventListener("click",s)}},[]);const N=s=>{s.preventDefault(),i(route("login"),{onFinish:()=>d("password")})},y=s=>{s.preventDefault(),i(route("post.token"),{onFinish:()=>d("targetUrl")})};return e.jsxs(U,{children:[e.jsx(C,{title:"Log in"}),m&&e.jsx("div",{className:"mb-4 text-lg font-bold text-red-600 text-center",children:m}),e.jsx("div",{className:"text-center mb-10",children:e.jsx(l,{className:"ms-4",onClick:w,children:"Logowanie do skanera"})}),e.jsxs("form",{onSubmit:N,children:[e.jsxs("div",{children:[e.jsx(p,{htmlFor:"email",value:"Email"}),e.jsx(n,{id:"email",type:"email",name:"email",value:t.email,className:"mt-1 block w-full",autoComplete:"username",onChange:s=>a("email",s.target.value)}),e.jsx(x,{message:c.email,className:"mt-2"})]}),e.jsxs("div",{className:"mt-4",children:[e.jsx(p,{htmlFor:"password",value:"Password"}),e.jsx(n,{id:"password",type:"password",name:"password",value:t.password,className:"mt-1 block w-full",autoComplete:"current-password",onChange:s=>a("password",s.target.value)}),e.jsx(x,{message:c.password,className:"mt-2"})]}),e.jsx("div",{className:"mt-4 block",children:e.jsxs("label",{className:"flex items-center",children:[e.jsx(F,{name:"remember",checked:t.remember,onChange:s=>a("remember",s.target.checked)}),e.jsx("span",{className:"ms-2 text-sm text-gray-600",children:"Remember me"})]})}),e.jsxs("div",{className:"mt-4 flex items-center justify-end",children:[h&&e.jsx(L,{href:route("password.request"),className:"rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2",children:"Forgot your password?"}),e.jsx(l,{className:"ms-4",disabled:g,children:"Log in"})]})]}),j&&e.jsx("div",{className:"popup-container fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-20",children:e.jsxs("div",{className:"popup-content bg-white p-6 rounded shadow-lg w-1/3 flex flex-col items-center min-w-80",children:[b,e.jsx("form",{onSubmit:y,className:"qr-form h-0 opacity-0",children:e.jsx(n,{ref:o,id:"targetUrl",type:"text",name:"targetUrl",value:t.targetUrl,className:"mt-1 block w-full w-4/6 pointer-events-none opacity-0",isFocused:!0,autoComplete:"off",onChange:s=>a("targetUrl",s.target.value)})}),e.jsx(l,{className:"mt-10 bg-red-500 active:bg-red-900",onClick:v,children:"Close"})]})})]})}export{q as default};
