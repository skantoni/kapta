// assets/js/dashboard.js

document.addEventListener('DOMContentLoaded', function() {
    // Mobile Sidebar Toggle
    const mobileToggle = document.getElementById('mobile-toggle');
    const sidebar = document.getElementById('sidebar');
    
    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && e.target !== mobileToggle && !mobileToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

    // Tabs Functionality
    const tabBtns = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');

    if (tabBtns.length > 0) {
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.dataset.target;
                
                tabBtns.forEach(b => b.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                btn.classList.add('active');
                document.getElementById(target).classList.add('active');
            });
        });
    }

    // Copy to Clipboard
    const copyBtns = document.querySelectorAll('.copy-btn');
    copyBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const text = btn.dataset.copy;
            navigator.clipboard.writeText(text).then(() => {
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="ph ph-check"></i> Copiado!';
                btn.classList.add('text-green-500');
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.remove('text-green-500');
                }, 2000);
            });
        });
    });

    // Wallet Deposit Fee Calculator
    const depositAmountInput = document.getElementById('deposit_amount');
    const feePreview = document.getElementById('fee_preview');
    
    if (depositAmountInput && feePreview) {
        depositAmountInput.addEventListener('input', function() {
            const amount = parseFloat(this.value) || 0;
            const feePercent = parseFloat(this.dataset.fee) || 0.10;
            
            if (amount > 0) {
                const fee = amount * feePercent;
                const net = amount - fee;
                
                feePreview.innerHTML = `
                    <div class="text-sm mt-2 p-3 bg-dark-surface rounded border border-dark-border">
                        <div class="flex justify-between mb-1 text-secondary">
                            <span>Valor:</span>
                            <span>Kz ${amount.toLocaleString('pt-PT')}</span>
                        </div>
                        <div class="flex justify-between mb-1 text-red-400">
                            <span>Taxa (${(feePercent * 100).toFixed(0)}%):</span>
                            <span>- Kz ${fee.toLocaleString('pt-PT')}</span>
                        </div>
                        <div class="flex justify-between font-bold text-primary border-t border-dark-border pt-1 mt-1">
                            <span>Créditos Reais:</span>
                            <span class="text-gold">Kz ${net.toLocaleString('pt-PT')}</span>
                        </div>
                    </div>
                `;
            } else {
                feePreview.innerHTML = '';
            }
        });
    }

    // Campaign Create CPM Preview
    const cpmInput = document.getElementById('cpm_rate');
    const viewsEstimate = document.getElementById('views_estimate');
    const budgetInput = document.getElementById('budget');
    
    function updateCampaignPreview() {
        if (!cpmInput || !viewsEstimate || !budgetInput) return;
        
        const cpm = parseFloat(cpmInput.value) || 0;
        const budget = parseFloat(budgetInput.value) || 0;
        
        if (cpm > 0 && budget > 0) {
            const potentialViews = (budget / cpm) * 1000;
            let formattedViews = potentialViews.toLocaleString('pt-PT', {maximumFractionDigits: 0});
            
            if (potentialViews >= 1000000) {
                formattedViews = (potentialViews / 1000000).toFixed(1) + 'M';
            } else if (potentialViews >= 1000) {
                formattedViews = (potentialViews / 1000).toFixed(1) + 'K';
            }
            
            viewsEstimate.innerHTML = `Orçamento dá para aprox. <strong class="text-gold">${formattedViews} views</strong>`;
        } else {
            viewsEstimate.innerHTML = '';
        }
    }

    if (cpmInput && budgetInput) {
        cpmInput.addEventListener('input', updateCampaignPreview);
        budgetInput.addEventListener('input', updateCampaignPreview);
    }
    
    // Sync Views Button (AJAX)
    const syncBtns = document.querySelectorAll('.btn-sync-views');
    syncBtns.forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="ph ph-spinner animate-spin"></i> A sincronizar...';
            this.disabled = true;
            
            const submissionId = this.dataset.id;
            const campaignId = this.dataset.campaign;
            
            try {
                const formData = new FormData();
                if (submissionId) formData.append('submission_id', submissionId);
                if (campaignId) formData.append('campaign_id', campaignId);
                
                const response = await fetch(`${window.APP_URL}/api/views-sync.php`, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.innerHTML = '<i class="ph ph-check"></i> Sincronizado';
                    this.classList.remove('btn-secondary');
                    this.classList.add('bg-green-500', 'text-white', 'border-transparent');
                    
                    // Reload page after a short delay to show updated stats
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    this.innerHTML = '<i class="ph ph-x"></i> Erro';
                    alert(result.message || 'Erro ao sincronizar views.');
                    setTimeout(() => {
                        this.innerHTML = originalHtml;
                        this.disabled = false;
                    }, 2000);
                }
            } catch (error) {
                console.error('Sync error:', error);
                this.innerHTML = '<i class="ph ph-x"></i> Erro de rede';
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                }, 2000);
            }
        });
    });
});
