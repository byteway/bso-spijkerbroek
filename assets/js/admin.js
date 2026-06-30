document.addEventListener('DOMContentLoaded', function(){

 const app = document.getElementById('app');
 if (!app) {
  return;
 }

 function renderMessage(type, text) {
  return '<p class="bso-dashboard-message bso-dashboard-message--' + type + '">' + text + '</p>';
 }

 function load(){
  const params = new URLSearchParams({ action: 'bso_dashboard_data' });
  const gameId = app.dataset.gameId || '';
  if (gameId && Number(gameId) > 0) {
   params.set('game_id', gameId);
  }

  fetch(ajaxurl+'?'+params.toString())
   .then(function (response) {
    if (!response.ok) {
     throw new Error('HTTP ' + response.status);
    }
    return response.json();
   })
   .then(function (payload) {
    if (payload && payload.success && payload.data && payload.data.html) {
     app.innerHTML = payload.data.html;
     return;
    }

    app.innerHTML = renderMessage('warning', 'Er is momenteel geen dashboarddata beschikbaar.');
   })
   .catch(function () {
    app.innerHTML = renderMessage('error', 'Dashboarddata kon niet worden geladen. Controleer je verbinding en probeer opnieuw.');
   });
 }

 load();
 setInterval(load,5000);
});
