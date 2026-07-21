const s=document.getElementById('sidebar'),m=document.getElementById('main'),t=document.getElementById('toggleSidebar'),b=document.getElementById('backdrop');
if(t)t.addEventListener('click',()=>{if(innerWidth<992){s.classList.toggle('mobile-open');b.classList.toggle('show')}});
if(b)b.addEventListener('click',()=>{s.classList.remove('mobile-open');b.classList.remove('show')});
if(typeof Chart!=='undefined'){Chart.defaults.color='#7c8298';Chart.defaults.borderColor='rgba(255,255,255,.05)'}
const wE=document.getElementById('weeklyChart');
if(wE){new Chart(wE,{type:'line',data:{labels:window.wL||[],datasets:[{data:window.wD||[],borderColor:'#6c63ff',backgroundColor:'rgba(108,99,255,.08)',borderWidth:2,tension:.4,fill:true,pointBackgroundColor:'#6c63ff',pointRadius:3}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,max:100,grid:{color:'rgba(255,255,255,.04)'},ticks:{callback:v=>v+'%'}},x:{grid:{display:false}}}}})}
const pE=document.getElementById('pctChart');
if(pE){const r=window.aR||0;new Chart(pE,{type:'doughnut',data:{datasets:[{data:[r,100-r],backgroundColor:['#6c63ff','rgba(255,255,255,.05)'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:'78%',plugins:{legend:{display:false}}}})}
document.querySelectorAll('[data-confirm]').forEach(e=>{e.addEventListener('click',ev=>{if(!confirm(e.dataset.confirm))ev.preventDefault()})});
