// assets/js/main.js

document.addEventListener('DOMContentLoaded', function() {
    
    // Auto-dismiss flash messages
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(() => {
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            });
        }, 5000);
    }

    // Initialize Tooltips if any (simple implementation)
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', e => {
            const tooltip = document.createElement('div');
            tooltip.className = 'custom-tooltip';
            tooltip.textContent = e.target.getAttribute('data-tooltip');
            document.body.appendChild(tooltip);
            
            const rect = e.target.getBoundingClientRect();
            tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            
            e.target.addEventListener('mouseleave', () => {
                tooltip.remove();
            }, { once: true });
        });
    });

    // Animate numbers
    const animatedNumbers = document.querySelectorAll('.animate-number');
    
    const animateValue = (obj, start, end, duration) => {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            
            let current = Math.floor(progress * (end - start) + start);
            
            // Format logic based on data attribute or default to Kz
            if (obj.dataset.format === 'kz') {
                obj.innerHTML = 'Kz ' + current.toLocaleString('pt-PT');
            } else if (obj.dataset.format === 'views') {
                obj.innerHTML = current.toLocaleString('pt-PT');
            } else {
                obj.innerHTML = current;
            }
            
            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                if (obj.dataset.suffix) {
                    obj.innerHTML += obj.dataset.suffix;
                }
            }
        };
        window.requestAnimationFrame(step);
    }

    const numberObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = parseInt(entry.target.dataset.value || 0);
                if (target > 0) {
                    animateValue(entry.target, 0, target, 2000);
                    numberObserver.unobserve(entry.target);
                }
            }
        });
    });

    animatedNumbers.forEach(num => {
        numberObserver.observe(num);
    });

    // Scroll Animation Observer
    const animateElements = document.querySelectorAll('.animate-on-scroll');
    const scrollObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });

    animateElements.forEach(el => {
        scrollObserver.observe(el);
    });
    
    // Auth Canvas Particles
    const canvas = document.getElementById('auth-canvas');
    if (canvas) {
        initParticles(canvas);
    }
});

function initParticles(canvas) {
    const ctx = canvas.getContext('2d');
    let width, height, particles;

    function resize() {
        width = window.innerWidth;
        height = window.innerHeight;
        canvas.width = width;
        canvas.height = height;
    }

    class Particle {
        constructor() {
            this.x = Math.random() * width;
            this.y = Math.random() * height;
            this.size = Math.random() * 2 + 0.5;
            this.speedX = Math.random() * 1 - 0.5;
            this.speedY = Math.random() * 1 - 0.5;
            this.color = Math.random() > 0.5 ? 'rgba(245, 200, 66, 0.4)' : 'rgba(124, 58, 237, 0.3)';
        }

        update() {
            this.x += this.speedX;
            this.y += this.speedY;

            if (this.x > width) this.x = 0;
            if (this.x < 0) this.x = width;
            if (this.y > height) this.y = 0;
            if (this.y < 0) this.y = height;
        }

        draw() {
            ctx.fillStyle = this.color;
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    function init() {
        resize();
        particles = [];
        const numParticles = Math.min(window.innerWidth / 10, 100);
        for (let i = 0; i < numParticles; i++) {
            particles.push(new Particle());
        }
    }

    function animate() {
        ctx.clearRect(0, 0, width, height);
        
        for (let i = 0; i < particles.length; i++) {
            particles[i].update();
            particles[i].draw();
            
            // Connect particles
            for (let j = i; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < 100) {
                    ctx.beginPath();
                    ctx.strokeStyle = `rgba(245, 200, 66, ${0.1 - distance/1000})`;
                    ctx.lineWidth = 0.5;
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.stroke();
                }
            }
        }
        requestAnimationFrame(animate);
    }

    window.addEventListener('resize', init);
    init();
    animate();
}
