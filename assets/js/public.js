document.addEventListener('DOMContentLoaded', function(){

 const app = document.getElementById('app');
 if (!app) {
  return;
 }

 function load(){
  const params = new URLSearchParams({ action: 'bso_dashboard_data' });
  const gameId = app.dataset.gameId || '';
  if (gameId && Number(gameId) > 0) {
   params.set('game_id', gameId);
  }

  fetch(ajaxurl+'?'+params.toString())
   .then(r=>r.json())
   .then(d=>{
    app.innerHTML=d.data.html;
   });
 }

 load();
 setInterval(load,5000);
});
