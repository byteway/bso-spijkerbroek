document.addEventListener('DOMContentLoaded', function(){

 function load(){
  fetch(ajaxurl+'?action=bso_dashboard_data')
   .then(r=>r.json())
   .then(d=>{
    document.getElementById('app').innerHTML=d.data.html;
   });
 }

 load();
 setInterval(load,5000);
});
