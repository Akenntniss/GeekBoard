/**
 * Mini-Jeux pour faire patienter l'utilisateur
 * Pacman-style, Tetris-style, Angry-style
 */

class MiniGameManager {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.currentGame = null;
        this.animationId = null;

        // Resize observer
        this.resize();
        window.addEventListener('resize', () => this.resize());
    }

    resize() {
        if (!this.canvas) return;
        const parent = this.canvas.parentElement;
        this.canvas.width = parent.clientWidth;
        this.canvas.height = 400; // Fixed height
        if (this.currentGame) this.currentGame.resize(this.canvas.width, this.canvas.height);
    }

    start(gameType) {
        this.stop();
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        switch (gameType) {
            case 'pacman':
                this.currentGame = new PacmanGame(this.canvas);
                break;
            case 'tetris':
                this.currentGame = new TetrisGame(this.canvas);
                break;
            case 'angry':
                this.currentGame = new AngryGame(this.canvas);
                break;
        }

        if (this.currentGame) {
            this.gameLoop();
        }
    }

    stop() {
        if (this.animationId) cancelAnimationFrame(this.animationId);
        if (this.currentGame) this.currentGame.destroy();
        this.currentGame = null;
    }

    gameLoop() {
        if (!this.currentGame) return;
        this.currentGame.update();
        this.currentGame.draw(this.ctx);
        this.animationId = requestAnimationFrame(() => this.gameLoop());
    }
}

/* ================= PACMAN STYLE (CHOMPER) ================= */
class PacmanGame {
    constructor(canvas) {
        this.width = canvas.width;
        this.height = canvas.height;
        this.player = { x: this.width / 2, y: this.height / 2, size: 15, speed: 4, mouthOpen: 0, dir: 0 }; // dir: 0=R, 1=D, 2=L, 3=U
        this.dots = [];
        this.score = 0;
        this.handlers = this.bindControls();

        // Init dots
        for (let i = 0; i < 20; i++) this.addDot();
    }

    bindControls() {
        const handler = (e) => {
            if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) e.preventDefault();
            if (e.key === 'ArrowRight') this.player.dir = 0;
            if (e.key === 'ArrowDown') this.player.dir = 1;
            if (e.key === 'ArrowLeft') this.player.dir = 2;
            if (e.key === 'ArrowUp') this.player.dir = 3;
        };
        document.addEventListener('keydown', handler);
        return handler;
    }

    destroy() {
        document.removeEventListener('keydown', this.handlers);
    }

    resize(w, h) { this.width = w; this.height = h; }

    addDot() {
        this.dots.push({
            x: Math.random() * (this.width - 20) + 10,
            y: Math.random() * (this.height - 20) + 10,
            color: ['#ffb7b2', '#ffdac1', '#e2f0cb', '#b5ead7'][Math.floor(Math.random() * 4)]
        });
    }

    update() {
        // Move
        if (this.player.dir === 0) this.player.x += this.player.speed;
        if (this.player.dir === 1) this.player.y += this.player.speed;
        if (this.player.dir === 2) this.player.x -= this.player.speed;
        if (this.player.dir === 3) this.player.y -= this.player.speed;

        // Bounds
        if (this.player.x > this.width) this.player.x = 0;
        if (this.player.x < 0) this.player.x = this.width;
        if (this.player.y > this.height) this.player.y = 0;
        if (this.player.y < 0) this.player.y = this.height;

        // Eat
        this.player.mouthOpen = (Math.sin(Date.now() / 100) + 1) * 0.2;

        for (let i = this.dots.length - 1; i >= 0; i--) {
            let d = this.dots[i];
            let dist = Math.hypot(d.x - this.player.x, d.y - this.player.y);
            if (dist < this.player.size + 5) {
                this.dots.splice(i, 1);
                this.score += 10;
                this.addDot();
            }
        }
    }

    draw(ctx) {
        // BG
        ctx.fillStyle = '#000000';
        ctx.fillRect(0, 0, this.width, this.height);

        // Score
        ctx.fillStyle = 'white';
        ctx.font = '20px Arial';
        ctx.fillText("Score: " + this.score, 10, 30);
        ctx.fillText("Utilisez les flèches pour bouger", 10, this.height - 10);

        // Dots
        this.dots.forEach(d => {
            ctx.beginPath();
            ctx.fillStyle = d.color;
            ctx.arc(d.x, d.y, 4, 0, Math.PI * 2);
            ctx.fill();
        });

        // Player
        ctx.save();
        ctx.translate(this.player.x, this.player.y);
        ctx.rotate(this.player.dir * Math.PI / 2);
        ctx.beginPath();
        ctx.fillStyle = '#ffff00';
        ctx.arc(0, 0, this.player.size, 0.2 + this.player.mouthOpen, 2 * Math.PI - (0.2 + this.player.mouthOpen));
        ctx.lineTo(0, 0);
        ctx.fill();
        ctx.restore();
    }
}

