document.addEventListener('DOMContentLoaded', function () {
 const refreshTargets = [];

 const app = document.getElementById('app');
 if (app) {
  refreshTargets.push(app);
 }

 document.querySelectorAll('[data-bso-scoreboard]').forEach(function (node) {
  refreshTargets.push(node);
 });

 if (!refreshTargets.length) {
  return;
 }

 function loadTarget(container) {
  const params = new URLSearchParams({ action: 'bso_dashboard_data' });
  const gameId = container.dataset.gameId || '';

  if (gameId && Number(gameId) > 0) {
   params.set('game_id', gameId);
  }

  fetch(ajaxurl + '?' + params.toString())
   .then(function (response) {
    return response.json();
   })
   .then(function (payload) {
    if (payload && payload.success && payload.data && payload.data.html) {
     container.innerHTML = payload.data.html;
     return;
    }

    container.innerHTML = '<p>Scoredata is momenteel niet beschikbaar.</p>';
   })
   .catch(function () {
    container.innerHTML = '<p>Scoredata kon niet worden geladen.</p>';
   });
 }

 function loadAll() {
  refreshTargets.forEach(loadTarget);
 }

 loadAll();
 setInterval(loadAll, 5000);
});
