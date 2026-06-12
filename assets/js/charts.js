// assets/js/charts.js

// Simple canvas chart drawer (no external libraries needed)
class KaptaCharts {
    constructor(canvasId, options = {}) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;
        
        this.ctx = this.canvas.getContext('2d');
        this.data = options.data || [];
        this.labels = options.labels || [];
        this.type = options.type || 'line';
        this.color = options.color || '#F5C842'; // Gold
        
        // Ensure proper canvas resolution
        this.resize();
        window.addEventListener('resize', () => this.resize());
        
        this.draw();
    }
    
    resize() {
        if (!this.canvas) return;
        const rect = this.canvas.parentElement.getBoundingClientRect();
        this.canvas.width = rect.width;
        this.canvas.height = rect.height || 300;
        this.draw();
    }
    
    draw() {
        if (!this.ctx || this.data.length === 0) return;
        
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        if (this.type === 'line') {
            this.drawLineChart();
        } else if (this.type === 'bar') {
            this.drawBarChart();
        } else if (this.type === 'donut') {
            this.drawDonutChart();
        }
    }
    
    drawLineChart() {
        const padding = { top: 20, right: 20, bottom: 40, left: 60 };
        const width = this.canvas.width - padding.left - padding.right;
        const height = this.canvas.height - padding.top - padding.bottom;
        
        const maxData = Math.max(...this.data, 1);
        
        // Draw grid
        this.ctx.strokeStyle = 'rgba(255, 255, 255, 0.05)';
        this.ctx.lineWidth = 1;
        this.ctx.beginPath();
        for (let i = 0; i <= 4; i++) {
            const y = padding.top + (height / 4) * i;
            this.ctx.moveTo(padding.left, y);
            this.ctx.lineTo(this.canvas.width - padding.right, y);
            
            // Y-axis labels
            this.ctx.fillStyle = '#8B8BA8';
            this.ctx.font = '12px Inter';
            this.ctx.textAlign = 'right';
            this.ctx.textBaseline = 'middle';
            const val = maxData - (maxData / 4) * i;
            this.ctx.fillText(this.formatNumber(val), padding.left - 10, y);
        }
        this.ctx.stroke();
        
        // Draw Line
        this.ctx.beginPath();
        this.ctx.strokeStyle = this.color;
        this.ctx.lineWidth = 3;
        this.ctx.lineJoin = 'round';
        this.ctx.lineCap = 'round';
        
        const xStep = width / Math.max(this.data.length - 1, 1);
        
        this.data.forEach((val, i) => {
            const x = padding.left + i * xStep;
            const y = padding.top + height - (val / maxData) * height;
            
            if (i === 0) this.ctx.moveTo(x, y);
            else this.ctx.lineTo(x, y);
        });
        
        this.ctx.stroke();
        
        // Fill area
        this.ctx.lineTo(padding.left + width, padding.top + height);
        this.ctx.lineTo(padding.left, padding.top + height);
        this.ctx.closePath();
        
        const gradient = this.ctx.createLinearGradient(0, padding.top, 0, padding.top + height);
        gradient.addColorStop(0, this.color + '40'); // 25% opacity
        gradient.addColorStop(1, this.color + '00'); // 0% opacity
        
        this.ctx.fillStyle = gradient;
        this.ctx.fill();
        
        // X-axis labels
        this.ctx.fillStyle = '#8B8BA8';
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'top';
        this.labels.forEach((label, i) => {
            // Only draw some labels if there are too many
            if (this.labels.length > 10 && i % Math.ceil(this.labels.length / 5) !== 0 && i !== this.labels.length - 1 && i !== 0) return;
            
            const x = padding.left + i * xStep;
            this.ctx.fillText(label, x, padding.top + height + 10);
        });
    }
    
    drawDonutChart() {
        const cx = this.canvas.width / 2;
        const cy = this.canvas.height / 2;
        const radius = Math.min(cx, cy) - 20;
        const innerRadius = radius * 0.7;
        
        const total = this.data.reduce((sum, val) => sum + val, 0);
        let startAngle = -Math.PI / 2;
        
        const colors = [this.color, '#1A1A2E', '#7C3AED', '#10B981'];
        
        this.data.forEach((val, i) => {
            const sliceAngle = (val / total) * 2 * Math.PI;
            
            this.ctx.beginPath();
            this.ctx.arc(cx, cy, radius, startAngle, startAngle + sliceAngle);
            this.ctx.arc(cx, cy, innerRadius, startAngle + sliceAngle, startAngle, true);
            this.ctx.closePath();
            
            this.ctx.fillStyle = colors[i % colors.length];
            this.ctx.fill();
            
            startAngle += sliceAngle;
        });
        
        // Draw total in center
        this.ctx.fillStyle = '#FFFFFF';
        this.ctx.font = 'bold 24px Outfit';
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'middle';
        
        // If it's a budget chart, show percentage spent
        if (this.data.length === 2) {
            const percent = Math.round((this.data[0] / total) * 100) || 0;
            this.ctx.fillText(percent + '%', cx, cy);
            this.ctx.font = '12px Inter';
            this.ctx.fillStyle = '#8B8BA8';
            this.ctx.fillText('Gasto', cx, cy + 20);
        }
    }
    
    formatNumber(num) {
        if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
        return Math.floor(num).toString();
    }
}