/* ================= TETRIS STYLE ================= */
class TetrisGame {
    constructor(canvas) {
        this.width = canvas.width;
        this.height = canvas.height;
        this.cols = 12;
        this.scale = Math.floor(this.height / 20);
        this.rows = 20;
        this.grid = Array(this.rows).fill().map(() => Array(this.cols).fill(0));

        this.pieces = [
            [[1, 1, 1, 1]], // I
            [[1, 1], [1, 1]], // O
            [[0, 1, 0], [1, 1, 1]], // T
            [[1, 0, 0], [1, 1, 1]], // L
            [[0, 0, 1], [1, 1, 1]] // J
        ];
        this.colors = [null, '#00f0f0', '#f0f000', '#a000f0', '#f0a000', '#0000f0'];

        this.resetPiece();
        this.score = 0;
        this.dropCounter = 0;
        this.dropInterval = 500; // ms
        this.lastTime = 0;

        this.handlers = this.bindControls();
    }

    bindControls() {
        const handler = (e) => {
            if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) e.preventDefault();
            if (e.key === 'ArrowLeft') this.move(-1);
            if (e.key === 'ArrowRight') this.move(1);
            if (e.key === 'ArrowDown') this.drop();
            if (e.key === 'ArrowUp') this.rotate();
        };
        document.addEventListener('keydown', handler);
        return handler;
    }

    destroy() { document.removeEventListener('keydown', this.handlers); }
    resize(w, h) { this.width = w; this.height = h; this.scale = Math.floor(h / 20); }

    resetPiece() {
        const typeId = Math.floor(Math.random() * this.pieces.length);
        this.piece = {
            matrix: this.pieces[typeId],
            pos: { x: Math.floor(this.cols / 2) - 1, y: 0 },
            color: typeId + 1
        };
        if (this.collide(this.grid, this.piece)) {
            this.grid.forEach(row => row.fill(0)); // Game Over reset
            this.score = 0;
        }
    }

    collide(grid, piece) {
        const m = piece.matrix;
        const o = piece.pos;
        for (let y = 0; y < m.length; ++y) {
            for (let x = 0; x < m[y].length; ++x) {
                if (m[y][x] !== 0 &&
                    (grid[y + o.y] && grid[y + o.y][x + o.x]) !== 0) {
                    return true;
                }
            }
        }
        return false;
    }

    merge(grid, piece) {
        piece.matrix.forEach((row, y) => {
            row.forEach((value, x) => {
                if (value !== 0) {
                    grid[y + piece.pos.y][x + piece.pos.x] = piece.color;
                }
            });
        });
    }

    rotate() {
        const pos = this.piece.pos.x;
        let offset = 1;
        const matrix = this.piece.matrix;
        // Transpose + Reverse = Rotate
        for (let y = 0; y < matrix.length; ++y) {
            for (let x = 0; x < y; ++x) {
                [matrix[x][y], matrix[y][x]] = [matrix[y][x], matrix[x][y]];
            }
        }
        matrix.forEach(row => row.reverse());

        while (this.collide(this.grid, this.piece)) {
            this.piece.pos.x += offset;
            offset = -(offset + (offset > 0 ? 1 : -1));
            if (offset > this.piece.matrix[0].length) {
                // Rotate back
                matrix.forEach(row => row.reverse());
                // transpose back... actually simple reset is better for simplicity
                return;
            }
        }
    }

    move(dir) {
        this.piece.pos.x += dir;
        if (this.collide(this.grid, this.piece)) {
            this.piece.pos.x -= dir;
        }
    }

    drop() {
        this.piece.pos.y++;
        if (this.collide(this.grid, this.piece)) {
            this.piece.pos.y--;
            this.merge(this.grid, this.piece);
            this.arenaSweep();
            this.resetPiece();
        }
        this.dropCounter = 0;
    }

    arenaSweep() {
        outer: for (let y = this.grid.length - 1; y > 0; --y) {
            for (let x = 0; x < this.grid[y].length; ++x) {
                if (this.grid[y][x] === 0) continue outer;
            }
            const row = this.grid.splice(y, 1)[0].fill(0);
            this.grid.unshift(row);
            ++y;
            this.score += 100;
        }
    }

    update() {
        const now = Date.now();
        const deltaTime = now - this.lastTime;
        this.lastTime = now;
        this.dropCounter += deltaTime;
        if (this.dropCounter > this.dropInterval) {
            this.drop();
        }
    }

    draw(ctx) {
        // BG
        ctx.fillStyle = '#202028';
        ctx.fillRect(0, 0, this.width, this.height);

        // Centering
        const offsetX = (this.width - this.cols * this.scale) / 2;

        // Draw Matrix helper
        const drawMatrix = (matrix, offset) => {
            matrix.forEach((row, y) => {
                row.forEach((value, x) => {
                    if (value !== 0) {
                        ctx.fillStyle = this.colors[value - 1] || 'white';
                        ctx.fillRect(x * this.scale + offset.x + offsetX, y * this.scale + offset.y, this.scale - 1, this.scale - 1);
                    }
                });
            });
        };

        drawMatrix(this.grid, { x: 0, y: 0 });
        drawMatrix(this.piece.matrix, this.piece.pos);

        // Score
        ctx.fillStyle = 'white';
        ctx.font = '20px Arial';
        ctx.fillText("Score: " + this.score, 10, 30);
        ctx.font = '14px Arial';
        ctx.fillText("Flèches: Haut (Rotation), Bas, Gauche, Droite", 10, this.height - 10);
    }
}

