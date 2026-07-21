/* ChronoX Dashboard JS */
const sidebar = document.getElementById('sidebar');
const main = document.getElementById('main');
const toggle = document.getElementById('toggleSidebar');
const backdrop = document.getElementById('backdrop');
if(toggle){toggle.addEventListener('click',()=>{if(window.innerWidth<992){sidebar.classList.toggle('mobile-open');backdrop.classList.toggle('show')}else{sidebar.classList.toggle('collapsed');main.classList.toggle('expanded')}})}
if(backdrop){backdrop.addEventListener('click',()=>{sidebar.classList.remove('mobile-open');backdrop.classList.remove('show')})}

// Charts
if(typeof Chart!=='undefined'){Chart.defaults.font.family="'Plus Jakarta Sans',sans-serif";Chart.defaults.plugins.legend.display=false}
const wEl=document.getElementById('weeklyChart');
if(wEl){const l=window.weeklyLabels||['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];const d=window.weeklyData||[0,0,0,0,0,0,0];new Chart(wEl,{type:'line',data:{labels:l,datasets:[{data:d,borderColor:'#4F46E5',backgroundColor:'rgba(79,70,229,.06)',borderWidth:2.5,tension:.4,fill:true,pointBackgroundColor:'#4F46E5',pointRadius:4,pointHoverRadius:6}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,max:100,grid:{color:'#F0F1F5'},ticks:{callback:v=>v+'%'}},x:{grid:{display:false}}}}})}
const pEl=document.getElementById('pctChart');
if(pEl){const r=window.attendanceRate||0;new Chart(pEl,{type:'doughnut',data:{labels:['Present','Absent'],datasets:[{data:[r,100-r],backgroundColor:['#4F46E5','#F0F1F5'],borderWidth:0,borderRadius:4}]},options:{responsive:true,maintainAspectRatio:false,cutout:'76%'}})}

document.querySelectorAll('[data-confirm]').forEach(el=>{el.addEventListener('click',e=>{if(!confirm(el.dataset.confirm))e.preventDefault()})});
