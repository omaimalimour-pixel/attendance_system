/* ChronoX Dashboard — matching landing theme */
const sb=document.getElementById('sb')||document.getElementById('sidebar');
const mn=document.getElementById('main');
const tg=document.getElementById('tog')||document.getElementById('toggleSidebar');
const bk=document.getElementById('bk')||document.getElementById('backdrop');
if(tg)tg.onclick=()=>{if(innerWidth<992){sb&&sb.classList.toggle('mobile-open');bk&&bk.classList.toggle('show')}};
if(bk)bk.onclick=()=>{sb&&sb.classList.remove('mobile-open');bk&&bk.classList.remove('show')};

/* Chart.js dark defaults — same feel as landing page */
if(typeof Chart!=='undefined'){
  Chart.defaults.font.family="'Inter',system-ui,sans-serif";
  Chart.defaults.color='#9AA2C0';
  Chart.defaults.borderColor='rgba(255,255,255,.06)';
  Chart.defaults.plugins.tooltip.backgroundColor='#0A0C18';
  Chart.defaults.plugins.tooltip.titleColor='#EDF0FA';
  Chart.defaults.plugins.tooltip.bodyColor='#9AA2C0';
  Chart.defaults.plugins.tooltip.borderColor='rgba(255,255,255,.12)';
  Chart.defaults.plugins.tooltip.borderWidth=1;
  Chart.defaults.plugins.tooltip.cornerRadius=10;
  Chart.defaults.plugins.tooltip.padding=12;
}

/* Weekly attendance line chart */
const wE=document.getElementById('wChart')||document.getElementById('weeklyChart');
if(wE){
  const ctx=wE.getContext('2d');
  const g=ctx.createLinearGradient(0,0,0,260);
  g.addColorStop(0,'rgba(129,140,248,.25)');
  g.addColorStop(1,'rgba(129,140,248,0)');
  new Chart(wE,{
    type:'line',
    data:{
      labels:window.wL||window.weeklyLabels||[],
      datasets:[{
        data:window.wD||window.weeklyData||[],
        borderColor:'#818CF8',backgroundColor:g,
        borderWidth:2.5,tension:.42,fill:true,
        pointBackgroundColor:'#818CF8',pointBorderColor:'#0A0C18',
        pointBorderWidth:2,pointRadius:4,pointHoverRadius:7
      }]
    },
    options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:false}},
      scales:{
        y:{beginAtZero:true,max:100,border:{display:false},grid:{color:'rgba(255,255,255,.04)'},ticks:{callback:v=>v+'%',font:{size:11},padding:8}},
        x:{border:{display:false},grid:{display:false},ticks:{font:{size:11},padding:6}}
      }
    }
  });
}

/* Doughnut */
const pE=document.getElementById('pChart')||document.getElementById('pctChart');
if(pE){
  const r=window.aR||window.attendanceRate||0;
  new Chart(pE,{
    type:'doughnut',
    data:{datasets:[{data:[r,100-r],backgroundColor:['#818CF8','rgba(255,255,255,.06)'],borderWidth:0,borderRadius:4,spacing:2}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'76%',plugins:{legend:{display:false},tooltip:{enabled:false}}}
  });
}

/* Confirm on delete buttons */
document.querySelectorAll('[data-confirm]').forEach(e=>e.addEventListener('click',ev=>{if(!confirm(e.dataset.confirm))ev.preventDefault()}));