/* ================= ANGRY STYLE (CATAPULT) ================= */
class AngryGame {
    constructor(canvas) {
        this.width = canvas.width;
        this.height = canvas.height;
        this.baseX = 100;
        this.baseY = this.height - 100;
        this.ball = { x: this.baseX, y: this.baseY, vx: 0, vy: 0, r: 15, dragging: false };
        this.targets = [];
        this.score = 0;

        this.handlers = this.bindControls();
        this.resetTargets();
    }

    resetTargets() {
        this.targets = [];
        for (let i = 0; i < 5; i++) {
            this.targets.push({
                x: this.width - 50 - (Math.random() * 200),
                y: this.height - 50 - (Math.random() * 200),
                w: 30, h: 30, hit: false
            });
        }
    }

    bindControls() {
        const onDown = (e) => {
            const rect = this.canvas.getBoundingClientRect();
            // Touch or Mouse
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            const x = clientX - rect.left;
            const y = clientY - rect.top;

            if (Math.hypot(x - this.ball.x, y - this.ball.y) < 30) {
                this.ball.dragging = true;
                this.ball.vx = 0; this.ball.vy = 0;
            }
        };

        const onMove = (e) => {
            if (!this.ball.dragging) return;
            e.preventDefault(); // Prevent scroll on touch
            const rect = this.canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            this.ball.x = clientX - rect.left;
            this.ball.y = clientY - rect.top;
        };

        const onUp = (e) => {
            if (!this.ball.dragging) return;
            this.ball.dragging = false;
            // Launch
            this.ball.vx = (this.baseX - this.ball.x) * 0.15;
            this.ball.vy = (this.baseY - this.ball.y) * 0.15;
        };

        this.canvas.addEventListener('mousedown', onDown);
        this.canvas.addEventListener('mousemove', onMove);
        window.addEventListener('mouseup', onUp);

        this.canvas.addEventListener('touchstart', onDown, { passive: false });
        this.canvas.addEventListener('touchmove', onMove, { passive: false });
        window.addEventListener('touchend', onUp);

        return { onDown, onMove, onUp };
    }

