/**
 * Confetti Effect for Raffle Game Pro
 * Canvas-based confetti explosion for winner celebration
 */

const confetti = (function () {
    const canvas = document.getElementById('confetti-canvas');
    if (!canvas) return { start: () => { }, stop: () => { } };

    const ctx = canvas.getContext('2d');
    let particles = [];
    let animationId = null;
    let isRunning = false;

    // Confetti colors
    const colors = [
        '#ff6b6b', '#ffd93d', '#6bcb77', '#4d96ff', '#9b59b6',
        '#ff8c42', '#00d2d3', '#ff6b81', '#54a0ff', '#5f27cd'
    ];

    // Resize canvas
    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }

    // Particle class
    class Particle {
        constructor() {
            this.reset();
        }

        reset() {
            this.x = Math.random() * canvas.width;
            this.y = -10;
            this.size = Math.random() * 10 + 5;
            this.speedY = Math.random() * 3 + 2;
            this.speedX = Math.random() * 4 - 2;
            this.color = colors[Math.floor(Math.random() * colors.length)];
            this.rotation = Math.random() * 360;
            this.rotationSpeed = Math.random() * 10 - 5;
            this.opacity = 1;
            this.shape = Math.random() > 0.5 ? 'rect' : 'circle';
        }

        update() {
            this.y += this.speedY;
            this.x += this.speedX;
            this.rotation += this.rotationSpeed;
            this.speedY += 0.1; // gravity
            this.speedX *= 0.99; // air resistance

            // Fade out near bottom
            if (this.y > canvas.height - 100) {
                this.opacity -= 0.02;
            }
        }

        draw() {
            ctx.save();
            ctx.translate(this.x, this.y);
            ctx.rotate((this.rotation * Math.PI) / 180);
            ctx.globalAlpha = this.opacity;
            ctx.fillStyle = this.color;

            if (this.shape === 'rect') {
                ctx.fillRect(-this.size / 2, -this.size / 2, this.size, this.size * 0.6);
            } else {
                ctx.beginPath();
                ctx.arc(0, 0, this.size / 2, 0, Math.PI * 2);
                ctx.fill();
            }

            ctx.restore();
        }
    }

    // Animation loop
    function animate() {
        if (!isRunning) return;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        particles.forEach((particle, index) => {
            particle.update();
            particle.draw();

            // Remove dead particles
            if (particle.opacity <= 0 || particle.y > canvas.height + 50) {
                particles.splice(index, 1);
            }
        });

        animationId = requestAnimationFrame(animate);
    }

    // Burst effect - create particles in waves
    function burst(x, y, count) {
        for (let i = 0; i < count; i++) {
            const particle = new Particle();
            particle.x = x + (Math.random() * 100 - 50);
            particle.y = y;
            particle.speedY = Math.random() * -15 - 5;
            particle.speedX = Math.random() * 20 - 10;
            particles.push(particle);
        }
    }

    // Start confetti
    function start() {
        resizeCanvas();
        isRunning = true;
        particles = [];

        // Create initial burst from multiple points
        const burstPoints = [
            { x: canvas.width * 0.25, y: canvas.height },
            { x: canvas.width * 0.5, y: canvas.height },
            { x: canvas.width * 0.75, y: canvas.height }
        ];

        burstPoints.forEach(point => {
            burst(point.x, point.y, 50);
        });

        // Continuous rain from top
        const rainInterval = setInterval(() => {
            if (!isRunning) {
                clearInterval(rainInterval);
                return;
            }
            for (let i = 0; i < 5; i++) {
                particles.push(new Particle());
            }
        }, 100);

        animate();
    }

    // Stop confetti
    function stop() {
        isRunning = false;
        if (animationId) {
            cancelAnimationFrame(animationId);
        }
        // Let remaining particles fall
        setTimeout(() => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles = [];
        }, 2000);
    }

    // Handle resize
    window.addEventListener('resize', resizeCanvas);

    return {
        start: start,
        stop: stop,
        burst: burst
    };
})();
