const sb=document.getElementById('sb'),mn=document.getElementById('main'),tg=document.getElementById('tog'),bk=document.getElementById('bk');
if(tg)tg.onclick=()=>{if(innerWidth<992){sb.classList.toggle('mobile-open');bk.classList.toggle('show')}};
if(bk)bk.onclick=()=>{sb.classList.remove('mobile-open');bk.classList.remove('show')};
if(typeof Chart!=='undefined'){Chart.defaults.color='#8891ad';Chart.defaults.borderColor='rgba(255,255,255,.04)'}
const wE=document.getElementById('wChart');
if(wE)new Chart(wE,{type:'line',data:{labels:window.wL||[],datasets:[{data:window.wD||[],borderColor:'#7c6aff',backgroundColor:'rgba(124,106,255,.06)',borderWidth:2.5,tension:.4,fill:true,pointBackgroundColor:'#7c6aff',pointRadius:3,pointHoverRadius:5}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,max:100,grid:{color:'rgba(255,255,255,.03)'},ticks:{callback:v=>v+'%',font:{size:10}}},x:{grid:{display:false},ticks:{font:{size:10}}}}}});
const pE=document.getElementById('pChart');
if(pE){const r=window.aR||0;new Chart(pE,{type:'doughnut',data:{datasets:[{data:[r,100-r],backgroundColor:['#7c6aff','rgba(255,255,255,.05)'],borderWidth:0,borderRadius:4}]},options:{responsive:true,maintainAspectRatio:false,cutout:'76%',plugins:{legend:{display:false}}}})}
document.querySelectorAll('[data-confirm]').forEach(e=>e.onclick=ev=>{if(!confirm(e.dataset.confirm))ev.preventDefault()});