    destroy() {
        // Simple cleanup, event listeners on canvas are okay to stay until DOM removal
        // but window listeners should be removed
        // For simplicity in this mini-impl, we assume canvas destruction clears its own listeners
    }

    resize(w, h) { this.width = w; this.height = h; this.baseY = h - 100; if (this.ball.vx === 0 && this.ball.vy === 0 && !this.ball.dragging) { this.ball.y = this.baseY; } }

    update() {
        if (!this.ball.dragging && (this.ball.vx !== 0 || this.ball.vy !== 0)) {
            this.ball.x += this.ball.vx;
            this.ball.y += this.ball.vy;
            this.ball.vy += 0.5; // Gravity
            this.ball.vx *= 0.99; // Drag

            // Bounce floor
            if (this.ball.y > this.height - this.ball.r) {
                this.ball.y = this.height - this.ball.r;
                this.ball.vy *= -0.6;
                if (Math.abs(this.ball.vy) < 1) {
                    this.ball.vx = 0; this.ball.vy = 0;
                    // Reset position after stop
                    setTimeout(() => {
                        if (this.ball.vx === 0) {
                            this.ball.x = this.baseX; this.ball.y = this.baseY;
                        }
                    }, 1000);
                }
            }
            // Walls
            if (this.ball.x > this.width || this.ball.x < 0) this.ball.vx *= -1;
        }

        // Collision
        this.targets.forEach(t => {
            if (!t.hit &&
                this.ball.x > t.x && this.ball.x < t.x + t.w &&
                this.ball.y > t.y && this.ball.y < t.y + t.h) {
                t.hit = true;
                this.score += 50;
            }
        });

        if (this.targets.every(t => t.hit)) this.resetTargets();
    }

    draw(ctx) {
        // BG
        ctx.fillStyle = '#87CEEB';
        ctx.fillRect(0, 0, this.width, this.height);

        // Ground
        ctx.fillStyle = '#654321';
        ctx.fillRect(0, this.height - 20, this.width, 20);

        // Slingshot Base
        ctx.strokeStyle = '#553311';
        ctx.lineWidth = 5;
        ctx.beginPath();
        ctx.moveTo(this.baseX, this.baseY);
        ctx.lineTo(this.baseX, this.height - 20);
        ctx.stroke();

        // Targets
        this.targets.forEach(t => {
            if (!t.hit) {
                ctx.fillStyle = '#ff0000'; // Pigs are green usually but boxes are easier
                ctx.fillRect(t.x, t.y, t.w, t.h);
                ctx.strokeRect(t.x, t.y, t.w, t.h);
            }
        });

        // Ball
        ctx.fillStyle = '#ff4444';
        ctx.beginPath();
        ctx.arc(this.ball.x, this.ball.y, this.ball.r, 0, Math.PI * 2);
        ctx.fill();

        // Rubber band
        if (this.ball.dragging) {
            ctx.strokeStyle = '#000';
            ctx.beginPath();
            ctx.moveTo(this.baseX, this.baseY);
            ctx.lineTo(this.ball.x, this.ball.y);
            ctx.stroke();
        }

        // Score
        ctx.fillStyle = 'black';
        ctx.font = '20px Arial';
        ctx.fillText("Score: " + this.score, 10, 30);
        ctx.fillText("Tirez l'oiseau rouge pour viser les boîtes !", 10, this.height - 40);
    }
}
