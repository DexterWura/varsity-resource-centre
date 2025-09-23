<?php
require_once 'bootstrap.php';

use Payment\PaymentConfig;
use Payment\PaymentProcessor;

$pageTitle = 'Payment - Pro Plagiarism Checker';
$plan = $_GET['plan'] ?? 'plagiarism_checker';

$planConfig = PaymentConfig::getPlan($plan);
$enabledGateways = PaymentConfig::getEnabledGateways();

if (!$planConfig) {
    header('Location: /404.php');
    exit;
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Plan Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fa-solid fa-crown me-2"></i>
                        <?= htmlspecialchars($planConfig['name']) ?>
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="text-primary">$<?= number_format($planConfig['price'], 2) ?> <?= $planConfig['currency'] ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($planConfig['description']) ?></p>
                            
                            <h6 class="mt-4">Features included:</h6>
                            <ul class="list-unstyled">
                                <?php foreach ($planConfig['features'] as $feature): ?>
                                <li class="mb-2">
                                    <i class="fa-solid fa-check text-success me-2"></i>
                                    <?= htmlspecialchars($feature) ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="pricing-badge">
                                <span class="badge bg-warning text-dark fs-6">Pro</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-credit-card me-2"></i>
                        Choose Payment Method
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($enabledGateways)): ?>
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-exclamation-triangle me-2"></i>
                            No payment methods are currently available. Please contact support.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($enabledGateways as $gatewayKey => $gateway): ?>
                            <div class="col-md-4 mb-3">
                                <div class="payment-method-card" data-gateway="<?= $gatewayKey ?>">
                                    <div class="card h-100 border-2 payment-option">
                                        <div class="card-body text-center">
                                            <div class="payment-icon mb-3">
                                                <i class="<?= $gateway['icon'] ?> fa-3x text-primary"></i>
                                            </div>
                                            <h6 class="card-title"><?= htmlspecialchars($gateway['name']) ?></h6>
                                            <p class="card-text text-muted small">
                                                <?= htmlspecialchars($gateway['description']) ?>
                                            </p>
                                            <button class="btn btn-primary btn-sm pay-btn" 
                                                    data-gateway="<?= $gatewayKey ?>"
                                                    data-plan="<?= $plan ?>">
                                                Pay $<?= number_format($planConfig['price'], 2) ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Status -->
            <div id="paymentStatus" class="mt-4" style="display: none;">
                <div class="alert alert-info">
                    <i class="fa-solid fa-spinner fa-spin me-2"></i>
                    <span id="paymentMessage">Processing payment...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-option {
    transition: all 0.3s ease;
    cursor: pointer;
}

.payment-option:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    border-color: var(--bs-primary) !important;
}

.payment-option.selected {
    border-color: var(--bs-primary) !important;
    background-color: rgba(13, 110, 253, 0.05);
}

.pricing-badge {
    margin-top: 2rem;
}

.pricing-badge .badge {
    font-size: 1.2rem;
    padding: 0.75rem 1.5rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentOptions = document.querySelectorAll('.payment-option');
    const payButtons = document.querySelectorAll('.pay-btn');
    const paymentStatus = document.getElementById('paymentStatus');
    const paymentMessage = document.getElementById('paymentMessage');

    // Handle payment option selection
    paymentOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            paymentOptions.forEach(opt => opt.classList.remove('selected'));
            // Add selected class to clicked option
            this.classList.add('selected');
        });
    });

    // Handle payment button clicks
    payButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const gateway = this.dataset.gateway;
            const plan = this.dataset.plan;
            
            // Show payment status
            paymentStatus.style.display = 'block';
            paymentMessage.textContent = 'Processing payment...';
            
            // Simulate payment processing
            setTimeout(() => {
                paymentMessage.textContent = 'Redirecting to payment gateway...';
                
                // In real implementation, this would redirect to the actual payment gateway
                // For now, we'll show a success message
                setTimeout(() => {
                    paymentMessage.innerHTML = '<i class="fa-solid fa-check-circle me-2"></i>Payment gateway integration ready!';
                    paymentStatus.querySelector('.alert').className = 'alert alert-success';
                }, 2000);
            }, 1000);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
