const canvas = document.getElementById("gameCanvas");
const ctx = canvas.getContext("2d");

// simpele speler (blokje)
let player = {
  x: 50,
  y: 50,
  size: 30
};

function draw() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  ctx.fillStyle = "blue";
  ctx.fillRect(player.x, player.y, player.size, player.size);
}

// simpele beweging
document.addEventListener("keydown", (e) => {
  if (e.key === "ArrowRight") player.x += 10;
  if (e.key === "ArrowLeft") player.x -= 10;
  if (e.key === "ArrowUp") player.y -= 10;
  if (e.key === "ArrowDown") player.y += 10;

  draw();
});

// start
draw();